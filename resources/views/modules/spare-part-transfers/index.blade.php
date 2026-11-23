@extends('layouts.app')

@section('title', 'نقل قطع غيار بدون تعميد الإدارة')
@section('page_title', 'نقل قطع غيار بدون تعميد الإدارة')
@section('page_subtitle', 'متابعة وتوثيق عمليات نقل وتدوير قطع الغيار بين المعدات، ورصد الحالات غير المحددة المصدر')

@section('content')
    @php
        $moduleConfig = [
            'baseUrl' => url('/spare-part-transfers'),
            'storeUrl' => route('spare-part-transfers.store'),
            'exportBaseUrl' => route('spare-part-transfers.export'),
            'initialExportUrl' => route('spare-part-transfers.export', array_filter($filters)),
            'initialFilters' => [
                'search' => $filters['search'] ?? '',
                'from_equipment' => $filters['from_equipment'] ?? '',
                'to_equipment' => $filters['to_equipment'] ?? '',
                'source_status' => $filters['source_status'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            'defaultFilters' => [
                'search' => '',
                'from_equipment' => '',
                'to_equipment' => '',
                'source_status' => '',
                'date_from' => '',
                'date_to' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'item_description' => '',
                'from_equipment' => '',
                'to_equipment' => '',
                'transfer_date' => now()->format('Y-m-d'),
                'notes' => '',
            ],
        ];
    @endphp

    <div x-data="fleetModuleManager(@js($moduleConfig))" class="space-y-5">
        {{-- KPI Mini-Header --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs">
                <p class="text-xs font-bold text-slate-600">إجمالي عمليات النقل</p>
                <p class="mt-1.5 text-2xl font-extrabold text-slate-900 tabular-nums" x-text="kpis.total">{{ $kpis['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200/90 bg-rose-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-rose-700">مصدر غير معروف (انحراف)</p>
                <p class="mt-1.5 text-2xl font-extrabold text-rose-800 tabular-nums" x-text="kpis.unknown_source">{{ $kpis['unknown_source'] }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200/90 bg-emerald-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-emerald-800">معدات مستفيدة</p>
                <p class="mt-1.5 text-2xl font-extrabold text-emerald-800 tabular-nums" x-text="kpis.unique_targets">{{ $kpis['unique_targets'] }}</p>
            </div>
            <div class="rounded-2xl border border-sky-200/90 bg-sky-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-sky-800">عمليات بملاحظات</p>
                <p class="mt-1.5 text-2xl font-extrabold text-sky-900 tabular-nums" x-text="kpis.with_notes">{{ $kpis['with_notes'] }}</p>
            </div>
        </section>

        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث بالبند، المعدة المنقول منها، المعدة المنقول إليها، أو الملاحظات..."
            :create-model="\App\Models\SparePartTransfer::class"
            create-label="إضافة عملية نقل جديدة"
            :show-import-purge="true"
        >
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">المعدة المنقول منها</label>
                <select x-model="filters.from_equipment" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">الكل</option>
                    @foreach ($options['from_equipments'] as $equipment)
                        <option value="{{ $equipment }}">{{ $equipment }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">المعدة المنقول إليها</label>
                <select x-model="filters.to_equipment" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">الكل</option>
                    @foreach ($options['to_equipments'] as $equipment)
                        <option value="{{ $equipment }}">{{ $equipment }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">حالة التوثيق</label>
                <select x-model="filters.source_status" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الحالات</option>
                    <option value="known">مصدر محدد</option>
                    <option value="unknown">مصدر غير معروف</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">من تاريخ</label>
                <input type="date" x-model="filters.date_from" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs text-slate-800 focus:border-slate-900 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">إلى تاريخ</label>
                <input type="date" x-model="filters.date_to" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs text-slate-800 focus:border-slate-900 focus:outline-none">
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
                @include('modules.spare-part-transfers._table', ['records' => $records])
            </div>
        </div>

        {{-- Slide-Over Create/Edit Drawer (No Required Fields) --}}
        <x-module.drawer
            create-title="إضافة عملية نقل قطعة غيار"
            edit-title="تعديل سجل نقل قطعة الغيار"
        >
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">البند</label>
                <textarea rows="3" x-model="form.item_description" placeholder="مثال: نقل طرمبة هيدروليك من كرين 14 الى كرين 6" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                <p x-show="firstError('item_description')" x-text="firstError('item_description')" class="mt-1 text-xs text-rose-600"></p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">المعدة المنقول منها</label>
                    <input type="text" list="spare-part-from-list" x-model="form.from_equipment" placeholder="مثال: كرين 14 (اتركه فارغاً إن لم تُعرف)" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="spare-part-from-list">
                        @foreach ($options['from_equipments'] as $equipment)
                            <option value="{{ $equipment }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('from_equipment')" x-text="firstError('from_equipment')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">المعدة المنقول إليها</label>
                    <input type="text" list="spare-part-to-list" x-model="form.to_equipment" placeholder="مثال: كرين 6" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="spare-part-to-list">
                        @foreach ($options['to_equipments'] as $equipment)
                            <option value="{{ $equipment }}"></option>
                        @endforeach
                    </datalist>
                    <p x-show="firstError('to_equipment')" x-text="firstError('to_equipment')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">التاريخ</label>
                <input type="date" x-model="form.transfer_date" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                <p x-show="firstError('transfer_date')" x-text="firstError('transfer_date')" class="mt-1 text-xs text-rose-600"></p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">ملاحظة</label>
                <textarea rows="3" x-model="form.notes" placeholder="أي ملاحظات إضافية..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                <p x-show="firstError('notes')" x-text="firstError('notes')" class="mt-1 text-xs text-rose-600"></p>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />
        <x-module.import-modal
            :action="route('spare-part-transfers.import.direct')"
            :template-url="route('spare-part-transfers.import.template')"
            title="استيراد وإسقاط بيانات نقل قطع الغيار"
        />
        <x-module.purge-modal
            :action="route('spare-part-transfers.purge-all')"
            module-name="نقل قطع غيار بدون تعميد الإدارة"
            :count="$kpis['total'] ?? 0"
        />
    </div>
@endsection

