@extends('layouts.app')

@section('title', 'توزيعة المعدات')
@section('page_title', 'توزيعة المعدات')
@section('page_subtitle', 'متابعة توزيعة المعدات حسب النوع والفرع والموقع وحالة المعدة مع ملخص تفصيلي حسب النوع')

@section('content')
    @php
        $currentView = $activeView ?? 'table';
        $moduleConfig = [
            'baseUrl' => url('/equipment-distributions'),
            'storeUrl' => route('equipment-distributions.store'),
            'exportBaseUrl' => route('equipment-distributions.export'),
            'initialExportUrl' => route('equipment-distributions.export', array_filter($filters)),
            'initialFilters' => [
                'view' => $currentView,
                'search' => $filters['search'] ?? '',
                'equipment_type' => $filters['equipment_type'] ?? '',
                'branch' => $filters['branch'] ?? '',
                'site_location' => $filters['site_location'] ?? '',
                'equipment_status' => $filters['equipment_status'] ?? '',
            ],
            'defaultFilters' => [
                'view' => $currentView,
                'search' => '',
                'equipment_type' => '',
                'branch' => '',
                'site_location' => '',
                'equipment_status' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'equipment_code' => '',
                'equipment_type' => '',
                'branch' => '',
                'site_location' => '',
                'equipment_status' => 'تم تشغيلها ساعة أو أكثر',
                'notes' => '',
            ],
        ];
    @endphp

    <div x-data="fleetModuleManager(@js($moduleConfig))" class="space-y-5">
        {{-- View Switcher Cards: جدول التوزيعة | ملخص حسب النوع | ملخص حسب الموقع | إجمالي المعدات والسائقين --}}
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <a
                href="{{ route('equipment-distributions.index', array_merge(request()->except('view', 'page'), ['view' => 'table'])) }}"
                class="group flex items-center justify-between gap-3 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ $currentView === 'table' ? 'border-slate-950 bg-slate-950 text-white shadow-lg ring-4 ring-slate-950/15' : 'border-slate-300 bg-white text-slate-900 shadow-xs hover:border-slate-900 hover:bg-slate-50 hover:shadow-md' }}"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $currentView === 'table' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-900 group-hover:bg-slate-900 group-hover:text-white transition-colors' }}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-base sm:text-lg font-black leading-tight">جدول توزيعة المعدات</p>
                        <p class="mt-1 text-xs font-bold {{ $currentView === 'table' ? 'text-slate-300' : 'text-slate-500' }}">السجل التفصيلي لجميع المعدات</p>
                    </div>
                </div>
                <div class="shrink-0 rounded-2xl px-3.5 py-2 text-center {{ $currentView === 'table' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-950' }}">
                    <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format((int) ($kpis['total'] ?? 0)) }}</p>
                    <p class="mt-1 text-[11px] font-extrabold {{ $currentView === 'table' ? 'text-slate-200' : 'text-slate-500' }}">معدة</p>
                </div>
            </a>

            <a
                href="{{ route('equipment-distributions.index', array_merge(request()->except('view', 'page'), ['view' => 'summary'])) }}"
                class="group flex items-center justify-between gap-3 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ $currentView === 'summary' ? 'border-emerald-800 bg-emerald-700 text-white shadow-lg ring-4 ring-emerald-700/15' : 'border-emerald-300 bg-emerald-50/40 text-slate-900 shadow-xs hover:border-emerald-700 hover:bg-emerald-50 hover:shadow-md' }}"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $currentView === 'summary' ? 'bg-white/15 text-white' : 'bg-emerald-100 text-emerald-800 group-hover:bg-emerald-700 group-hover:text-white transition-colors' }}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-base sm:text-lg font-black leading-tight">ملخص حسب النوع</p>
                        <p class="mt-1 text-xs font-bold {{ $currentView === 'summary' ? 'text-emerald-100' : 'text-emerald-900/75' }}">توزيع الأنواع على الفروع</p>
                    </div>
                </div>
                <div class="shrink-0 rounded-2xl px-3.5 py-2 text-center {{ $currentView === 'summary' ? 'bg-white/15 text-white' : 'bg-emerald-100 text-emerald-900' }}">
                    <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format((int) ($kpis['types_count'] ?? 0)) }}</p>
                    <p class="mt-1 text-[11px] font-extrabold {{ $currentView === 'summary' ? 'text-emerald-100' : 'text-emerald-800' }}">نوع</p>
                </div>
            </a>

            <a
                href="{{ route('equipment-distributions.index', array_merge(request()->except('view', 'page'), ['view' => 'site_summary'])) }}"
                class="group flex items-center justify-between gap-3 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ $currentView === 'site_summary' ? 'border-sky-800 bg-sky-700 text-white shadow-lg ring-4 ring-sky-700/15' : 'border-sky-300 bg-sky-50/40 text-slate-900 shadow-xs hover:border-sky-700 hover:bg-sky-50 hover:shadow-md' }}"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $currentView === 'site_summary' ? 'bg-white/15 text-white' : 'bg-sky-100 text-sky-800 group-hover:bg-sky-700 group-hover:text-white transition-colors' }}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-base sm:text-lg font-black leading-tight">ملخص حسب الموقع</p>
                        <p class="mt-1 text-xs font-bold {{ $currentView === 'site_summary' ? 'text-sky-100' : 'text-sky-900/75' }}">المواقع مرتبة داخل كل فرع</p>
                    </div>
                </div>
                <div class="shrink-0 rounded-2xl px-3.5 py-2 text-center {{ $currentView === 'site_summary' ? 'bg-white/15 text-white' : 'bg-sky-100 text-sky-900' }}">
                    <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format((int) ($kpis['sites_count'] ?? 0)) }}</p>
                    <p class="mt-1 text-[11px] font-extrabold {{ $currentView === 'site_summary' ? 'text-sky-100' : 'text-sky-800' }}">موقع</p>
                </div>
            </a>

            <a
                href="{{ route('equipment-distributions.index', array_merge(request()->except('view', 'page'), ['view' => 'equipment_drivers'])) }}"
                class="group flex items-center justify-between gap-3 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ $currentView === 'equipment_drivers' ? 'border-indigo-800 bg-indigo-700 text-white shadow-lg ring-4 ring-indigo-700/15' : 'border-indigo-300 bg-indigo-50/40 text-slate-900 shadow-xs hover:border-indigo-700 hover:bg-indigo-50 hover:shadow-md' }}"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $currentView === 'equipment_drivers' ? 'bg-white/15 text-white' : 'bg-indigo-100 text-indigo-800 group-hover:bg-indigo-700 group-hover:text-white transition-colors' }}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-base sm:text-lg font-black leading-tight">إجمالي المعدات والسائقين</p>
                        <p class="mt-1 text-xs font-bold {{ $currentView === 'equipment_drivers' ? 'text-indigo-100' : 'text-indigo-900/75' }}">ربط الأنواع بالسائقين وبدون سائق والمتعطل</p>
                    </div>
                </div>
                <div class="shrink-0 rounded-2xl px-3.5 py-2 text-center {{ $currentView === 'equipment_drivers' ? 'bg-white/15 text-white' : 'bg-indigo-100 text-indigo-900' }}">
                    <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format((int) (($equipmentDriversSummary['totals']['company_drivers'] ?? 0) + ($equipmentDriversSummary['totals']['external_drivers'] ?? 0))) }}</p>
                    <p class="mt-1 text-[11px] font-extrabold {{ $currentView === 'equipment_drivers' ? 'text-indigo-100' : 'text-indigo-800' }}">سائق</p>
                </div>
            </a>
        </section>

        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث بنوع المعدة، الفرع، الموقع، حالة المعدة، أو الملاحظات..."
            :create-model="\App\Models\EquipmentDistribution::class"
            create-label="إضافة معدة للتوزيعة"
            :show-import-purge="true"
        >
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">نوع المعدة</label>
                <select x-model="filters.equipment_type" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الأنواع</option>
                    @foreach ($options['equipment_types'] as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

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
                <label class="block text-[11px] font-bold text-slate-600 mb-1">الموقع</label>
                <select x-model="filters.site_location" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المواقع</option>
                    @foreach ($options['site_locations'] as $loc)
                        <option value="{{ $loc }}">{{ $loc }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">حالة المعدة</label>
                <select x-model="filters.equipment_status" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الحالات</option>
                    @foreach ($options['equipment_statuses'] as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>
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

        {{-- Main Table / Summary Container --}}
        <section class="relative overflow-hidden rounded-2xl border-2 border-slate-900 bg-white shadow-sm">
            <div x-show="loading" x-cloak class="absolute inset-0 z-20 flex items-center justify-center bg-white/75 backdrop-blur-xs">
                <div class="flex items-center gap-2.5 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-lg">
                    <span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                    <span>جاري تحديث البيانات...</span>
                </div>
            </div>

            <div x-ref="tableContainer" @click="handleTableClick($event)">
                @if ($currentView === 'summary')
                    @include('modules.equipment-distributions._summary', ['summary' => $summary])
                @elseif ($currentView === 'site_summary')
                    @include('modules.equipment-distributions._site_summary', ['siteSummary' => $siteSummary])
                @elseif ($currentView === 'equipment_drivers')
                    @include('modules.equipment-distributions._equipment_drivers', ['equipmentDriversSummary' => $equipmentDriversSummary])
                @else
                    @include('modules.equipment-distributions._table', ['records' => $records])
                @endif
            </div>
        </section>

        {{-- Create / Edit Drawer --}}
        <x-module.drawer create-title="إضافة معدة لتوزيعة المعدات" edit-title="تعديل بيانات توزيعة المعدة">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نوع المعدة <span class="text-rose-600">*</span></label>
                    <input type="text" x-model="form.equipment_type" required placeholder="مثال: حفارات / قلابات / فرادة إسفلتية" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">كود المعدة (اختياري)</label>
                    <input type="text" x-model="form.equipment_code" dir="ltr" placeholder="مثال: EX_000012" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الفرع</label>
                    <input type="text" x-model="form.branch" placeholder="مثال: نجران / المدينة / أبها / مكة" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الموقع</label>
                    <input type="text" x-model="form.site_location" placeholder="مثال: مشروع أحد رفيدة / الورشة المركزية بنجران" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">حالة المعدة</label>
                    <input type="text" x-model="form.equipment_status" placeholder="مثال: تم تشغيلها ساعة أو أكثر / متعطلة في الورشة / متوقفة بدون عمل" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">ملاحظة</label>
                    <textarea x-model="form.notes" rows="2" placeholder="مثال: بدون عمل فعلي / تقييد آلي للمعدة في الورشة" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                </div>
            </div>
        </x-module.drawer>

        <x-module.import-modal
            :action="route('equipment-distributions.import.direct')"
            :template-url="route('equipment-distributions.import.template')"
            title="استيراد ملف توزيعة المعدات"
        />

        <x-module.purge-modal
            :action="route('equipment-distributions.purge-all')"
            module-name="توزيعة المعدات"
        />
    </div>
@endsection

