@extends('layouts.app')

@section('title', 'مخالفات وزارة النقل')
@section('page_title', 'مخالفات وزارة النقل')
@section('page_subtitle', 'سجل مخالفات الهيئة العامة للنقل، المبالغ المالية، حالات الاعتراضات والتظلمات، ومواقف التصاريح')

@section('content')
    @php
        $moduleConfig = [
            'baseUrl' => url('/transport-violations'),
            'storeUrl' => route('transport-violations.store'),
            'exportBaseUrl' => route('transport-violations.export'),
            'initialExportUrl' => route('transport-violations.export', array_filter($filters)),
            'initialFilters' => [
                'search' => $filters['search'] ?? '',
                'record_holder' => $filters['record_holder'] ?? '',
                'department' => $filters['department'] ?? '',
                'city' => $filters['city'] ?? '',
                'objection_status' => $filters['objection_status'] ?? '',
                'employee_status' => $filters['employee_status'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            'defaultFilters' => [
                'search' => '',
                'record_holder' => '',
                'department' => '',
                'city' => '',
                'objection_status' => '',
                'employee_status' => '',
                'date_from' => '',
                'date_to' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'violation_number' => '',
                'record_holder' => '',
                'violation_date' => now()->format('Y-m-d'),
                'amount' => '0',
                'department' => '',
                'city' => '',
                'violation_description' => '',
                'objection_status' => '',
                'plate_number' => '',
                'equipment_code' => '',
                'driver_name' => '',
                'employee_id' => '',
                'employee_status' => '',
                'liability' => '',
                'permit_status' => '',
            ],
        ];
    @endphp

    <div x-data="fleetModuleManager(@js($moduleConfig))" class="space-y-5">
        {{-- KPI Mini-Header --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs">
                <p class="text-xs font-bold text-slate-600">إجمالي المخالفات</p>
                <p class="mt-1.5 text-2xl font-extrabold text-slate-900 tabular-nums" x-text="kpis.total">{{ $kpis['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200/90 bg-rose-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-rose-700">إجمالي مبالغ المخالفات (ر.س)</p>
                <p class="mt-1.5 text-2xl font-extrabold text-rose-800 tabular-nums" x-text="kpis.total_amount">{{ $kpis['total_amount'] }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200/90 bg-amber-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-amber-800">اعتراضات قيد الانتظار</p>
                <p class="mt-1.5 text-2xl font-extrabold text-amber-800 tabular-nums" x-text="kpis.pending_objection">{{ $kpis['pending_objection'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-300/90 bg-slate-100/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-slate-700">مرفوضة / لم تُقدّم في موعدها</p>
                <p class="mt-1.5 text-2xl font-extrabold text-slate-900 tabular-nums" x-text="kpis.rejected_or_missed">{{ $kpis['rejected_or_missed'] }}</p>
            </div>
        </section>

        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث برقم المخالفة، السجل، الإدارة، المدينة، رقم اللوحة، الكود، السائق، أو الرقم الوظيفي..."
            :create-model="\App\Models\TransportViolation::class"
            create-label="إضافة مخالفة جديدة"
            :show-import-purge="true"
        >
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">السجل</label>
                <select x-model="filters.record_holder" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع السجلات</option>
                    @foreach ($options['record_holders'] as $holder)
                        <option value="{{ $holder }}">{{ $holder }}</option>
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
                <label class="block text-[11px] font-bold text-slate-600 mb-1">مدينة المخالفة</label>
                <select x-model="filters.city" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المدن</option>
                    @foreach ($options['cities'] as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">حالة الاعتراض</label>
                <select x-model="filters.objection_status" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع حالات الاعتراض</option>
                    @foreach ($options['objection_statuses'] as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">حالة الموظف</label>
                <select x-model="filters.employee_status" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الحالات</option>
                    @foreach ($options['employee_statuses'] as $empStatus)
                        <option value="{{ $empStatus }}">{{ $empStatus }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:col-span-2 lg:col-span-2">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">من تاريخ</label>
                    <input type="date" x-model="filters.date_from" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs text-slate-800 focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">إلى تاريخ</label>
                    <input type="date" x-model="filters.date_to" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs text-slate-800 focus:border-slate-900 focus:outline-none">
                </div>
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
                @include('modules.transport-violations._table', [
                    'records' => $records,
                    'filteredAmountTotal' => $filteredAmountTotal,
                ])
            </div>
        </div>

        {{-- Slide-Over Create/Edit Drawer (No Required Fields) --}}
        <x-module.drawer
            create-title="إضافة مخالفة وزارة نقل جديدة"
            edit-title="تعديل سجل مخالفة وزارة النقل"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">رقم المخالفة</label>
                    <input type="text" dir="ltr" x-model="form.violation_number" placeholder="10000021443674" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('violation_number')" x-text="firstError('violation_number')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">السجل</label>
                    <input type="text" list="violation-record-holders" x-model="form.record_holder" placeholder="المسار الحديث للمقاولات / الموظف" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="violation-record-holders">
                        <option value="المسار الحديث للمقاولات"></option>
                        <option value="المسار الحديث المحدودة"></option>
                        <option value="الموظف"></option>
                    </datalist>
                    <p x-show="firstError('record_holder')" x-text="firstError('record_holder')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">التاريخ</label>
                    <input type="date" x-model="form.violation_date" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('violation_date')" x-text="firstError('violation_date')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">المبلغ (ر.س)</label>
                    <input type="number" step="0.01" min="0" x-model="form.amount" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('amount')" x-text="firstError('amount')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الإدارة</label>
                    <input type="text" list="violation-departments" x-model="form.department" placeholder="المعدات / انتاج نجران / الانتاج" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="violation-departments">
                        @foreach ($options['departments'] as $department)
                            <option value="{{ $department }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('department')" x-text="firstError('department')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">مدينة المخالفة</label>
                    <input type="text" list="violation-cities" x-model="form.city" placeholder="نجران / الرياض / الحصينية" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="violation-cities">
                        @foreach ($options['cities'] as $city)
                            <option value="{{ $city }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('city')" x-text="firstError('city')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">شرح المخالفة</label>
                <textarea rows="2" x-model="form.violation_description" placeholder="مثال: تجاوز الوزن الإجمالي المسموح به..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                <p x-show="firstError('violation_description')" x-text="firstError('violation_description')" class="mt-1 text-xs text-rose-600"></p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">حالة الاعتراض</label>
                <input type="text" list="violation-objection-statuses" x-model="form.objection_status" placeholder="تم عمل اعتراض وفي انتظار النتيجه" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                <datalist id="violation-objection-statuses">
                    @foreach ($options['objection_statuses'] as $status)
                        <option value="{{ $status }}"></option>
                    @endforeach
                </datalist>
                <p x-show="firstError('objection_status')" x-text="firstError('objection_status')" class="mt-1 text-xs text-rose-600"></p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">رقم اللوحة</label>
                    <input type="text" x-model="form.plate_number" placeholder="أ ر و 9526" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('plate_number')" x-text="firstError('plate_number')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الكود</label>
                    <input type="text" dir="ltr" x-model="form.equipment_code" placeholder="TR-099" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('equipment_code')" x-text="firstError('equipment_code')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">السائق</label>
                    <input type="text" x-model="form.driver_name" placeholder="اسم السائق" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('driver_name')" x-text="firstError('driver_name')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الرقم الوظيفي</label>
                    <input type="text" dir="ltr" x-model="form.employee_id" placeholder="4480" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('employee_id')" x-text="firstError('employee_id')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">حالة الموظف</label>
                    <input type="text" list="violation-employee-statuses" x-model="form.employee_status" placeholder="متواجد على رأس العمل / خروج نهائي" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="violation-employee-statuses">
                        <option value="متواجد على رأس العمل"></option>
                        <option value="خروج نهائي"></option>
                        <option value="مجاز"></option>
                        <option value="المصنع"></option>
                        <option value="الكهرباء"></option>
                    </datalist>
                    <p x-show="firstError('employee_status')" x-text="firstError('employee_status')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">التحميل</label>
                    <input type="text" list="violation-liabilities" x-model="form.liability" placeholder="الشركة / الانتاج / المصنع" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="violation-liabilities">
                        <option value="الشركة"></option>
                        <option value="الانتاج"></option>
                        <option value="المصنع"></option>
                        <option value="الكهرباء"></option>
                        <option value="فى انتظار النتيجة"></option>
                    </datalist>
                    <p x-show="firstError('liability')" x-text="firstError('liability')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">موقف التصريح</label>
                <input type="text" list="violation-permit-statuses" x-model="form.permit_status" placeholder="يوجد تصريح / لا يوجد" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                <datalist id="violation-permit-statuses">
                    <option value="يوجد تصريح"></option>
                    <option value="لا يوجد"></option>
                    <option value="لا يوجد حيث الوزن لا يستدعي عمل تصريح"></option>
                </datalist>
                <p x-show="firstError('permit_status')" x-text="firstError('permit_status')" class="mt-1 text-xs text-rose-600"></p>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />
        <x-module.import-modal
            :action="route('transport-violations.import.direct')"
            :template-url="route('transport-violations.import.template')"
            title="استيراد وإسقاط بيانات مخالفات وزارة النقل"
        />
        <x-module.purge-modal
            :action="route('transport-violations.purge-all')"
            module-name="مخالفات وزارة النقل"
            :count="$kpis['total'] ?? 0"
        />
    </div>
@endsection

