@extends('layouts.app')

@section('title', 'السيارات المتعطلة بالورش')
@section('page_title', 'السيارات المتعطلة بالورش')
@section('page_subtitle', 'سجل السيارات المتعطلة والتي تحتاج صيانة أو فحصاً دورياً وشاملاً موزّعة حسب الفرع والإدارة')

@section('content')
    @php
        $moduleConfig = [
            'baseUrl' => url('/car-breakdowns'),
            'storeUrl' => route('car-breakdowns.store'),
            'exportBaseUrl' => route('car-breakdowns.export'),
            'initialExportUrl' => route('car-breakdowns.export', array_filter($filters)),
            'initialFilters' => [
                'search' => $filters['search'] ?? '',
                'vehicle_category' => $filters['vehicle_category'] ?? '',
                'branch' => $filters['branch'] ?? '',
                'department' => $filters['department'] ?? '',
                'fuel_type' => $filters['fuel_type'] ?? '',
                'model_year' => $filters['model_year'] ?? '',
            ],
            'defaultFilters' => [
                'search' => '',
                'vehicle_category' => '',
                'branch' => '',
                'department' => '',
                'fuel_type' => '',
                'model_year' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'car_code' => '',
                'vehicle_category' => 'سيارة',
                'car_type' => '',
                'plate_number' => '',
                'model_year' => '',
                'previous_receiver' => '',
                'employee_id' => '',
                'department' => '',
                'job_title' => '',
                'fuel_type' => 'بنزين',
                'branch' => '',
                'condition_status' => '',
            ],
        ];
    @endphp

    <div x-data="fleetModuleManager(@js($moduleConfig))" class="space-y-5">
        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث بكود السيارة، النوع، اللوحة، المستلم السابق، الرقم الوظيفي، الفرع، أو الحالة..."
            :create-model="\App\Models\CarBreakdown::class"
            create-label="إضافة عطل سيارة"
            :show-import-purge="true"
        >
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">التصنيف</label>
                <select x-model="filters.vehicle_category" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">الكل (سيارات وباصات)</option>
                    <option value="سيارة">سيارة</option>
                    <option value="باص">باص</option>
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
                <label class="block text-[11px] font-bold text-slate-600 mb-1">الإدارة</label>
                <select x-model="filters.department" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الإدارات</option>
                    @foreach ($options['departments'] as $department)
                        <option value="{{ $department }}">{{ $department }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">نوع الوقود</label>
                <select x-model="filters.fuel_type" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">الكل</option>
                    @foreach ($options['fuel_types'] as $fuel)
                        <option value="{{ $fuel }}">{{ $fuel }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">الموديل</label>
                <select x-model="filters.model_year" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الموديلات</option>
                    @foreach ($options['model_years'] as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
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
                @include('modules.car-breakdowns._table', ['records' => $records])
            </div>
        </div>

        {{-- Slide-Over Create/Edit Drawer (No Required Fields) --}}
        <x-module.drawer
            create-title="إضافة سجل عطل سيارة جديد"
            edit-title="تعديل سجل عطل السيارة"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">كود المركبة</label>
                    <input type="text" dir="ltr" x-model="form.car_code" placeholder="C_000205 / BUS_000013" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('car_code')" x-text="firstError('car_code')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">التصنيف (سيارة / باص)</label>
                    <select x-model="form.vehicle_category" class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold focus:border-slate-900 focus:outline-none">
                        <option value="سيارة">سيارة</option>
                        <option value="باص">باص</option>
                    </select>
                    <p x-show="firstError('vehicle_category')" x-text="firstError('vehicle_category')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">اللوحة</label>
                    <input type="text" x-model="form.plate_number" placeholder="أ ل ر 9882" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('plate_number')" x-text="firstError('plate_number')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع السيارة</label>
                    <input type="text" x-model="form.car_type" placeholder="مثال: متسوبيشي 2011 بكب غمارتين" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('car_type')" x-text="firstError('car_type')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">موديل</label>
                    <input type="text" dir="ltr" x-model="form.model_year" placeholder="2018" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('model_year')" x-text="firstError('model_year')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">المستلم السابق</label>
                    <input type="text" x-model="form.previous_receiver" placeholder="اسم المستلم أو بدون" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('previous_receiver')" x-text="firstError('previous_receiver')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الرقم الوظيفي</label>
                    <input type="text" dir="ltr" x-model="form.employee_id" placeholder="6649" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('employee_id')" x-text="firstError('employee_id')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الإدارة</label>
                    <input type="text" list="car-departments-list" x-model="form.department" placeholder="التنفيذ / المعدات / الإنتاج" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="car-departments-list">
                        @foreach ($options['departments'] as $department)
                            <option value="{{ $department }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('department')" x-text="firstError('department')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الوظيفة</label>
                    <input type="text" x-model="form.job_title" placeholder="مهندس موقع / مشرف صيانة" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('job_title')" x-text="firstError('job_title')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع الوقود</label>
                    <input type="text" list="car-fuel-list" x-model="form.fuel_type" placeholder="بنزين / ديزل" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="car-fuel-list">
                        <option value="بنزين"></option>
                        <option value="ديزل"></option>
                    </datalist>
                    <p x-show="firstError('fuel_type')" x-text="firstError('fuel_type')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الفرع</label>
                    <input type="text" list="car-branches-list" x-model="form.branch" placeholder="نجران / الرياض / المدينة" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="car-branches-list">
                        @foreach ($options['branches'] as $branch)
                            <option value="{{ $branch }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('branch')" x-text="firstError('branch')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">الحالة</label>
                <textarea rows="3" x-model="form.condition_status" placeholder="مثال: ماكينة + عفشة / فحص دوري..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                <p x-show="firstError('condition_status')" x-text="firstError('condition_status')" class="mt-1 text-xs text-rose-600"></p>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />
        <x-module.import-modal
            :action="route('car-breakdowns.import.direct')"
            :template-url="route('car-breakdowns.import.template')"
            title="استيراد وإسقاط بيانات السيارات المتعطلة"
        />
        <x-module.purge-modal
            :action="route('car-breakdowns.purge-all')"
            module-name="السيارات المتعطلة بالورش"
            :count="$kpis['total'] ?? 0"
        />
    </div>
@endsection

