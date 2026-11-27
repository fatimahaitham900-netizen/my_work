@extends('layouts.app')

@section('title', 'الأوراق النظامية للسيارات')
@section('page_title', 'الأوراق النظامية للسيارات')
@section('page_subtitle', 'متابعة الفحص الدوري المنتهي، الاستمارات المنتهية، والتأمين المنتهي للسيارات')

@section('content')
    @php
        $defaultDocType = $activeDocumentType !== '' ? $activeDocumentType : \App\Models\CarRegulatoryDocument::TYPE_INSPECTION;

        $moduleConfig = [
            'baseUrl' => url('/car-regulatory-documents'),
            'storeUrl' => route('car-regulatory-documents.store'),
            'exportBaseUrl' => route('car-regulatory-documents.export'),
            'initialExportUrl' => route('car-regulatory-documents.export', array_filter($filters)),
            'initialFilters' => [
                'search' => $filters['search'] ?? '',
                'document_type' => $activeDocumentType,
                'branch' => $filters['branch'] ?? '',
                'project_location' => $filters['project_location'] ?? '',
            ],
            'defaultFilters' => [
                'search' => '',
                'document_type' => $activeDocumentType,
                'branch' => '',
                'project_location' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'document_type' => $defaultDocType,
                'car_code' => '',
                'plate_number' => '',
                'car_type_model' => '',
                'branch' => '',
                'project_location' => '',
                'hijri_expiry_date' => '',
                'expiry_date' => now()->format('Y-m-d'),
                'status_label' => '',
                'notes' => '',
            ],
        ];
    @endphp

    <div x-data="fleetModuleManager(@js($moduleConfig))" class="space-y-5">
        {{-- Sub-Pages Navigation Cards (صفحات الأوراق النظامية للسيارات) --}}
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($documentTabs as $tab)
                @php
                    $isActiveTab = $tab['value'] === $activeDocumentType;
                    $tabRouteParams = $tab['value'] !== '' ? ['document_type' => $tab['value']] : [];
                @endphp
                <a
                    href="{{ route('car-regulatory-documents.index', $tabRouteParams) }}"
                    class="group flex items-center justify-between gap-3 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ $isActiveTab ? 'border-slate-950 bg-slate-950 text-white shadow-lg ring-4 ring-slate-950/15' : 'border-slate-300 bg-white text-slate-900 shadow-xs hover:border-slate-900 hover:bg-slate-50 hover:shadow-md' }}"
                >
                    <div class="min-w-0">
                        <p class="text-base sm:text-lg font-black leading-tight">{{ $tab['label'] }}</p>
                        <p class="mt-1 text-xs font-bold {{ $isActiveTab ? 'text-slate-300' : 'text-slate-500' }}">كشف الأوراق النظامية للسيارات</p>
                    </div>
                    <div class="shrink-0 rounded-2xl px-3.5 py-2 text-center {{ $isActiveTab ? 'bg-rose-500/25 text-rose-100' : 'bg-slate-100 text-slate-950' }}">
                        <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format((int) $tab['count']) }}</p>
                        <p class="mt-1 text-[11px] font-extrabold {{ $isActiveTab ? 'text-rose-200' : 'text-slate-500' }}">سيارة</p>
                    </div>
                </a>
            @endforeach
        </section>

        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث بكود السيارة، رقم اللوحة، نوع السيارة والموديل، الفرع، أو المشروع..."
            :create-model="\App\Models\CarRegulatoryDocument::class"
            create-label="إضافة سجل جديد"
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
                <label class="block text-[11px] font-bold text-slate-600 mb-1">المشروع / الموقع</label>
                <select x-model="filters.project_location" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المشاريع والمواقع</option>
                    @foreach ($options['project_locations'] as $loc)
                        <option value="{{ $loc }}">{{ $loc }}</option>
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
                @include('modules.car-regulatory-documents._table', [
                    'records' => $records,
                    'activeDocumentType' => $activeDocumentType,
                ])
            </div>
        </div>

        {{-- Slide-Over Create/Edit Drawer --}}
        <x-module.drawer
            create-title="إضافة سجل أوراق نظامية للسيارات"
            edit-title="تعديل سجل الأوراق النظامية للسيارة"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع الوثيقة</label>
                    <select x-model="form.document_type" class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold focus:border-slate-900 focus:outline-none">
                        <option value="فحص دوري">فحص دوري</option>
                        <option value="استمارة">استمارة</option>
                        <option value="تأمين">تأمين</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">كود السيارة</label>
                    <input type="text" dir="ltr" x-model="form.car_code" placeholder="C_000205" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">رقم اللوحة</label>
                    <input type="text" x-model="form.plate_number" placeholder="أ ل ر 9882" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع السيارة والموديل</label>
                <input type="text" x-model="form.car_type_model" placeholder="تويوتا هايلكس غمارتين (2018)" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الفرع</label>
                    <input type="text" list="car-reg-branches" x-model="form.branch" placeholder="أبها / البحر الأحمر / الجوف" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="car-reg-branches">
                        @foreach ($options['branches'] as $branch)
                            <option value="{{ $branch }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">المشروع / الموقع</label>
                    <input type="text" list="car-reg-projects" x-model="form.project_location" placeholder="مشروع باللحمر / مشروع السلاحف" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="car-reg-projects">
                        @foreach ($options['project_locations'] as $loc)
                            <option value="{{ $loc }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تاريخ الانتهاء (هجري)</label>
                    <input type="text" dir="ltr" x-model="form.hijri_expiry_date" placeholder="1447-10-21" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تاريخ الانتهاء (ميلادي)</label>
                    <input type="date" x-model="form.expiry_date" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">ملاحظات</label>
                <textarea x-model="form.notes" rows="2" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />

        <x-module.import-modal
            :action="route('car-regulatory-documents.import.direct')"
            :template-url="route('car-regulatory-documents.import.template')"
            :title="$activeDocumentType !== '' ? 'استيراد وإسقاط (' . $activeDocumentType . ' للسيارات)' : 'استيراد وإسقاط الأوراق النظامية للسيارات'"
            :hidden-fields="['document_type' => $activeDocumentType]"
        />

        <x-module.purge-modal
            :action="route('car-regulatory-documents.purge-all')"
            :module-name="$activeDocumentType !== '' ? $activeDocumentType . ' (السيارات)' : 'الأوراق النظامية للسيارات'"
            :count="$kpis['total'] ?? 0"
            :hidden-fields="['document_type' => $activeDocumentType]"
        />
    </div>
@endsection

