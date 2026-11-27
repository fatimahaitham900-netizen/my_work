@extends('layouts.app')

@section('title', 'الحوادث ومطالبة التأمين')
@section('page_title', 'الحوادث ومطالبة التأمين')
@section('page_subtitle', 'متابعة سجل الحوادث ومطالبات التأمين وتقارير نجم ونسب المسؤولية والتعويضات')

@section('content')
    @php
        $currentTab = $activeTab ?? \App\Models\AccidentClaim::TYPE_ACCIDENT;
        $isAccidentsTab = $currentTab === \App\Models\AccidentClaim::TYPE_ACCIDENT;

        $moduleConfig = [
            'baseUrl' => url('/accident-claims'),
            'storeUrl' => route('accident-claims.store'),
            'exportBaseUrl' => route('accident-claims.export'),
            'initialExportUrl' => route('accident-claims.export', array_filter($filters)),
            'initialFilters' => [
                'record_type' => $currentTab,
                'search' => $filters['search'] ?? '',
                'branch' => $filters['branch'] ?? '',
                'insurance_company' => $filters['insurance_company'] ?? '',
                'claim_status' => $filters['claim_status'] ?? '',
            ],
            'defaultFilters' => [
                'record_type' => $currentTab,
                'search' => '',
                'branch' => '',
                'insurance_company' => '',
                'claim_status' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'record_type' => $currentTab,
                'vehicle_code' => '',
                'user_code' => '',
                'plate_number' => '',
                'accident_date' => now()->format('Y-m-d'),
                'accident_location' => '',
                'branch' => '',
                'record_holder' => '',
                'driver_name' => '',
                'employee_id' => '',
                'najm_report_number' => '',
                'claim_number' => '',
                'insurance_company' => '',
                'fault_percentage' => '0',
                'estimated_cost' => '0',
                'compensation_amount' => '0',
                'claim_status' => 'تحت الإجراء',
                'notes' => '',
            ],
        ];
    @endphp

    <div x-data="fleetModuleManager(@js($moduleConfig))" class="space-y-5">
        {{-- View Switcher Cards: الحوادث | مطالبات التأمين --}}
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <a
                href="{{ route('accident-claims.index', ['record_type' => \App\Models\AccidentClaim::TYPE_ACCIDENT]) }}"
                class="group flex items-center justify-between gap-4 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ $isAccidentsTab ? 'border-slate-950 bg-slate-950 text-white shadow-lg ring-4 ring-slate-950/15' : 'border-slate-300 bg-white text-slate-900 shadow-xs hover:border-slate-900 hover:bg-slate-50 hover:shadow-md' }}"
            >
                <div class="flex items-center gap-3.5 min-w-0">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $isAccidentsTab ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-900 group-hover:bg-slate-900 group-hover:text-white transition-colors' }}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-base sm:text-lg font-black leading-tight">الحوادث</p>
                        <p class="mt-1 text-xs font-bold {{ $isAccidentsTab ? 'text-slate-300' : 'text-slate-500' }}">كشف الحوادث (كود المستخدم، الفرع، السجل، الملاحظات)</p>
                    </div>
                </div>
                <div class="shrink-0 rounded-2xl px-4 py-2 text-center {{ $isAccidentsTab ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-950' }}">
                    <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format((int) ($kpis['accidents_count'] ?? 0)) }}</p>
                    <p class="mt-1 text-[11px] font-extrabold {{ $isAccidentsTab ? 'text-slate-200' : 'text-slate-500' }}">حادث</p>
                </div>
            </a>

            <a
                href="{{ route('accident-claims.index', ['record_type' => \App\Models\AccidentClaim::TYPE_INSURANCE_CLAIM]) }}"
                class="group flex items-center justify-between gap-4 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ ! $isAccidentsTab ? 'border-rose-800 bg-rose-700 text-white shadow-lg ring-4 ring-rose-700/15' : 'border-rose-300 bg-rose-50/40 text-slate-900 shadow-xs hover:border-rose-700 hover:bg-rose-50 hover:shadow-md' }}"
            >
                <div class="flex items-center gap-3.5 min-w-0">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ ! $isAccidentsTab ? 'bg-white/15 text-white' : 'bg-rose-100 text-rose-800 group-hover:bg-rose-700 group-hover:text-white transition-colors' }}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-base sm:text-lg font-black leading-tight">مطالبات التأمين</p>
                        <p class="mt-1 text-xs font-bold {{ ! $isAccidentsTab ? 'text-rose-100' : 'text-rose-900/75' }}">متابعة تقارير نجم، نسب المسؤولية، والتعويضات</p>
                    </div>
                </div>
                <div class="shrink-0 rounded-2xl px-4 py-2 text-center {{ ! $isAccidentsTab ? 'bg-white/15 text-white' : 'bg-rose-100 text-rose-900' }}">
                    <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format((int) ($kpis['claims_count'] ?? 0)) }}</p>
                    <p class="mt-1 text-[11px] font-extrabold {{ ! $isAccidentsTab ? 'text-rose-100' : 'text-rose-800' }}">مطالبة</p>
                </div>
            </a>
        </section>

        {{-- KPI Summary Strip (for Insurance Claims Tab) --}}
        @if (! $isAccidentsTab)
            <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl border-2 border-slate-900 bg-white p-4 text-center shadow-xs">
                    <p class="text-xs font-black text-slate-600">إجمالي مطالبات التأمين</p>
                    <p class="mt-1 text-2xl sm:text-3xl font-black text-slate-950 tabular-nums" x-text="kpis.claims_count">{{ $kpis['claims_count'] }}</p>
                </div>
                <div class="rounded-2xl border-2 border-amber-600 bg-amber-50/70 p-4 text-center shadow-xs">
                    <p class="text-xs font-black text-amber-900">مطالبات قيد الإجراء</p>
                    <p class="mt-1 text-2xl sm:text-3xl font-black text-amber-800 tabular-nums" x-text="kpis.open_claims">{{ $kpis['open_claims'] }}</p>
                </div>
                <div class="rounded-2xl border-2 border-rose-600 bg-rose-50/70 p-4 text-center shadow-xs">
                    <p class="text-xs font-black text-rose-800">إجمالي التكلفة التقديرية (ر.س)</p>
                    <p class="mt-1 text-2xl sm:text-3xl font-black text-rose-700 tabular-nums" x-text="kpis.total_cost">{{ $kpis['total_cost'] }}</p>
                </div>
                <div class="rounded-2xl border-2 border-emerald-700 bg-emerald-50/70 p-4 text-center shadow-xs">
                    <p class="text-xs font-black text-emerald-800">إجمالي التعويضات (ر.س)</p>
                    <p class="mt-1 text-2xl sm:text-3xl font-black text-emerald-700 tabular-nums" x-text="kpis.total_compensation">{{ $kpis['total_compensation'] }}</p>
                </div>
            </section>
        @endif

        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            :search-placeholder="$isAccidentsTab ? 'ابحث بكود المستخدم، الفرع، السجل، أو الملاحظات...' : 'ابحث بكود المركبة، اللوحة، السائق، رقم تقرير نجم، رقم المطالبة، شركة التأمين، أو الفرع...'"
            :create-model="\App\Models\AccidentClaim::class"
            :create-label="$isAccidentsTab ? 'إضافة حادث' : 'تسجيل مطالبة تأمين'"
            :show-import-purge="true"
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

            @if (! $isAccidentsTab)
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">شركة التأمين</label>
                    <select x-model="filters.insurance_company" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                        <option value="">جميع الشركات</option>
                        @foreach ($options['insurance_companies'] as $company)
                            <option value="{{ $company }}">{{ $company }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">حالة المطالبة</label>
                    <select x-model="filters.claim_status" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                        <option value="">جميع الحالات</option>
                        @foreach ($options['claim_statuses'] as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </x-module.toolbar>

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3.5 text-sm font-bold text-emerald-900">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->has('file'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3.5 text-sm font-bold text-rose-900">
                {{ $errors->first('file') }}
            </div>
        @endif

        {{-- Main Table Card --}}
        <section class="relative overflow-hidden rounded-2xl border-2 border-slate-900 bg-white shadow-sm">
            <div class="flex items-center gap-3 bg-slate-900 px-5 py-3.5 text-white">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 text-amber-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-black text-white">
                        {{ $isAccidentsTab ? 'جدول سجل الحوادث' : 'جدول سجل المطالبات التأمينية' }}
                    </h3>
                    <p class="text-[11px] font-medium text-slate-300">
                        {{ $isAccidentsTab ? 'متابعة الحوادث المسجلة حسب كود المستخدم والفرع والسجل والملاحظات' : 'متابعة الحوادث المرورية، تقارير نجم، ونسب المسؤولية ومطالبات التعويض' }}
                    </p>
                </div>
            </div>

            <div x-show="loading" x-cloak class="absolute inset-0 z-20 flex items-center justify-center bg-white/75 backdrop-blur-xs">
                <div class="flex items-center gap-2.5 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-lg">
                    <span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                    <span>جاري تحديث البيانات...</span>
                </div>
            </div>

            <div x-ref="tableContainer" @click="handleTableClick($event)">
                @include('modules.accident-claims._table', ['records' => $records, 'activeTab' => $currentTab])
            </div>
        </section>

        {{-- Create / Edit Drawer --}}
        <x-module.drawer
            :create-title="$isAccidentsTab ? 'إضافة سجل حادث' : 'تسجيل مطالبة تأمين'"
            :edit-title="$isAccidentsTab ? 'تعديل سجل الحادث' : 'تعديل مطالبة التأمين'"
        >
            <input type="hidden" x-model="form.record_type">

            @if ($isAccidentsTab)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">كود المستخدم</label>
                        <input type="text" x-model="form.user_code" dir="ltr" placeholder="مثال: C_000120" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">الفرع</label>
                        <input type="text" x-model="form.branch" placeholder="مثال: الرياض / مكة / نجران" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">السجل</label>
                        <input type="text" x-model="form.record_holder" placeholder="مثال: شركة المقاولات" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">ملاحظات</label>
                        <textarea x-model="form.notes" rows="3" placeholder="تفاصيل الحادث والملاحظات..." class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">كود السيارة / المعدة</label>
                        <input type="text" x-model="form.vehicle_code" dir="ltr" placeholder="مثال: C_000120" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">رقم اللوحة</label>
                        <input type="text" x-model="form.plate_number" placeholder="مثال: أ ب ج 4521" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">تاريخ الحادث</label>
                        <input type="date" x-model="form.accident_date" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">الفرع</label>
                        <input type="text" x-model="form.branch" placeholder="مثال: الرياض / مكة / نجران" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">موقع الحادث</label>
                        <input type="text" x-model="form.accident_location" placeholder="مثال: طريق الملك فهد - الرياض" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">اسم السائق</label>
                        <input type="text" x-model="form.driver_name" placeholder="اسم السائق" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">الرقم الوظيفي</label>
                        <input type="text" x-model="form.employee_id" dir="ltr" placeholder="مثال: 4512" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">رقم تقرير نجم</label>
                        <input type="text" x-model="form.najm_report_number" dir="ltr" placeholder="مثال: NJM-884120" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">رقم المطالبة</label>
                        <input type="text" x-model="form.claim_number" dir="ltr" placeholder="مثال: CLM-55210" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">شركة التأمين</label>
                        <input type="text" x-model="form.insurance_company" placeholder="مثال: التعاونية / ولاء / ميدغلف" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">نسبة المسؤولية (%)</label>
                        <input type="number" min="0" max="100" x-model="form.fault_percentage" placeholder="0" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">التكلفة التقديرية (ر.س)</label>
                        <input type="number" step="0.01" min="0" x-model="form.estimated_cost" placeholder="0" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">مبلغ التعويض (ر.س)</label>
                        <input type="number" step="0.01" min="0" x-model="form.compensation_amount" placeholder="0" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">حالة المطالبة</label>
                        <input type="text" list="claim-status-options" x-model="form.claim_status" placeholder="تحت الإجراء / مرفوعة للتأمين / تم التعويض / مغلقة" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                        <datalist id="claim-status-options">
                            <option value="تحت الإجراء"></option>
                            <option value="مرفوعة للتأمين"></option>
                            <option value="تم التعويض"></option>
                            <option value="مغلقة"></option>
                        </datalist>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">ملاحظات</label>
                        <textarea x-model="form.notes" rows="2" placeholder="أي ملاحظات إضافية حول الحادث أو المطالبة..." class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                    </div>
                </div>
            @endif
        </x-module.drawer>

        <x-module.import-modal
            :action="route('accident-claims.import.direct')"
            :template-url="route('accident-claims.import.template')"
            :title="$isAccidentsTab ? 'استيراد ملف الحوادث' : 'استيراد ملف مطالبات التأمين'"
            :hidden-fields="['record_type' => $currentTab]"
        />

        <x-module.purge-modal
            :action="route('accident-claims.purge-all')"
            :module-name="$isAccidentsTab ? 'الحوادث' : 'مطالبات التأمين'"
            :hidden-fields="['record_type' => $currentTab]"
        />
    </div>
@endsection
