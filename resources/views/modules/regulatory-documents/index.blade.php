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
        {{-- Sub-Pages Navigation Tabs (صفحات الأوراق النظامية) --}}
        <section class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900">صفحات الأوراق النظامية</h2>
                    <p class="text-xs text-slate-500 mt-0.5">اختر الكشف لعرض الفحص الدوري المنتهي، كروت التشغيل المنتهية، أو الاستمارات المنتهية</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($documentTabs as $tab)
                        @php
                            $isActiveTab = $tab['value'] === $activeDocumentType;
                            $tabRouteParams = $tab['value'] !== '' ? ['document_type' => $tab['value']] : [];
                        @endphp
                        <a
                            href="{{ route('regulatory-documents.index', $tabRouteParams) }}"
                            class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs sm:text-sm font-extrabold transition {{ $isActiveTab ? 'border-slate-900 bg-slate-900 text-white shadow-sm' : 'border-slate-200 bg-slate-50 text-slate-800 hover:border-slate-400 hover:bg-white' }}"
                        >
                            <span>{{ $tab['label'] }}</span>
                            <span class="rounded-md px-1.5 py-0.5 text-[11px] tabular-nums {{ $isActiveTab ? 'bg-rose-500/30 text-rose-100' : 'bg-slate-200/80 text-slate-700' }}">
                                {{ $tab['count'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- KPI Mini-Header --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs">
                <p class="text-xs font-bold text-slate-600">إجمالي السجلات</p>
                <p class="mt-1.5 text-2xl font-extrabold text-slate-900 tabular-nums" x-text="kpis.total">{{ $kpis['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200/90 bg-rose-50/70 p-4 shadow-xs">
                <p class="text-xs font-bold text-rose-700">منتهية أو مفقودة</p>
                <p class="mt-1.5 text-2xl font-extrabold text-rose-800 tabular-nums" x-text="kpis.critical">{{ $kpis['critical'] }}</p>
            </div>
            <button
                type="button"
                @click="filters.renewal_status = 'مطلوب تجديد'; applyFilters()"
                class="rounded-2xl border border-amber-200/90 bg-amber-50/70 p-4 text-start shadow-xs hover:bg-amber-100/70 transition cursor-pointer"
                title="تصفية السجلات المطلوب تجديدها"
            >
                <p class="text-xs font-bold text-amber-800">مطلوب تجديد</p>
                <p class="mt-1.5 text-2xl font-extrabold text-amber-900 tabular-nums" x-text="kpis.renewal_required">{{ $kpis['renewal_required'] }}</p>
            </button>
            <button
                type="button"
                @click="filters.renewal_status = 'عمر افتراضي'; applyFilters()"
                class="rounded-2xl border border-purple-200/90 bg-purple-50/70 p-4 text-start shadow-xs hover:bg-purple-100/70 transition cursor-pointer"
                title="تصفية السجلات المنتهية بالعمر الافتراضي"
            >
                <p class="text-xs font-bold text-purple-800">عمر افتراضي</p>
                <p class="mt-1.5 text-2xl font-extrabold text-purple-900 tabular-nums" x-text="kpis.end_of_life">{{ $kpis['end_of_life'] }}</p>
            </button>
        </section>

        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث بكود المعدة، نوع المعدة، رقم اللوحة، الفرع، الرقم التسلسلي، أو الموقف من التجديد..."
            :create-model="\App\Models\RegulatoryDocument::class"
            create-label="إضافة سجل جديد"
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
                @include('modules.regulatory-documents._table', [
                    'records' => $records,
                    'activeDocumentType' => $activeDocumentType,
                ])
            </div>
        </div>

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
    </div>
@endsection
