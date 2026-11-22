@extends('layouts.app')

@section('title', 'معدات بدون عمل فعلي بالمشاريع')
@section('page_title', 'معدات بدون عمل فعلي بالمشاريع')
@section('page_subtitle', 'رصد المعدات المتوقفة دون تشغيل فعلي في المشاريع والمواقع، وتتبع إجمالي أيام التوقف لإعادة التوزيع')

@section('content')
    @php
        $moduleConfig = [
            'baseUrl' => url('/idle-project-equipment'),
            'storeUrl' => route('idle-project-equipment.store'),
            'exportBaseUrl' => route('idle-project-equipment.export'),
            'initialExportUrl' => route('idle-project-equipment.export', array_filter($filters)),
            'initialFilters' => [
                'search' => $filters['search'] ?? '',
                'branch' => $filters['branch'] ?? '',
                'site_location' => $filters['site_location'] ?? '',
                'equipment_type' => $filters['equipment_type'] ?? '',
                'idle_band' => $filters['idle_band'] ?? '',
            ],
            'defaultFilters' => [
                'search' => '',
                'branch' => '',
                'site_location' => '',
                'equipment_type' => '',
                'idle_band' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'equipment_code' => '',
                'equipment_type' => '',
                'driver_name' => '',
                'employee_id' => '',
                'branch' => '',
                'site_location' => '',
                'equipment_status' => 'بدون عمل فعلي',
                'total_idle_days' => '0',
            ],
        ];
    @endphp

    <div x-data="fleetModuleManager(@js($moduleConfig))" class="space-y-5">
        {{-- KPI Mini-Header --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs">
                <p class="text-xs font-bold text-slate-600">إجمالي المعدات</p>
                <p class="mt-1.5 text-2xl font-extrabold text-slate-900 tabular-nums" x-text="kpis.total">{{ $kpis['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200/90 bg-rose-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-rose-700">توقف مرتفع (7 أيام فأكثر)</p>
                <p class="mt-1.5 text-2xl font-extrabold text-rose-800 tabular-nums" x-text="kpis.high_idle_days">{{ $kpis['high_idle_days'] }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200/90 bg-amber-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-amber-800">مجموع أيام التوقف</p>
                <p class="mt-1.5 text-2xl font-extrabold text-amber-900 tabular-nums" x-text="kpis.sum_idle_days">{{ $kpis['sum_idle_days'] }}</p>
            </div>
            <div class="rounded-2xl border border-sky-200/90 bg-sky-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-sky-800">المشاريع والمواقع المتأثرة</p>
                <p class="mt-1.5 text-2xl font-extrabold text-sky-900 tabular-nums" x-text="kpis.sites_count">{{ $kpis['sites_count'] }}</p>
            </div>
        </section>

        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث بكود المعدة، نوع المعدة، اسم السائق، الرقم الوظيفي، الفرع، أو الموقع..."
            :create-model="\App\Models\IdleProjectEquipment::class"
            create-label="إضافة معدة بدون عمل"
        >
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
                <label class="block text-[11px] font-bold text-slate-600 mb-1">الموقع / المشروع</label>
                <select x-model="filters.site_location" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المواقع</option>
                    @foreach ($options['sites'] as $site)
                        <option value="{{ $site }}">{{ $site }}</option>
                    @endforeach
                </select>
            </div>

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
                <label class="block text-[11px] font-bold text-slate-600 mb-1">فئة أيام التوقف</label>
                <select x-model="filters.idle_band" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">الكل</option>
                    <option value="high">مرتفع (7 أيام فأكثر)</option>
                    <option value="medium">متوسط (4 – 6 أيام)</option>
                    <option value="low">منخفض (1 – 3 أيام)</option>
                </select>
            </div>
        </x-module.toolbar>

        {{-- Ruled Data Table --}}
        <div class="relative overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
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
                @include('modules.idle-project-equipment._table', [
                    'records' => $records,
                    'filteredIdleDaysTotal' => $filteredIdleDaysTotal,
                ])
            </div>
        </div>

        {{-- Slide-Over Create/Edit Drawer (No Required Fields) --}}
        <x-module.drawer
            create-title="إضافة معدة بدون عمل فعلي"
            edit-title="تعديل سجل معدة بدون عمل فعلي"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">كود المعدة</label>
                    <input type="text" dir="ltr" x-model="form.equipment_code" placeholder="AP_000008" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('equipment_code')" x-text="firstError('equipment_code')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع المعدة</label>
                    <input type="text" list="idle-equipment-types" x-model="form.equipment_type" placeholder="فرادات / بلدوزرات / قلابات" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="idle-equipment-types">
                        @foreach ($options['equipment_types'] as $type)
                            <option value="{{ $type }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('equipment_type')" x-text="firstError('equipment_type')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">اسم السائق</label>
                    <input type="text" x-model="form.driver_name" placeholder="اسم السائق" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('driver_name')" x-text="firstError('driver_name')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الرقم الوظيفي</label>
                    <input type="text" dir="ltr" x-model="form.employee_id" placeholder="3903" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('employee_id')" x-text="firstError('employee_id')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الفرع</label>
                    <input type="text" list="idle-branches" x-model="form.branch" placeholder="الرياض / ابها / نجران" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="idle-branches">
                        @foreach ($options['branches'] as $branch)
                            <option value="{{ $branch }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('branch')" x-text="firstError('branch')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الموقع</label>
                    <input type="text" list="idle-sites" x-model="form.site_location" placeholder="مشروع الحائر / مشروع احد روفيدة" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="idle-sites">
                        @foreach ($options['sites'] as $site)
                            <option value="{{ $site }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('site_location')" x-text="firstError('site_location')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">حالة المعدة</label>
                    <input type="text" x-model="form.equipment_status" placeholder="بدون عمل فعلي" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('equipment_status')" x-text="firstError('equipment_status')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">إجمالي أيام التوقف</label>
                    <input type="number" min="0" x-model="form.total_idle_days" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('total_idle_days')" x-text="firstError('total_idle_days')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />
    </div>
@endsection

