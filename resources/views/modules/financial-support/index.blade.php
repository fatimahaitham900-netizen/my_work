@extends('layouts.app')

@section('title', 'الدعم المالي')
@section('page_title', 'الدعم المالي')
@section('page_subtitle', 'كشوفات التحويلات الشهرية لدعم إصلاحات المعدات، العهد، وموقف الإصلاح والتوريد')

@section('content')
    @php
        $currentMonthSummary = collect($monthTabs)->firstWhere('month', (int) $activeMonth);
        $defaultMonthValue = $activeMonth === 'all' ? '11' : (string) $activeMonth;

        $moduleConfig = [
            'baseUrl' => url('/financial-support'),
            'storeUrl' => route('financial-support.store'),
            'exportBaseUrl' => route('financial-support.export'),
            'initialExportUrl' => route('financial-support.export', array_filter($filters)),
            'initialFilters' => [
                'month' => $activeMonth,
                'search' => $filters['search'] ?? '',
                'branch' => $filters['branch'] ?? '',
                'transfer_stage' => $filters['transfer_stage'] ?? '',
                'repair_status' => $filters['repair_status'] ?? '',
            ],
            'defaultFilters' => [
                'month' => $activeMonth,
                'search' => '',
                'branch' => '',
                'transfer_stage' => '',
                'repair_status' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'support_year' => '2026',
                'support_month' => $defaultMonthValue,
                'equipment_type' => '',
                'equipment_code' => '',
                'amount' => '0',
                'beneficiary_name' => '',
                'fault_type' => '',
                'branch' => '',
                'case_status' => 'استهلاك طبيعي',
                'po_number' => '',
                'transfer_stage' => 'تم التحويل',
                'transfer_date' => '',
                'supply_date' => '',
                'repair_date' => '',
                'repair_status' => 'تم الاصلاح',
            ],
        ];
    @endphp

    <div x-data="fleetModuleManager(@js($moduleConfig))" class="space-y-5">
        {{-- Monthly Sub-Pages Navigation Cards (صفحات الشهور) --}}
        <section class="space-y-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($monthTabs as $tab)
                    @php $isActive = (string) $tab['month'] === (string) $activeMonth; @endphp
                    <a
                        href="{{ route('financial-support.index', ['month' => $tab['month']]) }}"
                        class="group flex items-center justify-between gap-3 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ $isActive ? 'border-slate-950 bg-slate-950 text-white shadow-lg ring-4 ring-slate-950/15' : 'border-slate-300 bg-white text-slate-900 shadow-xs hover:border-slate-900 hover:bg-slate-50 hover:shadow-md' }}"
                    >
                        <div class="min-w-0">
                            <p class="text-base sm:text-lg font-black leading-tight">{{ $tab['title'] }} / {{ $tab['year'] }}</p>
                            <p class="mt-1 text-xs font-bold {{ $isActive ? 'text-slate-300' : 'text-slate-500' }}">كشف الدعم المالي للشهر</p>
                        </div>
                        <div class="shrink-0 rounded-2xl px-3.5 py-2 text-center {{ $isActive ? 'bg-emerald-500/25 text-emerald-100' : 'bg-slate-100 text-slate-950' }}">
                            <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format((int) $tab['transfers_count']) }}</p>
                            <p class="mt-1 text-[11px] font-extrabold {{ $isActive ? 'text-emerald-200' : 'text-slate-500' }}">تحويل</p>
                        </div>
                    </a>
                @endforeach

                <a
                    href="{{ route('financial-support.index', ['month' => 'all']) }}"
                    class="group flex items-center justify-between gap-3 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ $activeMonth === 'all' ? 'border-emerald-800 bg-emerald-700 text-white shadow-lg ring-4 ring-emerald-700/15' : 'border-emerald-300 bg-emerald-50/40 text-slate-900 shadow-xs hover:border-emerald-700 hover:bg-emerald-50 hover:shadow-md' }}"
                >
                    <div class="min-w-0">
                        <p class="text-base sm:text-lg font-black leading-tight">جميع الشهور</p>
                        <p class="mt-1 text-xs font-bold {{ $activeMonth === 'all' ? 'text-emerald-100' : 'text-emerald-900/75' }}">كشف شامل لجميع التحويلات</p>
                    </div>
                    <div class="shrink-0 rounded-2xl px-3.5 py-2 text-center {{ $activeMonth === 'all' ? 'bg-white/15 text-white' : 'bg-emerald-100 text-emerald-900' }}">
                        <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format(array_sum(array_column($monthTabs, 'transfers_count'))) }}</p>
                        <p class="mt-1 text-[11px] font-extrabold {{ $activeMonth === 'all' ? 'text-emerald-100' : 'text-emerald-800' }}">تحويل</p>
                    </div>
                </a>
            </div>

            {{-- Active Month Executive Summary Strip (Matches Dashboard Table) --}}
            @if ($currentMonthSummary)
                <div
                    x-data="{ editingSummary: false }"
                    class="rounded-xl border-2 border-slate-800 bg-slate-50/70 overflow-hidden"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-800 px-4 py-2 text-white">
                        <span class="text-xs sm:text-sm font-extrabold">
                            ملخص موقف الدعم المالي — {{ $currentMonthSummary['title'] }} / {{ $currentMonthSummary['year'] }}
                        </span>

                        @can('update', \App\Models\FinancialSupportMonth::find($currentMonthSummary['id']))
                            <button
                                type="button"
                                @click="editingSummary = !editingSummary"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1 text-xs font-bold text-white hover:bg-white/25 transition cursor-pointer"
                            >
                                <span x-text="editingSummary ? 'إغلاق التعديل' : 'تعديل ملخص الشهر / العهد'"></span>
                            </button>
                        @endcan
                    </div>

                    <div class="grid grid-cols-2 divide-y divide-slate-300 sm:grid-cols-4 lg:grid-cols-7 sm:divide-y-0 text-center bg-white">
                        <div class="p-3 border-e border-slate-300">
                            <p class="text-[11px] font-extrabold text-slate-600">دعم إصلاحات معدات</p>
                            <p class="mt-1 text-lg font-black text-blue-600 tabular-nums">{{ number_format($currentMonthSummary['repair_support_amount'], 0) }}</p>
                        </div>
                        <div class="p-3 border-e border-slate-300">
                            <p class="text-[11px] font-extrabold text-slate-600">عهد</p>
                            <p class="mt-1 text-lg font-black text-slate-950 tabular-nums">{{ number_format($currentMonthSummary['custody_amount'], 0) }}</p>
                        </div>
                        <div class="p-3 border-e border-slate-300 bg-sky-50/50">
                            <p class="text-[11px] font-extrabold text-slate-600">الإجمالي</p>
                            <p class="mt-1 text-lg font-black text-emerald-600 tabular-nums">{{ number_format($currentMonthSummary['total_amount'], 0) }}</p>
                        </div>
                        <button
                            type="button"
                            @click="filters.repair_status = ''; filters.transfer_stage = 'لم يتم التحويل'; applyFilters()"
                            class="p-3 border-e border-slate-300 hover:bg-amber-50/70 transition-colors cursor-pointer"
                            title="تصفية البنود التي لم يتم تحويلها"
                        >
                            <p class="text-[11px] font-extrabold text-amber-700">لم يتم التحويل</p>
                            <p class="mt-1 text-lg font-black text-amber-600 tabular-nums">{{ $currentMonthSummary['not_transferred_amount'] ? number_format($currentMonthSummary['not_transferred_amount'], 0) : '—' }}</p>
                        </button>
                        <div class="p-3 border-e border-slate-300">
                            <p class="text-[11px] font-extrabold text-slate-600">إجمالي المعدات</p>
                            <p class="mt-1 text-lg font-black text-slate-950 tabular-nums">{{ $currentMonthSummary['total_equipment_count'] }}</p>
                        </div>
                        <div class="p-3 border-e border-slate-300">
                            <p class="text-[11px] font-extrabold text-slate-600">تم الإصلاح</p>
                            <p class="mt-1 text-lg font-black text-emerald-600 tabular-nums">{{ $currentMonthSummary['repaired_count'] }}</p>
                        </div>
                        <button
                            type="button"
                            @click="filters.transfer_stage = ''; filters.repair_status = 'لم يتم الاصلاح'; applyFilters()"
                            class="p-3 hover:bg-rose-50/70 transition-colors cursor-pointer"
                            title="تصفية السجلات المتبقية تحت الإصلاح"
                        >
                            <p class="text-[11px] font-extrabold text-rose-700">متبقي تحت الإصلاح</p>
                            <p class="mt-1 text-lg font-black text-rose-600 tabular-nums">{{ (int) $currentMonthSummary['under_repair_count'] > 0 ? $currentMonthSummary['under_repair_count'] : '—' }}</p>
                        </button>
                    </div>

                    {{-- Inline Month Summary Editor (Zero Required Fields) --}}
                    <form
                        x-show="editingSummary"
                        x-cloak
                        method="POST"
                        action="{{ route('financial-support.months.update', $currentMonthSummary['id']) }}"
                        class="border-t-2 border-slate-800 bg-slate-50 p-4 space-y-3"
                    >
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">دعم إصلاحات معدات</label>
                                <input type="number" step="0.01" name="repair_support_amount" value="{{ $currentMonthSummary['repair_support_amount'] }}" class="block w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold tabular-nums focus:border-slate-900 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">عهد</label>
                                <input type="number" step="0.01" name="custody_amount" value="{{ $currentMonthSummary['custody_amount'] }}" class="block w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold tabular-nums focus:border-slate-900 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">لم يتم التحويل</label>
                                <input type="number" step="0.01" name="not_transferred_amount" value="{{ $currentMonthSummary['not_transferred_amount'] }}" class="block w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold tabular-nums focus:border-slate-900 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">إجمالي المعدات</label>
                                <input type="number" name="total_equipment_count" value="{{ $currentMonthSummary['total_equipment_count'] }}" class="block w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold tabular-nums focus:border-slate-900 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">تم الإصلاح</label>
                                <input type="number" name="repaired_count" value="{{ $currentMonthSummary['repaired_count'] }}" class="block w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold tabular-nums focus:border-slate-900 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-rose-700 mb-1">متبقي تحت الإصلاح</label>
                                <input type="number" name="under_repair_count" value="{{ $currentMonthSummary['under_repair_count'] }}" class="block w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold tabular-nums focus:border-slate-900 focus:outline-none">
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-extrabold text-white hover:bg-black transition cursor-pointer">
                                حفظ ملخص الشهر
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </section>

        {{-- KPI Mini-Header --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs">
                <p class="text-xs font-bold text-slate-600">إجمالي بنود الكشف</p>
                <p class="mt-1.5 text-2xl font-extrabold text-slate-900 tabular-nums" x-text="kpis.total">{{ $kpis['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200/90 bg-emerald-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-emerald-800">إجمالي المبالغ (ر.س)</p>
                <p class="mt-1.5 text-2xl font-extrabold text-emerald-900 tabular-nums" x-text="kpis.total_amount">{{ $kpis['total_amount'] }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200/90 bg-rose-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-rose-700">لم يتم الإصلاح / متعطل</p>
                <p class="mt-1.5 text-2xl font-extrabold text-rose-800 tabular-nums" x-text="kpis.under_repair">{{ $kpis['under_repair'] }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200/90 bg-amber-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-amber-800">لم يتم التحويل</p>
                <p class="mt-1.5 text-2xl font-extrabold text-amber-900 tabular-nums" x-text="kpis.not_transferred">{{ $kpis['not_transferred'] }}</p>
            </div>
        </section>

        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث بنوع المعدة، الكود، اسم المستفيد، نوع العطل، الفرع، أو رقم الـ PO..."
            :create-model="\App\Models\FinancialSupportTransfer::class"
            create-label="إضافة تحويل جديد"
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

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">المرحلة</label>
                <select x-model="filters.transfer_stage" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المراحل</option>
                    @foreach ($options['transfer_stages'] as $stage)
                        <option value="{{ $stage }}">{{ $stage }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">موقف الإصلاح</label>
                <select x-model="filters.repair_status" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المواقف</option>
                    @foreach ($options['repair_statuses'] as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
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
                @include('modules.financial-support._table', [
                    'records' => $records,
                    'filteredAmountTotal' => $filteredAmountTotal,
                    'activeMonth' => $activeMonth,
                ])
            </div>
        </div>

        {{-- Slide-Over Create/Edit Drawer (No Required Fields) --}}
        <x-module.drawer
            create-title="إضافة بند دعم مالي جديد"
            edit-title="تعديل سجل الدعم المالي"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الشهر</label>
                    <select x-model="form.support_month" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                        <option value="8">شهر 8 / 2026</option>
                        <option value="9">شهر 9 / 2026</option>
                        <option value="10">شهر 10 / 2026</option>
                        <option value="11">شهر 11 / 2026</option>
                        <option value="12">شهر 12 / 2026</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">المبلغ (ر.س)</label>
                    <input type="number" step="0.01" min="0" x-model="form.amount" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('amount')" x-text="firstError('amount')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع المعدة</label>
                    <input type="text" x-model="form.equipment_type" placeholder="شيول / حفار / مولد / قلاب" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الكود</label>
                    <input type="text" dir="ltr" x-model="form.equipment_code" placeholder="WL-003" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">اسم المستفيد</label>
                    <input type="text" x-model="form.beneficiary_name" placeholder="اتحاد الخير / عبدالقوي محمد" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الفرع</label>
                    <input type="text" list="support-branches-list" x-model="form.branch" placeholder="نجران / الجنادرية / مكة المكرمة" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="support-branches-list">
                        @foreach ($options['branches'] as $branch)
                            <option value="{{ $branch }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع العطل</label>
                <textarea rows="2" x-model="form.fault_type" placeholder="تفاصيل الإصلاح أو قطع الغيار..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الحالة</label>
                    <input type="text" x-model="form.case_status" placeholder="استهلاك طبيعي" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">رقم الـ PO</label>
                    <input type="text" dir="ltr" x-model="form.po_number" placeholder="PO-0005542" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">المرحلة</label>
                    <input type="text" list="support-stages-list" x-model="form.transfer_stage" placeholder="تم التحويل / دعم مالي / لم يتم التحويل" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="support-stages-list">
                        <option value="تم التحويل"></option>
                        <option value="دعم مالي"></option>
                        <option value="لم يتم التحويل"></option>
                    </datalist>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تاريخ التحويل</label>
                    <input type="text" x-model="form.transfer_date" placeholder="08/09/2026" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تاريخ التوريد</label>
                    <input type="text" x-model="form.supply_date" placeholder="15-08-2026 أو مديونية" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تاريخ الإصلاح</label>
                    <input type="text" x-model="form.repair_date" placeholder="18-08-2026 أو مديونية" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">موقف الإصلاح</label>
                <input type="text" list="support-repair-statuses" x-model="form.repair_status" placeholder="تم الاصلاح / لم يتم الاصلاح / STOK" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                <datalist id="support-repair-statuses">
                    <option value="تم الاصلاح"></option>
                    <option value="لم يتم الاصلاح"></option>
                    <option value="STOK"></option>
                </datalist>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />
        <x-module.import-modal
            :action="route('financial-support.import.direct')"
            :template-url="route('financial-support.import.template')"
            title="استيراد وإسقاط بيانات الدعم المالي"
            :hidden-fields="['month' => ($activeMonth ?? 'all') !== 'all' ? $activeMonth : '']"
        />
        <x-module.purge-modal
            :action="route('financial-support.purge-all')"
            :module-name="($activeMonth ?? 'all') !== 'all' ? 'الدعم المالي (شهر '.$activeMonth.')' : 'الدعم المالي'"
            :count="$kpis['total'] ?? 0"
            :hidden-fields="['month' => ($activeMonth ?? 'all') !== 'all' ? $activeMonth : '']"
        />
    </div>
@endsection

