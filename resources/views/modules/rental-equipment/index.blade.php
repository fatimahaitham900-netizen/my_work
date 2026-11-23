@extends('layouts.app')

@section('title', 'معدات الإيجار')
@section('page_title', 'معدات الإيجار')
@section('page_subtitle', 'متابعة معدات الإيجار بالمشاريع، المقاولين، مبالغ الإيجار، والفروع والمشاريع')

@section('content')
    @php
        $moduleConfig = [
            'baseUrl' => url('/rental-equipment'),
            'storeUrl' => route('rental-equipment.store'),
            'exportBaseUrl' => route('rental-equipment.export'),
            'initialExportUrl' => route('rental-equipment.export', array_filter($filters)),
            'initialFilters' => [
                'search' => $filters['search'] ?? '',
                'contractor_name' => $filters['contractor_name'] ?? '',
                'equipment_type' => $filters['equipment_type'] ?? '',
                'rental_type' => $filters['rental_type'] ?? '',
                'branch' => $filters['branch'] ?? '',
                'project' => $filters['project'] ?? '',
            ],
            'defaultFilters' => [
                'search' => '',
                'contractor_name' => '',
                'equipment_type' => '',
                'rental_type' => '',
                'branch' => '',
                'project' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'contractor_name' => '',
                'equipment_type' => '',
                'rental_amount' => '0',
                'rental_type' => 'شهري',
                'branch' => '',
                'project' => '',
                'notes' => '',
            ],
        ];
    @endphp

    <div x-data="fleetModuleManager(@js($moduleConfig))" class="space-y-5">
        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث باسم المقاول، النوع، نوع الإيجار، الفرع، أو المشروع..."
            :create-model="\App\Models\RentalEquipment::class"
            create-label="إضافة معدة إيجار"
            :show-import-purge="true"
        >
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">اسم المقاول</label>
                <select x-model="filters.contractor_name" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المقاولين</option>
                    @foreach ($options['contractors'] as $contractor)
                        <option value="{{ $contractor }}">{{ $contractor }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">النوع</label>
                <select x-model="filters.equipment_type" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الأنواع</option>
                    @foreach ($options['equipment_types'] as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">نوع الإيجار</label>
                <select x-model="filters.rental_type" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">الكل</option>
                    @foreach ($options['rental_types'] as $rentalType)
                        <option value="{{ $rentalType }}">{{ $rentalType }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">الفرع</label>
                <select x-model="filters.branch" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الفروع</option>
                    @foreach ($options['branches'] as $branch)
                        <option value="{{ $branch }}">{{ $branch }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">المشروع</label>
                <select x-model="filters.project" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المشاريع</option>
                    @foreach ($options['projects'] as $project)
                        <option value="{{ $project }}">{{ $project }}</option>
                    @endforeach
                </select>
            </div>
        </x-module.toolbar>

        {{-- Ruled Data Table --}}
        <div class="relative overflow-hidden rounded-2xl border-2 border-slate-900 bg-white shadow-md">
            <div x-show="loadingTable" x-cloak class="absolute inset-0 z-20 flex items-center justify-center bg-white/70 backdrop-blur-[1px]">
                <span class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-lg">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span>جاري تحديث البيانات...</span>
                </span>
            </div>

            <div x-ref="tableContainer">
                @include('modules.rental-equipment._table', [
                    'records' => $records,
                    'filteredAmountTotal' => $filteredAmountTotal,
                ])
            </div>
        </div>

        {{-- Slide-Over Create/Edit Drawer (No Required Fields) --}}
        <x-module.drawer
            create-title="إضافة معدة إيجار جديدة"
            edit-title="تعديل سجل معدة الإيجار"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">اسم المقاول</label>
                    <input type="text" list="rental-contractors" x-model="form.contractor_name" placeholder="شركة ابن الجزيرة المحدودة" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="rental-contractors">
                        @foreach ($options['contractors'] as $contractor)
                            <option value="{{ $contractor }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('contractor_name')" x-text="firstError('contractor_name')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">النوع</label>
                    <input type="text" list="rental-equipment-types" x-model="form.equipment_type" placeholder="حفار / شيول / قلاب" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="rental-equipment-types">
                        @foreach ($options['equipment_types'] as $type)
                            <option value="{{ $type }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('equipment_type')" x-text="firstError('equipment_type')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">مبلغ الإيجار (ر.س)</label>
                    <input type="number" step="0.01" min="0" x-model="form.rental_amount" placeholder="16000" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('rental_amount')" x-text="firstError('rental_amount')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع الإيجار</label>
                    <input type="text" list="rental-types" x-model="form.rental_type" placeholder="شهري / يومي" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="rental-types">
                        <option value="شهري"></option>
                        <option value="يومي"></option>
                        <option value="بالساعة"></option>
                    </datalist>
                    <p x-show="firstError('rental_type')" x-text="firstError('rental_type')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الفرع</label>
                    <input type="text" list="rental-branches" x-model="form.branch" placeholder="الرياض" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="rental-branches">
                        @foreach ($options['branches'] as $branch)
                            <option value="{{ $branch }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('branch')" x-text="firstError('branch')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">المشروع</label>
                    <input type="text" list="rental-projects" x-model="form.project" placeholder="الحائر" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="rental-projects">
                        @foreach ($options['projects'] as $project)
                            <option value="{{ $project }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('project')" x-text="firstError('project')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">ملاحظات</label>
                <textarea x-model="form.notes" rows="2" placeholder="أي ملاحظات إضافية..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />

        <x-module.import-modal
            :action="route('rental-equipment.import.direct')"
            :template-url="route('rental-equipment.import.template')"
            title="استيراد وإسقاط بيانات معدات الإيجار"
        />

        <x-module.purge-modal
            :action="route('rental-equipment.purge-all')"
            module-name="معدات الإيجار"
            :count="$kpis['total'] ?? 0"
        />
    </div>
@endsection
