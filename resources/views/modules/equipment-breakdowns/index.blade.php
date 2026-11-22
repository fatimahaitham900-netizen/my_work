@extends('layouts.app')

@section('title', 'معدات متعطلة بالورش')
@section('page_title', 'معدات متعطلة بالورش')
@section('page_subtitle', 'سجل أعطال الورش التفصيلي، أحجام الأعطال، المواقف، المبالغ التقديرية، ومواعيد الإصلاح')

@section('content')
    @php
        $moduleConfig = [
            'baseUrl' => url('/equipment-breakdowns'),
            'storeUrl' => route('equipment-breakdowns.store'),
            'exportBaseUrl' => route('equipment-breakdowns.export'),
            'initialExportUrl' => route('equipment-breakdowns.export', array_filter($filters)),
            'initialFilters' => [
                'search' => $filters['search'] ?? '',
                'workshop_name' => $filters['workshop_name'] ?? '',
                'workshop_status' => $filters['workshop_status'] ?? '',
                'equipment_name' => $filters['equipment_name'] ?? '',
                'brand' => $filters['brand'] ?? '',
                'severity' => $filters['severity'] ?? '',
                'spare_parts_delay' => $filters['spare_parts_delay'] ?? '',
                'category' => $filters['category'] ?? '',
                'repair_status' => $filters['repair_status'] ?? '',
                'focus' => $filters['focus'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            'defaultFilters' => [
                'search' => '',
                'workshop_name' => '',
                'workshop_status' => '',
                'equipment_name' => '',
                'brand' => '',
                'severity' => '',
                'spare_parts_delay' => '',
                'category' => '',
                'repair_status' => '',
                'focus' => '',
                'date_from' => '',
                'date_to' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'equipment_code' => '',
                'equipment_name' => '',
                'brand' => '',
                'workshop_name' => '',
                'category' => 'mechanical',
                'severity' => 'medium',
                'breakdown_date' => now()->format('Y-m-d'),
                'workshop_entry_date' => '',
                'expected_repair_date' => '',
                'fault_description' => '',
                'repair_status' => 'in_workshop',
                'workshop_status' => 'جاري الكشف',
                'spare_parts_delay' => false,
                'estimated_cost' => '0',
                'cost_note' => '',
                'notes' => '',
                'waiting_stoppages_count' => 0,
                'linked_waiting_stoppages' => [],
                'resolve_linked_stoppages' => false,
                'linked_stoppage_status' => 'resolved',
            ],
        ];
    @endphp

    <div
        x-data="{
            ...fleetModuleManager(@js($moduleConfig)),
            viewMode: @js($filters['view_mode'] ?? 'list'),
            drillDownByBrand(brand, workshop = '') {
                this.filters.brand = brand;
                this.filters.equipment_name = '';
                this.filters.workshop_name = workshop;
                this.viewMode = 'list';
                this.applyFilters();
            },
            drillDownByEquipmentName(equipmentName, workshop = '') {
                this.filters.equipment_name = equipmentName;
                this.filters.brand = '';
                this.filters.workshop_name = workshop;
                this.viewMode = 'list';
                this.applyFilters();
            }
        }"
        class="space-y-5"
    >
        {{-- View Mode Switcher Bar (طريقة العرض) --}}
        <section class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900">طريقة العرض</h2>
                    <p class="text-xs text-slate-500 mt-0.5">بدّل بين الجدول التفصيلي، أو ملخص الورش والأعداد حسب النوع، أو حسب نوع المعدة</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="viewMode = 'list'"
                        :class="viewMode === 'list' ? 'border-slate-900 bg-slate-900 text-white shadow-sm' : 'border-slate-200 bg-slate-50 text-slate-800 hover:border-slate-400 hover:bg-white'"
                        class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs sm:text-sm font-extrabold transition cursor-pointer"
                    >
                        <span>العرض التفصيلي</span>
                        <span class="rounded-md px-1.5 py-0.5 text-[11px] tabular-nums" :class="viewMode === 'list' ? 'bg-rose-500/30 text-rose-100' : 'bg-slate-200/80 text-slate-700'">
                            {{ $kpis['total'] }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="viewMode = 'by_brand'"
                        :class="viewMode === 'by_brand' ? 'border-slate-900 bg-slate-900 text-white shadow-sm' : 'border-slate-200 bg-slate-50 text-slate-800 hover:border-slate-400 hover:bg-white'"
                        class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs sm:text-sm font-extrabold transition cursor-pointer"
                    >
                        <span>عرض حسب النوع (النوع × الورش)</span>
                        <span class="rounded-md px-1.5 py-0.5 text-[11px] tabular-nums" :class="viewMode === 'by_brand' ? 'bg-emerald-500/30 text-emerald-100' : 'bg-slate-200/80 text-slate-700'">
                            {{ count($brandMatrix['rows']) }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="viewMode = 'by_equipment_name'"
                        :class="viewMode === 'by_equipment_name' ? 'border-slate-900 bg-slate-900 text-white shadow-sm' : 'border-slate-200 bg-slate-50 text-slate-800 hover:border-slate-400 hover:bg-white'"
                        class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs sm:text-sm font-extrabold transition cursor-pointer"
                    >
                        <span>عرض حسب نوع المعدة (المعدة × الورش)</span>
                        <span class="rounded-md px-1.5 py-0.5 text-[11px] tabular-nums" :class="viewMode === 'by_equipment_name' ? 'bg-sky-500/30 text-sky-100' : 'bg-slate-200/80 text-slate-700'">
                            {{ count($equipmentMatrix['rows']) }}
                        </span>
                    </button>
                </div>
            </div>
        </section>

        {{-- KPI Mini-Header --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs">
                <p class="text-xs font-bold text-slate-600">إجمالي الأعطال</p>
                <p class="mt-1.5 text-2xl font-extrabold text-slate-900 tabular-nums" x-text="kpis.total">{{ $kpis['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200/90 bg-rose-50/60 p-4 shadow-xs">
                <p class="text-xs font-bold text-rose-700">أعطال ثقيلة (حرجة)</p>
                <p class="mt-1.5 text-2xl font-extrabold text-rose-700 tabular-nums" x-text="kpis.critical">{{ $kpis['critical'] }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200/90 bg-amber-50/60 p-4 shadow-xs">
                <p class="text-xs font-bold text-amber-800">دعم مالي / قطع / تسعير</p>
                <p class="mt-1.5 text-2xl font-extrabold text-amber-800 tabular-nums" x-text="kpis.spare_parts_delay">{{ $kpis['spare_parts_delay'] }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200/90 bg-emerald-50/60 p-4 shadow-xs">
                <p class="text-xs font-bold text-emerald-800">تم الإصلاح</p>
                <p class="mt-1.5 text-2xl font-extrabold text-emerald-800 tabular-nums" x-text="kpis.repaired">{{ $kpis['repaired'] }}</p>
            </div>
        </section>

        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث بالمعدة، النوع، الكود، الورشة، العطل، الموقف، أو الملاحظات..."
            :create-model="\App\Models\EquipmentBreakdown::class"
        >
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">الورشة</label>
                <select x-model="filters.workshop_name" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الورش</option>
                    @foreach ($workshops as $workshop)
                        <option value="{{ $workshop }}">{{ $workshop }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">نوع المعدة</label>
                <select x-model="filters.equipment_name" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المعدات</option>
                    @foreach ($equipmentNames as $eqName)
                        <option value="{{ $eqName }}">{{ $eqName }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">النوع</label>
                <select x-model="filters.brand" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الأنواع</option>
                    @foreach ($brands as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">الموقف</label>
                <select x-model="filters.workshop_status" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المواقف</option>
                    @foreach ($workshopStatuses as $ws)
                        <option value="{{ $ws }}">{{ $ws }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">حجم العطل</label>
                <select x-model="filters.severity" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">الكل</option>
                    <option value="critical">ثقيل (حرج)</option>
                    <option value="medium">متوسط</option>
                    <option value="minor">خفيف</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">تأخر قطع الغيار</label>
                <select x-model="filters.spare_parts_delay" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">الكل</option>
                    <option value="1">يوجد تأخر قطع غيار</option>
                    <option value="0">بدون تأخر</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
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

        {{-- Pivot View 1: By Brand x Workshops (عرض حسب النوع والورش) --}}
        <div
            x-show="viewMode === 'by_brand'"
            x-cloak
            class="overflow-hidden rounded-2xl border-2 border-black bg-white shadow-sm"
        >
            <div class="bg-slate-900 px-6 py-4 text-center text-lg sm:text-xl font-black text-white">
                توزيع المعدات المتعطلة حسب النوع والورش (اضغط على أي رقم لعرض المعدات)
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-center text-sm">
                    <thead>
                        <tr class="border-b-2 border-black bg-sky-100/80">
                            <th scope="col" class="border-e-2 border-black px-3 py-3 text-center font-black text-black">م</th>
                            <th scope="col" class="border-e-2 border-black px-4 py-3 text-start font-black text-black">النوع</th>
                            @foreach ($brandMatrix['workshops'] as $workshop)
                                <th scope="col" class="border-e-2 border-black px-3 py-3 text-center font-black text-black">{{ $workshop }}</th>
                            @endforeach
                            <th scope="col" class="bg-slate-200/80 px-3.5 py-3 text-center font-black text-rose-700">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach ($brandMatrix['rows'] as $index => $row)
                            <tr class="border-b border-slate-300 even:bg-slate-50/70 hover:bg-sky-50/50 transition-colors">
                                <td class="whitespace-nowrap border-e-2 border-black px-3 py-3 font-extrabold text-slate-700 tabular-nums">
                                    {{ $index + 1 }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-black px-4 py-3 text-start font-black text-slate-950">
                                    <button
                                        type="button"
                                        @click="drillDownByBrand(@js($row['raw_value']))"
                                        class="hover:text-blue-700 hover:underline cursor-pointer"
                                    >
                                        {{ $row['label'] }}
                                    </button>
                                </td>
                                @foreach ($brandMatrix['workshops'] as $workshop)
                                    @php $cellCount = $row['counts'][$workshop] ?? 0; @endphp
                                    <td class="whitespace-nowrap border-e-2 border-black px-3 py-2.5 tabular-nums">
                                        @if ($cellCount > 0)
                                            <button
                                                type="button"
                                                @click="drillDownByBrand(@js($row['raw_value']), @js($workshop))"
                                                class="inline-flex min-w-9 items-center justify-center rounded-xl bg-blue-50 px-2.5 py-1 text-base font-black text-blue-700 ring-1 ring-inset ring-blue-600/30 hover:bg-blue-600 hover:text-white transition cursor-pointer"
                                                title="عرض {{ $row['label'] }} في {{ $workshop }}"
                                            >
                                                {{ $cellCount }}
                                            </button>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="whitespace-nowrap bg-rose-50/50 px-3.5 py-2.5 tabular-nums">
                                    <button
                                        type="button"
                                        @click="drillDownByBrand(@js($row['raw_value']))"
                                        class="inline-flex min-w-10 items-center justify-center rounded-xl bg-rose-100 px-2.5 py-1 text-base font-black text-rose-700 ring-1 ring-inset ring-rose-600/30 hover:bg-rose-600 hover:text-white transition cursor-pointer"
                                    >
                                        {{ $row['total'] }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-black bg-sky-100/90">
                            <td colspan="2" class="border-e-2 border-black px-4 py-3 text-center text-base font-black text-black">
                                الإجمالي
                            </td>
                            @foreach ($brandMatrix['workshops'] as $workshop)
                                <td class="border-e-2 border-black px-3 py-3 tabular-nums">
                                    <button
                                        type="button"
                                        @click="drillDownByBrand('', @js($workshop))"
                                        class="text-base sm:text-lg font-black text-slate-950 hover:text-blue-700 hover:underline cursor-pointer"
                                    >
                                        {{ $brandMatrix['column_totals'][$workshop] ?? 0 }}
                                    </button>
                                </td>
                            @endforeach
                            <td class="bg-rose-600 px-3.5 py-3 text-base sm:text-lg font-black text-white tabular-nums">
                                {{ $brandMatrix['grand_total'] }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Pivot View 2: By Equipment Type x Workshops (عرض حسب نوع المعدة والورش) --}}
        <div
            x-show="viewMode === 'by_equipment_name'"
            x-cloak
            class="overflow-hidden rounded-2xl border-2 border-black bg-white shadow-sm"
        >
            <div class="bg-slate-900 px-6 py-4 text-center text-lg sm:text-xl font-black text-white">
                توزيع المعدات المتعطلة حسب نوع المعدة والورش (اضغط على أي رقم لعرض المعدات)
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-center text-sm">
                    <thead>
                        <tr class="border-b-2 border-black bg-sky-100/80">
                            <th scope="col" class="border-e-2 border-black px-3 py-3 text-center font-black text-black">م</th>
                            <th scope="col" class="border-e-2 border-black px-4 py-3 text-start font-black text-black">نوع المعدة</th>
                            @foreach ($equipmentMatrix['workshops'] as $workshop)
                                <th scope="col" class="border-e-2 border-black px-3 py-3 text-center font-black text-black">{{ $workshop }}</th>
                            @endforeach
                            <th scope="col" class="bg-slate-200/80 px-3.5 py-3 text-center font-black text-rose-700">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach ($equipmentMatrix['rows'] as $index => $row)
                            <tr class="border-b border-slate-300 even:bg-slate-50/70 hover:bg-sky-50/50 transition-colors">
                                <td class="whitespace-nowrap border-e-2 border-black px-3 py-3 font-extrabold text-slate-700 tabular-nums">
                                    {{ $index + 1 }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-black px-4 py-3 text-start font-black text-slate-950">
                                    <button
                                        type="button"
                                        @click="drillDownByEquipmentName(@js($row['raw_value']))"
                                        class="hover:text-emerald-700 hover:underline cursor-pointer"
                                    >
                                        {{ $row['label'] }}
                                    </button>
                                </td>
                                @foreach ($equipmentMatrix['workshops'] as $workshop)
                                    @php $cellCount = $row['counts'][$workshop] ?? 0; @endphp
                                    <td class="whitespace-nowrap border-e-2 border-black px-3 py-2.5 tabular-nums">
                                        @if ($cellCount > 0)
                                            <button
                                                type="button"
                                                @click="drillDownByEquipmentName(@js($row['raw_value']), @js($workshop))"
                                                class="inline-flex min-w-9 items-center justify-center rounded-xl bg-emerald-50 px-2.5 py-1 text-base font-black text-emerald-700 ring-1 ring-inset ring-emerald-600/30 hover:bg-emerald-600 hover:text-white transition cursor-pointer"
                                                title="عرض {{ $row['label'] }} في {{ $workshop }}"
                                            >
                                                {{ $cellCount }}
                                            </button>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="whitespace-nowrap bg-rose-50/50 px-3.5 py-2.5 tabular-nums">
                                    <button
                                        type="button"
                                        @click="drillDownByEquipmentName(@js($row['raw_value']))"
                                        class="inline-flex min-w-10 items-center justify-center rounded-xl bg-rose-100 px-2.5 py-1 text-base font-black text-rose-700 ring-1 ring-inset ring-rose-600/30 hover:bg-rose-600 hover:text-white transition cursor-pointer"
                                    >
                                        {{ $row['total'] }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-black bg-sky-100/90">
                            <td colspan="2" class="border-e-2 border-black px-4 py-3 text-center text-base font-black text-black">
                                الإجمالي
                            </td>
                            @foreach ($equipmentMatrix['workshops'] as $workshop)
                                <td class="border-e-2 border-black px-3 py-3 tabular-nums">
                                    <button
                                        type="button"
                                        @click="drillDownByEquipmentName('', @js($workshop))"
                                        class="text-base sm:text-lg font-black text-slate-950 hover:text-emerald-700 hover:underline cursor-pointer"
                                    >
                                        {{ $equipmentMatrix['column_totals'][$workshop] ?? 0 }}
                                    </button>
                                </td>
                            @endforeach
                            <td class="bg-rose-600 px-3.5 py-3 text-base sm:text-lg font-black text-white tabular-nums">
                                {{ $equipmentMatrix['grand_total'] }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Ruled Data Table --}}
        <div x-show="viewMode === 'list'" class="relative overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
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
                @include('modules.equipment-breakdowns._table', ['records' => $records])
            </div>
        </div>

        {{-- Slide-Over Create/Edit Drawer (No Required Fields) --}}
        <x-module.drawer
            create-title="تسجيل عطل معدة جديد"
            edit-title="تعديل سجل عطل المعدة"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">المعدة</label>
                    <input type="text" x-model="form.equipment_name" placeholder="مثال: مولد / قلاب / بلدوزر" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('equipment_name')" x-text="firstError('equipment_name')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">النوع</label>
                    <input type="text" dir="ltr" x-model="form.brand" placeholder="CAT / MAN / KOMATSU" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('brand')" x-text="firstError('brand')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الكود</label>
                    <input type="text" dir="ltr" x-model="form.equipment_code" placeholder="DT_000070" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('equipment_code')" x-text="firstError('equipment_code')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الورشة</label>
                    <input type="text" list="breakdown-workshop-presets" x-model="form.workshop_name" placeholder="مثال: ورشه نجران" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="breakdown-workshop-presets">
                        <option value="ورشه نجران"></option>
                        <option value="ورشه المدينة"></option>
                        <option value="ورشه مكة المكرمة"></option>
                        <option value="ورشه الجنادريه"></option>
                        <option value="ورشه جدة"></option>
                        <option value="ورشه الشفا"></option>
                        <option value="ورشه احد رفيدة"></option>
                    </datalist>
                    <p x-show="firstError('workshop_name')" x-text="firstError('workshop_name')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الموقف</label>
                    <input type="text" list="breakdown-status-presets" x-model="form.workshop_status" placeholder="دعم مالي / قيد الاصلاح / مؤجل" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="breakdown-status-presets">
                        <option value="جاري الكشف"></option>
                        <option value="دعم مالي"></option>
                        <option value="قيد الاصلاح"></option>
                        <option value="متوقف تسعير"></option>
                        <option value="توريد قطع"></option>
                        <option value="نقل للورشه المركزيه"></option>
                        <option value="مؤجل"></option>
                        <option value="تم الاصلاح"></option>
                    </datalist>
                    <p x-show="firstError('workshop_status')" x-text="firstError('workshop_status')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">حجم العطل</label>
                    <select x-model="form.severity" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                        <option value="critical">ثقيل (حرج)</option>
                        <option value="medium">متوسط</option>
                        <option value="minor">خفيف</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">التصنيف الفني</label>
                    <select x-model="form.category" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                        @foreach ($categories as $category)
                            <option value="{{ $category->value }}">{{ $category->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">حالة الإصلاح</label>
                    <select x-model="form.repair_status" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                        @foreach ($repairStatuses as $repairStatus)
                            <option value="{{ $repairStatus->value }}">{{ $repairStatus->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Smart Module 4 -> Module 2 Cascade Prompt when marking as Repaired --}}
            <div
                x-show="drawerMode === 'edit' && form.repair_status === 'repaired' && (form.waiting_stoppages_count || 0) > 0"
                x-cloak
                class="rounded-2xl border border-emerald-300 bg-emerald-50/90 p-4 space-y-3"
            >
                <div class="flex items-start gap-2.5">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-emerald-950">
                            تنبيه ذكي: يوجد <span class="tabular-nums" x-text="form.waiting_stoppages_count"></span> سائق متوقف مرتبط بهذا العطل
                        </p>
                        <ul class="mt-1 space-y-0.5 text-[11px] text-emerald-800">
                            <template x-for="drv in (form.linked_waiting_stoppages || [])" :key="drv.id">
                                <li x-text="'• ' + drv.driver_name + ' (' + drv.employee_id + ') — متوقف ' + drv.idle_days + ' يوم'"></li>
                            </template>
                        </ul>
                    </div>
                </div>

                <label class="flex items-center gap-2.5 text-xs font-bold text-emerald-950 cursor-pointer">
                    <input type="checkbox" x-model="form.resolve_linked_stoppages" class="h-4 w-4 rounded border-emerald-400 text-emerald-600 focus:ring-emerald-500">
                    <span>تحديث حالة السائقين المرتبطين في محور توقف السائقين تلقائياً</span>
                </label>

                <div x-show="form.resolve_linked_stoppages" x-cloak>
                    <label class="block text-[11px] font-semibold text-emerald-900 mb-1">الحالة الجديدة للسائقين المرتبطين</label>
                    <select x-model="form.linked_stoppage_status" class="block w-full rounded-xl border border-emerald-300 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-emerald-600 focus:outline-none">
                        <option value="resolved">تمت المعالجة (عودة للعمل)</option>
                        <option value="reassigned">تم إعادة التوجيه</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">العطل</label>
                <textarea rows="2" x-model="form.fault_description" placeholder="وصف العطل الفني..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                <p x-show="firstError('fault_description')" x-text="firstError('fault_description')" class="mt-1 text-xs text-rose-600"></p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تاريخ التوقف</label>
                    <input type="date" x-model="form.breakdown_date" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('breakdown_date')" x-text="firstError('breakdown_date')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تاريخ الإصلاح</label>
                    <input type="date" x-model="form.expected_repair_date" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('expected_repair_date')" x-text="firstError('expected_repair_date')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">المبلغ (ر.س)</label>
                    <input type="number" step="0.01" min="0" x-model="form.estimated_cost" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('estimated_cost')" x-text="firstError('estimated_cost')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">ملاحظة المبلغ (إن وُجدت)</label>
                    <input type="text" x-model="form.cost_note" placeholder="مثال: من الاعتماد البنكي / خارج الورشه" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('cost_note')" x-text="firstError('cost_note')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div>
                <label class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50/70 px-3.5 py-2.5 text-xs font-bold text-amber-900 cursor-pointer">
                    <input type="checkbox" x-model="form.spare_parts_delay" class="h-4 w-4 rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                    <span>يوجد تأخر في توريد قطع الغيار</span>
                </label>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">ملاحظات الموقف</label>
                <textarea rows="3" x-model="form.notes" placeholder="تفاصيل الموقف، الرفع المالي، أو خطة النقل والتشغيل..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                <p x-show="firstError('notes')" x-text="firstError('notes')" class="mt-1 text-xs text-rose-600"></p>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />
    </div>
@endsection
