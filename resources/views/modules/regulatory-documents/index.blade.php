@extends('layouts.app')

@section('title', 'الأوراق النظامية')
@section('page_title', 'الأوراق النظامية')
@section('page_subtitle', 'متابعة الفحص الدوري المنتهي، كروت التشغيل المنتهية، والاستمارات المنتهية للمعدات')

@section('content')
    @php
        $defaultDocType = $activeDocumentType !== '' ? $activeDocumentType : 'فحص دوري';

        $moduleConfig = [
            'baseUrl' => url('/regulatory-documents'),
            'storeUrl' => route('regulatory-documents.store'),
            'exportBaseUrl' => route('regulatory-documents.export'),
            'initialExportUrl' => route('regulatory-documents.export', array_filter($filters)),
            'initialFilters' => [
                'search' => $filters['search'] ?? '',
                'document_type' => $activeDocumentType,
                'branch' => $filters['branch'] ?? '',
                'transport_type' => $filters['transport_type'] ?? '',
                'equipment_type' => $filters['equipment_type'] ?? '',
                'renewal_status' => $filters['renewal_status'] ?? '',
                'compliance_status' => $filters['compliance_status'] ?? '',
                'entity_type' => $filters['entity_type'] ?? '',
                'focus' => $filters['focus'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            'defaultFilters' => [
                'search' => '',
                'document_type' => $activeDocumentType,
                'branch' => '',
                'transport_type' => '',
                'equipment_type' => '',
                'renewal_status' => '',
                'compliance_status' => '',
                'entity_type' => '',
                'focus' => '',
                'date_from' => '',
                'date_to' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'entity_type' => 'equipment',
                'entity_identifier' => '',
                'equipment_type' => '',
                'plate_number' => '',
                'transport_type' => 'نقل عام',
                'branch' => '',
                'document_type' => $defaultDocType,
                'document_number' => '',
                'issue_date' => '',
                'hijri_expiry_date' => '',
                'expiry_date' => now()->format('Y-m-d'),
                'compliance_status' => 'expired',
                'renewal_status' => 'مطلوب تجديد',
                'renewal_cost' => '0',
                'notes' => '',
            ],
        ];
    @endphp

    <div
        x-data="{
            ...fleetModuleManager(@js($moduleConfig)),
            computedCompliance() {
                if (this.form.compliance_status === 'missing' || !this.form.expiry_date) {
                    return { label: 'مفقود (أحمر)', classes: 'border-rose-200 bg-rose-50 text-rose-800' };
                }
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const expiry = new Date(this.form.expiry_date + 'T00:00:00');
                const diffDays = Math.round((expiry - today) / 86400000);
                if (diffDays < 0) {
                    return { label: 'منتهي منذ ' + Math.abs(diffDays) + ' يوم (أحمر)', classes: 'border-rose-200 bg-rose-50 text-rose-800' };
                }
                if (diffDays <= 30) {
                    return { label: 'قارب على الانتهاء — متبقي ' + diffDays + ' يوم (كهرماني)', classes: 'border-amber-200 bg-amber-50 text-amber-900' };
                }
                return { label: 'ساري — متبقي ' + diffDays + ' يوم (أخضر)', classes: 'border-emerald-200 bg-emerald-50 text-emerald-800' };
            }
        }"
        class="space-y-5"
    >
        {{-- Sub-Pages Navigation Cards (صفحات الأوراق النظامية) --}}
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($documentTabs as $tab)
                @php
                    $isActiveTab = ! ($isSummaryView ?? false) && $tab['value'] === $activeDocumentType;
                    $tabRouteParams = $tab['value'] !== '' ? ['document_type' => $tab['value']] : [];
                @endphp
                <a
                    href="{{ route('regulatory-documents.index', $tabRouteParams) }}"
                    class="group flex items-center justify-between gap-3 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ $isActiveTab ? 'border-slate-950 bg-slate-950 text-white shadow-lg ring-4 ring-slate-950/15' : 'border-slate-300 bg-white text-slate-900 shadow-xs hover:border-slate-900 hover:bg-slate-50 hover:shadow-md' }}"
                >
                    <div class="min-w-0">
                        <p class="text-base sm:text-lg font-black leading-tight">{{ $tab['label'] }}</p>
                        <p class="mt-1 text-xs font-bold {{ $isActiveTab ? 'text-slate-300' : 'text-slate-500' }}">كشف الأوراق النظامية للمعدات</p>
                    </div>
                    <div class="shrink-0 rounded-2xl px-3.5 py-2 text-center {{ $isActiveTab ? 'bg-rose-500/25 text-rose-100' : 'bg-slate-100 text-slate-950' }}">
                        <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format((int) $tab['count']) }}</p>
                        <p class="mt-1 text-[11px] font-extrabold {{ $isActiveTab ? 'text-rose-200' : 'text-slate-500' }}">سجل</p>
                    </div>
                </a>
            @endforeach

            <a
                href="{{ route('regulatory-documents.index', ['view' => 'summary']) }}"
                class="group flex items-center justify-between gap-3 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ ($isSummaryView ?? false) ? 'border-emerald-800 bg-emerald-700 text-white shadow-lg ring-4 ring-emerald-700/15' : 'border-emerald-300 bg-emerald-50/40 text-slate-900 shadow-xs hover:border-emerald-700 hover:bg-emerald-50 hover:shadow-md' }}"
            >
                <div class="min-w-0">
                    <p class="text-base sm:text-lg font-black leading-tight">ملخص حسب نوع المعدة</p>
                    <p class="mt-1 text-xs font-bold {{ ($isSummaryView ?? false) ? 'text-emerald-100' : 'text-emerald-900/75' }}">توزيع الأوراق المنتهية حسب نوع المعدة</p>
                </div>
                <div class="shrink-0 rounded-2xl px-3.5 py-2 text-center {{ ($isSummaryView ?? false) ? 'bg-white/15 text-white' : 'bg-emerald-100 text-emerald-900' }}">
                    <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format(count($equipmentSummary['rows'] ?? [])) }}</p>
                    <p class="mt-1 text-[11px] font-extrabold {{ ($isSummaryView ?? false) ? 'text-emerald-100' : 'text-emerald-800' }}">نوع</p>
                </div>
            </a>
        </section>

        @if ($isSummaryView ?? false)
            {{-- Summary Table by Equipment Type (فحص منتهي × استمارات منتهية × كروت منتهية) --}}
            <section class="overflow-hidden rounded-2xl border-2 border-slate-950 bg-white shadow-md">
                <div class="bg-slate-950 px-6 py-4 text-center text-lg sm:text-xl font-black text-white">
                    ملخص الأوراق النظامية المنتهية حسب نوع المعدة (اضغط على أي رقم لعرض التفاصيل)
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-center text-sm">
                        <thead>
                            <tr class="border-b-2 border-black bg-sky-100/90 text-sm sm:text-base font-black text-black">
                                <th scope="col" class="w-16 whitespace-nowrap border-e-2 border-black px-3 py-4 text-center">م</th>
                                <th scope="col" class="whitespace-nowrap border-e-2 border-black px-5 py-4 text-center">نوع المعدة</th>
                                <th scope="col" class="whitespace-nowrap border-e-2 border-black px-4 py-4 text-center text-rose-800">فحص منتهي</th>
                                <th scope="col" class="whitespace-nowrap border-e-2 border-black px-4 py-4 text-center text-purple-900">استمارات منتهية</th>
                                <th scope="col" class="whitespace-nowrap border-e-2 border-black px-4 py-4 text-center text-amber-900">كروت منتهية</th>
                                <th scope="col" class="whitespace-nowrap bg-slate-200/80 px-5 py-4 text-center text-slate-950">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse ($equipmentSummary['rows'] ?? [] as $index => $row)
                                <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-base font-black text-slate-950 tabular-nums">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-5 py-3.5 text-center text-base sm:text-lg font-black text-slate-950">
                                        {{ $row['equipment_type'] }}
                                    </td>
                                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3 text-center tabular-nums">
                                        @if ($row['expired_inspection'] > 0)
                                            <a
                                                href="{{ route('regulatory-documents.index', array_filter(['document_type' => 'فحص دوري', 'equipment_type' => $row['raw_equipment_type']])) }}"
                                                class="inline-flex min-w-12 items-center justify-center rounded-xl bg-rose-100 px-3.5 py-1.5 text-base font-black text-rose-900 ring-1 ring-inset ring-rose-600/35 hover:bg-rose-600 hover:text-white transition"
                                            >
                                                {{ number_format($row['expired_inspection']) }}
                                            </a>
                                        @else
                                            <span class="text-base font-bold text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3 text-center tabular-nums">
                                        @if ($row['expired_registration'] > 0)
                                            <a
                                                href="{{ route('regulatory-documents.index', array_filter(['document_type' => 'استمارة', 'equipment_type' => $row['raw_equipment_type']])) }}"
                                                class="inline-flex min-w-12 items-center justify-center rounded-xl bg-purple-100 px-3.5 py-1.5 text-base font-black text-purple-900 ring-1 ring-inset ring-purple-600/35 hover:bg-purple-700 hover:text-white transition"
                                            >
                                                {{ number_format($row['expired_registration']) }}
                                            </a>
                                        @else
                                            <span class="text-base font-bold text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3 text-center tabular-nums">
                                        @if ($row['expired_operating_card'] > 0)
                                            <a
                                                href="{{ route('regulatory-documents.index', array_filter(['document_type' => 'كرت تشغيل', 'equipment_type' => $row['raw_equipment_type']])) }}"
                                                class="inline-flex min-w-12 items-center justify-center rounded-xl bg-amber-100 px-3.5 py-1.5 text-base font-black text-amber-950 ring-1 ring-inset ring-amber-600/35 hover:bg-amber-600 hover:text-white transition"
                                            >
                                                {{ number_format($row['expired_operating_card']) }}
                                            </a>
                                        @else
                                            <span class="text-base font-bold text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap bg-slate-100/80 px-5 py-3 text-center tabular-nums">
                                        <a
                                            href="{{ route('regulatory-documents.index', array_filter(['equipment_type' => $row['raw_equipment_type']])) }}"
                                            class="inline-flex min-w-14 items-center justify-center rounded-xl bg-slate-900 px-3.5 py-1.5 text-base font-black text-white hover:bg-emerald-700 transition"
                                        >
                                            {{ number_format($row['total']) }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <p class="text-base font-black text-slate-900">لا توجد أوراق نظامية منتهية لعرضها حالياً</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($equipmentSummary['rows'] ?? []) > 0)
                            <tfoot>
                                <tr class="border-t-2 border-black bg-sky-100/95 text-base sm:text-lg font-black">
                                    <td colspan="2" class="border-e-2 border-black px-5 py-4 text-center text-black">
                                        الإجمالي
                                    </td>
                                    <td class="border-e-2 border-black px-4 py-4 text-center text-rose-800 tabular-nums">
                                        <a href="{{ route('regulatory-documents.index', ['document_type' => 'فحص دوري']) }}" class="hover:underline">
                                            {{ number_format($equipmentSummary['totals']['expired_inspection'] ?? 0) }}
                                        </a>
                                    </td>
                                    <td class="border-e-2 border-black px-4 py-4 text-center text-purple-900 tabular-nums">
                                        <a href="{{ route('regulatory-documents.index', ['document_type' => 'استمارة']) }}" class="hover:underline">
                                            {{ number_format($equipmentSummary['totals']['expired_registration'] ?? 0) }}
                                        </a>
                                    </td>
                                    <td class="border-e-2 border-black px-4 py-4 text-center text-amber-900 tabular-nums">
                                        <a href="{{ route('regulatory-documents.index', ['document_type' => 'كرت تشغيل']) }}" class="hover:underline">
                                            {{ number_format($equipmentSummary['totals']['expired_operating_card'] ?? 0) }}
                                        </a>
                                    </td>
                                    <td class="bg-slate-950 px-5 py-4 text-center text-white tabular-nums">
                                        {{ number_format($equipmentSummary['totals']['total'] ?? 0) }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </section>
        @else
        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث بكود المعدة، نوع المعدة، رقم اللوحة، الفرع، الرقم التسلسلي، أو الموقف من التجديد..."
            :create-model="\App\Models\RegulatoryDocument::class"
            create-label="إضافة سجل جديد"
            :show-import-purge="true"
        >
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">الفرع الحالي</label>
                <select x-model="filters.branch" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الفروع</option>
                    @foreach ($options['branches'] as $branch)
                        <option value="{{ $branch }}">{{ $branch }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">نوع المعدة</label>
                <select x-model="filters.equipment_type" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الأنواع</option>
                    @foreach ($options['equipment_types'] as $eqType)
                        <option value="{{ $eqType }}">{{ $eqType }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">نوع النقل</label>
                <select x-model="filters.transport_type" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">الكل</option>
                    @foreach ($options['transport_types'] as $tType)
                        <option value="{{ $tType }}">{{ $tType }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">الموقف من التجديد</label>
                <select x-model="filters.renewal_status" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المواقف</option>
                    @foreach ($options['renewal_statuses'] as $rStatus)
                        <option value="{{ $rStatus }}">{{ $rStatus }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">حالة الامتثال</label>
                <select x-model="filters.compliance_status" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الحالات</option>
                    @foreach ($complianceStatuses as $complianceStatus)
                        <option value="{{ $complianceStatus->value }}">{{ $complianceStatus->label() }}</option>
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
                @include('modules.regulatory-documents._table', [
                    'records' => $records,
                    'activeDocumentType' => $activeDocumentType,
                ])
            </div>
        </div>
        @endif

        {{-- Slide-Over Create/Edit Drawer (No Required Fields) --}}
        <x-module.drawer
            create-title="إضافة سجل أوراق نظامية جديد"
            edit-title="تعديل سجل الوثيقة النظامية"
        >
            {{-- Live Auto-Classification Banner --}}
            <div class="rounded-2xl border p-3.5 text-xs font-semibold" :class="computedCompliance().classes">
                التصنيف التلقائي للامتثال:
                <span class="font-bold" x-text="computedCompliance().label"></span>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">كود المعدة / المعرّف</label>
                    <input type="text" dir="ltr" x-model="form.entity_identifier" placeholder="DT_000033" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('entity_identifier')" x-text="firstError('entity_identifier')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع المعدة</label>
                    <input type="text" list="reg-equipment-types" x-model="form.equipment_type" placeholder="قلابات، وايتات، دينات..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="reg-equipment-types">
                        @foreach ($options['equipment_types'] as $eqType)
                            <option value="{{ $eqType }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">رقم اللوحة</label>
                    <input type="text" x-model="form.plate_number" placeholder="أ س س 5626" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع النقل</label>
                    <select x-model="form.transport_type" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                        <option value="">—</option>
                        <option value="نقل عام">نقل عام</option>
                        <option value="نقل خاص">نقل خاص</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الفرع الحالي</label>
                    <input type="text" list="reg-branches" x-model="form.branch" placeholder="نجران، مكة، الرياض..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="reg-branches">
                        @foreach ($options['branches'] as $branch)
                            <option value="{{ $branch }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع الوثيقة</label>
                    <input type="text" list="document-type-presets" x-model="form.document_type" placeholder="فحص دوري، كرت تشغيل، استمارة..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="document-type-presets">
                        <option value="فحص دوري"></option>
                        <option value="كرت تشغيل"></option>
                        <option value="استمارة"></option>
                        <option value="تأمين"></option>
                        <option value="رخصة قيادة"></option>
                        <option value="بطاقة سائق"></option>
                    </datalist>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الرقم التسلسلي / رقم الوثيقة</label>
                    <input type="text" dir="ltr" x-model="form.document_number" placeholder="63523900" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الموقف من التجديد</label>
                    <select x-model="form.renewal_status" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                        <option value="">—</option>
                        <option value="مطلوب تجديد">مطلوب تجديد</option>
                        <option value="عمر افتراضي">عمر افتراضي</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="flex items-center gap-2.5 rounded-xl border border-rose-200 bg-rose-50/70 px-3.5 py-2.5 text-xs font-bold text-rose-800 cursor-pointer">
                    <input
                        type="checkbox"
                        :checked="form.compliance_status === 'missing'"
                        @change="form.compliance_status = $event.target.checked ? 'missing' : 'valid'; if ($event.target.checked) { form.issue_date = ''; form.expiry_date = ''; form.hijri_expiry_date = ''; }"
                        class="h-4 w-4 rounded border-rose-400 text-rose-600 focus:ring-rose-500"
                    >
                    <span>الوثيقة مفقودة / غير متوفرة حالياً</span>
                </label>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" x-show="form.compliance_status !== 'missing'">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">التاريخ الهجري (هـ)</label>
                    <input type="text" dir="ltr" x-model="form.hijri_expiry_date" placeholder="28/05/1448" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الميلادي (تاريخ الانتهاء)</label>
                    <input type="date" x-model="form.expiry_date" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('expiry_date')" x-text="firstError('expiry_date')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع الكيان</label>
                    <select x-model="form.entity_type" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                        @foreach ($entityTypes as $entityType)
                            <option value="{{ $entityType->value }}">{{ $entityType->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تاريخ الإصدار</label>
                    <input type="date" x-model="form.issue_date" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تكلفة التجديد (ر.س)</label>
                    <input type="number" step="0.01" min="0" x-model="form.renewal_cost" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('renewal_cost')" x-text="firstError('renewal_cost')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">ملاحظات</label>
                <textarea rows="2" x-model="form.notes" placeholder="حالة طلب التجديد أو ملاحظات الامتثال..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />
        <x-module.import-modal
            :action="route('regulatory-documents.import.direct')"
            :template-url="route('regulatory-documents.import.template')"
            title="استيراد وإسقاط بيانات الأوراق النظامية"
            :hidden-fields="['document_type' => $activeDocumentType ?? '']"
        />
        <x-module.purge-modal
            :action="route('regulatory-documents.purge-all')"
            :module-name="($activeDocumentType ?? '') !== '' ? 'الأوراق النظامية ('.$activeDocumentType.')' : 'الأوراق النظامية'"
            :count="$kpis['total'] ?? 0"
            :hidden-fields="['document_type' => $activeDocumentType ?? '']"
        />
    </div>
@endsection
