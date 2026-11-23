@extends('layouts.app')

@section('title', 'انحراف الصيانات')
@section('page_title', 'انحراف الصيانات')
@section('page_subtitle', 'متابعة نسبة الصيانة اليومية والوقائية ونسب انحراف الصيانات اليومي والإجمالي')

@section('content')
    @php
        $moduleConfig = [
            'baseUrl' => url('/maintenance-deviations'),
            'storeUrl' => route('maintenance-deviations.store'),
            'exportBaseUrl' => route('maintenance-deviations.export'),
            'initialExportUrl' => route('maintenance-deviations.export', array_filter($filters)),
            'initialFilters' => [
                'search' => $filters['search'] ?? '',
                'row_type' => $filters['row_type'] ?? '',
            ],
            'defaultFilters' => [
                'search' => '',
                'row_type' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'day_number' => (string) (($records->max('day_number') ?? 17) + 1),
                'row_type' => 'active',
                'daily_maintenance_rate' => '99',
                'preventive_maintenance_rate' => '74',
                'daily_deviation_rate' => '1',
                'preventive_deviation_rate' => '26',
                'status_note' => '',
                'notes' => '',
            ],
        ];
    @endphp

    <div
        x-data="{
            ...fleetModuleManager(@js($moduleConfig)),
            syncDeviations() {
                if (this.form.row_type === 'active') {
                    const daily = parseFloat(this.form.daily_maintenance_rate);
                    if (!isNaN(daily)) {
                        this.form.daily_deviation_rate = String(Math.max(0, 100 - daily));
                    }
                    const prev = parseFloat(this.form.preventive_maintenance_rate);
                    if (!isNaN(prev)) {
                        this.form.preventive_deviation_rate = String(Math.max(0, 100 - prev));
                    }
                }
            }
        }"
        class="space-y-5"
    >
        {{-- KPI Mini-Header --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-emerald-200/90 bg-emerald-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-emerald-800">متوسط نسبة الصيانة اليومية</p>
                <p class="mt-1.5 text-2xl font-extrabold text-emerald-900 tabular-nums" x-text="kpis.daily_maintenance_rate">{{ $kpis['daily_maintenance_rate'] }}</p>
            </div>
            <div class="rounded-2xl border border-sky-200/90 bg-sky-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-sky-800">متوسط نسبة الصيانة الوقائية</p>
                <p class="mt-1.5 text-2xl font-extrabold text-sky-900 tabular-nums" x-text="kpis.preventive_maintenance_rate">{{ $kpis['preventive_maintenance_rate'] }}</p>
            </div>
            <div class="rounded-2xl border border-blue-200/90 bg-blue-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-blue-800">نسبة انحراف الصيانة العادية (اليومية)</p>
                <p class="mt-1.5 text-2xl font-extrabold text-blue-900 tabular-nums" x-text="kpis.daily_deviation_rate">{{ $kpis['daily_deviation_rate'] }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200/90 bg-rose-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-rose-700">نسبة انحراف الصيانة الوقائية</p>
                <p class="mt-1.5 text-2xl font-extrabold text-rose-800 tabular-nums" x-text="kpis.preventive_deviation_rate">{{ $kpis['preventive_deviation_rate'] }}</p>
            </div>
        </section>

        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث برقم اليوم أو الملاحظات..."
            :create-model="\App\Models\MaintenanceDeviation::class"
            create-label="إضافة يوم جديد"
            :show-import-purge="true"
        >
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">حالة اليوم</label>
                <select x-model="filters.row_type" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الأيام</option>
                    <option value="active">أيام العمل المسجّلة</option>
                    <option value="on_leave">مسؤول الصيانة مجاز</option>
                    <option value="day_off">أيام التوقف / الإجازة</option>
                </select>
            </div>
        </x-module.toolbar>

        {{-- Ruled Data Table --}}
        <div class="relative overflow-hidden rounded-2xl border-2 border-black bg-white shadow-sm">
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
                @include('modules.maintenance-deviations._table', [
                    'records' => $records,
                    'summary' => $summary,
                ])
            </div>
        </div>

        {{-- Slide-Over Create/Edit Drawer (No Required Fields) --}}
        <x-module.drawer
            create-title="إضافة سجل انحراف صيانات"
            edit-title="تعديل سجل انحراف الصيانات"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">التاريخ (رقم اليوم)</label>
                    <input type="number" min="1" max="366" x-model="form.day_number" placeholder="18" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-bold tabular-nums focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">حالة اليوم</label>
                    <select x-model="form.row_type" @change="if (form.row_type === 'on_leave' && !form.status_note) { form.status_note = 'مسؤول الصيانة مجاز'; }" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold focus:border-slate-900 focus:outline-none">
                        <option value="active">يوم عمل اعتيادي (نسب مئوية)</option>
                        <option value="on_leave">مسؤول الصيانة مجاز (صف رمادي)</option>
                        <option value="day_off">إجازة / بدون قراءة (صف أزرق)</option>
                    </select>
                </div>
            </div>

            <div x-show="form.row_type === 'active'" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">نسبة الصيانة اليومية (%)</label>
                        <input type="number" step="0.01" min="0" max="100" x-model="form.daily_maintenance_rate" @input="syncDeviations()" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-bold tabular-nums focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">نسبة الصيانة الوقائية (%)</label>
                        <input type="number" step="0.01" min="0" max="100" x-model="form.preventive_maintenance_rate" @input="syncDeviations()" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-bold tabular-nums focus:border-slate-900 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">نسبة انحراف الصيانة اليومية (%)</label>
                        <input type="number" step="0.01" min="0" max="100" x-model="form.daily_deviation_rate" class="block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm font-bold tabular-nums focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-rose-700 mb-1.5">نسبة انحراف الصيانة الوقائية (%)</label>
                        <input type="number" step="0.01" min="0" max="100" x-model="form.preventive_deviation_rate" class="block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm font-bold tabular-nums focus:border-slate-900 focus:outline-none">
                    </div>
                </div>
            </div>

            <div x-show="form.row_type !== 'active'">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">بيان الحالة في الجدول</label>
                <input type="text" x-model="form.status_note" placeholder="مسؤول الصيانة مجاز" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">ملاحظات إضافية</label>
                <textarea rows="2" x-model="form.notes" placeholder="ملاحظات اختيارية..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />
        <x-module.import-modal
            :action="route('maintenance-deviations.import.direct')"
            :template-url="route('maintenance-deviations.import.template')"
            title="استيراد وإسقاط بيانات انحراف الصيانات"
        />
        <x-module.purge-modal
            :action="route('maintenance-deviations.purge-all')"
            module-name="انحراف الصيانات"
            :count="$records->total()"
        />
    </div>
@endsection

