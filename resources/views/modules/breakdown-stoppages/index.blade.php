@extends('layouts.app')

@section('title', 'سائقين متوقفين على أعطال المعدات')
@section('page_title', 'سائقين متوقفين على أعطال المعدات')
@section('page_subtitle', 'متابعة السائقين المتوقفين، آخر تشغيل، أيام التوقف، تشخيص العطل، وموقف الورش')

@section('content')
    @php
        $activeBreakdownsMap = $activeBreakdowns->mapWithKeys(static fn ($b): array => [
            (string) $b->id => $b->equipment_code,
        ]);

        $moduleConfig = [
            'baseUrl' => url('/breakdown-stoppages'),
            'storeUrl' => route('breakdown-stoppages.store'),
            'exportBaseUrl' => route('breakdown-stoppages.export'),
            'initialExportUrl' => route('breakdown-stoppages.export', array_filter($filters)),
            'initialFilters' => [
                'search' => $filters['search'] ?? '',
                'branch' => $filters['branch'] ?? '',
                'workshop_status' => $filters['workshop_status'] ?? '',
                'status' => $filters['status'] ?? '',
                'idle_threshold' => $filters['idle_threshold'] ?? '',
                'focus' => $filters['focus'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            'defaultFilters' => [
                'search' => '',
                'branch' => '',
                'workshop_status' => '',
                'status' => '',
                'idle_threshold' => '',
                'focus' => '',
                'date_from' => '',
                'date_to' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'driver_name' => '',
                'employee_id' => '',
                'job_title' => '',
                'branch' => '',
                'phone' => '',
                'equipment_code' => '',
                'equipment_type' => '',
                'breakdown_id' => '',
                'stoppage_start_date' => now()->format('Y-m-d'),
                'expected_resume_date' => '',
                'fault_diagnosis' => '',
                'workshop_status' => 'قيد الاصلاح',
                'action_taken' => '',
                'status' => 'waiting',
                'notes' => '',
            ],
        ];
    @endphp

    <div
        x-data="{
            ...fleetModuleManager(@js($moduleConfig)),
            breakdownsMap: @js($activeBreakdownsMap),
            onBreakdownSelected() {
                const code = this.breakdownsMap[this.form.breakdown_id];
                if (code && !this.form.equipment_code) {
                    this.form.equipment_code = code;
                }
            }
        }"
        class="space-y-5"
    >
        {{-- KPI Mini-Header --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium text-slate-500">إجمالي السجلات</p>
                <p class="mt-1.5 text-2xl font-bold text-slate-900 tabular-nums" x-text="kpis.total">{{ $kpis['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200/90 bg-amber-50/60 p-4 shadow-xs">
                <p class="text-xs font-medium text-amber-800">بانتظار الإصلاح</p>
                <p class="mt-1.5 text-2xl font-bold text-amber-800 tabular-nums" x-text="kpis.waiting">{{ $kpis['waiting'] }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200/90 bg-rose-50/60 p-4 shadow-xs">
                <p class="text-xs font-medium text-rose-700">توقف حرج (&gt; 3 أيام)</p>
                <p class="mt-1.5 text-2xl font-bold text-rose-700 tabular-nums" x-text="kpis.critical_over_3">{{ $kpis['critical_over_3'] }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200/90 bg-emerald-50/60 p-4 shadow-xs">
                <p class="text-xs font-medium text-emerald-800">تم الحل / إعادة التوجيه</p>
                <p class="mt-1.5 text-2xl font-bold text-emerald-800 tabular-nums" x-text="kpis.resolved">{{ $kpis['resolved'] }}</p>
            </div>
        </section>

        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث باسم السائق، الرقم الوظيفي، الفرع، كود المعدة، أو تشخيص العطل..."
            :create-model="\App\Models\BreakdownDriverStoppage::class"
        >
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">الفرع</label>
                <select x-model="filters.branch" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الفروع</option>
                    @foreach ($branches as $branchName)
                        <option value="{{ $branchName }}">{{ $branchName }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">موقف الورش</label>
                <select x-model="filters.workshop_status" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المواقف</option>
                    @foreach ($workshopStatuses as $ws)
                        <option value="{{ $ws }}">{{ $ws }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">حالة المتابعة</label>
                <select x-model="filters.status" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الحالات</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">مدة التوقف</label>
                <select x-model="filters.idle_threshold" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">الكل</option>
                    <option value="over_3">أكثر من 3 أيام (تنبيه)</option>
                    <option value="over_7">7 أيام فأكثر (حرج)</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">آخر تشغيل (من)</label>
                    <input type="date" x-model="filters.date_from" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs text-slate-800 focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">آخر تشغيل (إلى)</label>
                    <input type="date" x-model="filters.date_to" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs text-slate-800 focus:border-slate-900 focus:outline-none">
                </div>
            </div>
        </x-module.toolbar>

        {{-- Ruled Data Table --}}
        <div class="relative overflow-hidden rounded-2xl border border-slate-300/80 bg-white shadow-sm">
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
                @include('modules.breakdown-stoppages._table', ['records' => $records])
            </div>
        </div>

        {{-- Slide-Over Create/Edit Drawer --}}
        <x-module.drawer
            create-title="إضافة سائق متوقف على عطل"
            edit-title="تعديل سجل توقف السائق"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">اسم السائق</label>
                    <input type="text" x-model="form.driver_name" placeholder="مثال: احمد عبدالكريم قاسم" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('driver_name')" x-text="firstError('driver_name')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">الرقم الوظيفي</label>
                    <input type="text" dir="ltr" x-model="form.employee_id" placeholder="مثال: 3246" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('employee_id')" x-text="firstError('employee_id')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">المهنة</label>
                    <input type="text" x-model="form.job_title" placeholder="مثال: سائق قلاب / سائق حفار" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('job_title')" x-text="firstError('job_title')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">الفرع</label>
                    <input type="text" list="stoppage-branch-presets" x-model="form.branch" placeholder="مثال: مكة / نجران / المدينة" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="stoppage-branch-presets">
                        <option value="مكة"></option>
                        <option value="نجران"></option>
                        <option value="المدينة"></option>
                        <option value="احدرفيده"></option>
                    </datalist>
                    <p x-show="firstError('branch')" x-text="firstError('branch')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">كود المعدة</label>
                    <input type="text" dir="ltr" x-model="form.equipment_code" placeholder="مثال: DT_000020" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('equipment_code')" x-text="firstError('equipment_code')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">نوع المعدة</label>
                    <input type="text" x-model="form.equipment_type" placeholder="مثال: قلاب / بلدوزر / حفار" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('equipment_type')" x-text="firstError('equipment_type')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">تاريخ آخر تشغيل</label>
                    <input type="date" x-model="form.stoppage_start_date" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('stoppage_start_date')" x-text="firstError('stoppage_start_date')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">موقف الورش</label>
                    <input type="text" list="workshop-status-presets" x-model="form.workshop_status" placeholder="مثال: توريد قطع / قيد الاصلاح" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="workshop-status-presets">
                        <option value="توريد قطع"></option>
                        <option value="قيد الاصلاح"></option>
                        <option value="جاري الاصلاح"></option>
                        <option value="تم الاصلاح"></option>
                        <option value="متوقف تسعير"></option>
                        <option value="دعم مالي"></option>
                        <option value="نقل للورشه المركزيه"></option>
                        <option value="جاري النقل من الموقع الى الورشة"></option>
                    </datalist>
                    <p x-show="firstError('workshop_status')" x-text="firstError('workshop_status')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">تشخيص العطل</label>
                <textarea rows="3" x-model="form.fault_diagnosis" placeholder="اكتب تشخيص العطل الفني بالتفصيل..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                <p x-show="firstError('fault_diagnosis')" x-text="firstError('fault_diagnosis')" class="mt-1 text-xs text-rose-600"></p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">الإجراء المتخذ</label>
                <textarea rows="2" x-model="form.action_taken" placeholder="الإجراء الإداري أو التشغيلي المتخذ حيال السائق / المعدة..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                <p x-show="firstError('action_taken')" x-text="firstError('action_taken')" class="mt-1 text-xs text-rose-600"></p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">حالة المتابعة</label>
                    <select x-model="form.status" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <p x-show="firstError('status')" x-text="firstError('status')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">رقم الجوال (اختياري)</label>
                    <input type="text" x-model="form.phone" placeholder="05XXXXXXXX" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('phone')" x-text="firstError('phone')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">ربط بسجل عطل نشط (اختياري)</label>
                <select
                    x-model="form.breakdown_id"
                    @change="onBreakdownSelected()"
                    class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"
                >
                    <option value="">— بدون ربط مباشر بعطل —</option>
                    @foreach ($activeBreakdowns as $breakdown)
                        <option value="{{ $breakdown->id }}">
                            {{ $breakdown->equipment_code }} — {{ $breakdown->category->label() }} ({{ $breakdown->severity->label() }} • {{ $breakdown->breakdown_date?->format('Y-m-d') }})
                        </option>
                    @endforeach
                </select>
                <p x-show="firstError('breakdown_id')" x-text="firstError('breakdown_id')" class="mt-1 text-xs text-rose-600"></p>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />
    </div>
@endsection
