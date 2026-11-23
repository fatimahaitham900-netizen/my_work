@extends('layouts.app')

@section('title', 'معدات تعديل استمارة')
@section('page_title', 'معدات تعديل استمارة')
@section('page_subtitle', 'متابعة المعدات التي تحتاج إلى تعديل استمارة مع حساب أيام التوقف تلقائياً من تاريخ التوقف')

@section('content')
    @php
        $moduleConfig = [
            'baseUrl' => url('/registration-amendments'),
            'storeUrl' => route('registration-amendments.store'),
            'exportBaseUrl' => route('registration-amendments.export'),
            'initialExportUrl' => route('registration-amendments.export', array_filter($filters)),
            'initialFilters' => [
                'search' => $filters['search'] ?? '',
                'equipment_type' => $filters['equipment_type'] ?? '',
                'branch' => $filters['branch'] ?? '',
            ],
            'defaultFilters' => [
                'search' => '',
                'equipment_type' => '',
                'branch' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'equipment_code' => '',
                'equipment_type' => '',
                'branch' => '',
                'stoppage_date' => now()->format('Y-m-d'),
                'notes' => '',
            ],
        ];
    @endphp

    <div
        x-data="{
            ...fleetModuleManager(@js($moduleConfig)),
            computedStoppageDays() {
                if (!this.form.stoppage_date) return null;
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const start = new Date(this.form.stoppage_date + 'T00:00:00');
                if (Number.isNaN(start.getTime())) return null;
                return Math.max(0, Math.round((today - start) / 86400000));
            }
        }"
        class="space-y-5"
    >
        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث بالكود، نوع المعدة، أو الفرع..."
            :create-model="\App\Models\RegistrationAmendment::class"
            create-label="إضافة معدة تعديل استمارة"
            :show-import-purge="true"
        >
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">نوع المعدة</label>
                <select x-model="filters.equipment_type" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الأنواع</option>
                    @foreach ($options['equipment_types'] as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
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
                @include('modules.registration-amendments._table', [
                    'records' => $records,
                ])
            </div>
        </div>

        {{-- Slide-Over Create/Edit Drawer (No Required Fields) --}}
        <x-module.drawer
            create-title="إضافة معدة تعديل استمارة"
            edit-title="تعديل سجل معدة تعديل استمارة"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الكود</label>
                    <input type="text" dir="ltr" x-model="form.equipment_code" placeholder="DT_000033" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('equipment_code')" x-text="firstError('equipment_code')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع المعدة</label>
                    <input type="text" list="reg-amend-types" x-model="form.equipment_type" placeholder="قلاب / تريلا / وايت" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="reg-amend-types">
                        @foreach ($options['equipment_types'] as $type)
                            <option value="{{ $type }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('equipment_type')" x-text="firstError('equipment_type')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الفرع</label>
                    <input type="text" list="reg-amend-branches" x-model="form.branch" placeholder="نجران / الرياض / مكة" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="reg-amend-branches">
                        @foreach ($options['branches'] as $branch)
                            <option value="{{ $branch }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('branch')" x-text="firstError('branch')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تاريخ التوقف</label>
                    <input type="date" x-model="form.stoppage_date" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('stoppage_date')" x-text="firstError('stoppage_date')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            {{-- Live Auto-Calculated Stoppage Days Preview --}}
            <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-3.5 text-xs font-extrabold text-amber-950 flex items-center justify-between">
                <span>أيام التوقف (تُحسب تلقائياً من تاريخ التوقف):</span>
                <span class="rounded-lg bg-amber-600 px-3 py-1 text-sm font-black text-white tabular-nums" x-text="computedStoppageDays() !== null ? computedStoppageDays() + ' يوم' : '—'"></span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">ملاحظات</label>
                <textarea x-model="form.notes" rows="2" placeholder="أي ملاحظات إضافية..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />

        <x-module.import-modal
            :action="route('registration-amendments.import.direct')"
            :template-url="route('registration-amendments.import.template')"
            title="استيراد وإسقاط بيانات معدات تعديل استمارة"
        />

        <x-module.purge-modal
            :action="route('registration-amendments.purge-all')"
            module-name="معدات تعديل استمارة"
            :count="$kpis['total'] ?? 0"
        />
    </div>
@endsection

