@extends('layouts.app')

@section('title', 'السائقين')
@section('page_title', 'السائقين')
@section('page_subtitle', 'بيانات السائقين والرواتب والمهن والجنسيات مع ربط تلقائي للفرع والموقع من صفحة توزيعة المعدات')

@section('content')
    @php
        $currentView = $activeView ?? 'table';
        $moduleConfig = [
            'baseUrl' => url('/drivers'),
            'storeUrl' => route('drivers.store'),
            'exportBaseUrl' => route('drivers.export'),
            'initialExportUrl' => route('drivers.export', array_filter($filters)),
            'initialFilters' => [
                'view' => $currentView,
                'search' => $filters['search'] ?? '',
                'branch' => $filters['branch'] ?? '',
                'site_location' => $filters['site_location'] ?? '',
                'job_title' => $filters['job_title'] ?? '',
                'nationality' => $filters['nationality'] ?? '',
                'sponsor' => $filters['sponsor'] ?? '',
            ],
            'defaultFilters' => [
                'view' => $currentView,
                'search' => '',
                'branch' => '',
                'site_location' => '',
                'job_title' => '',
                'nationality' => '',
                'sponsor' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'equipment_code' => '',
                'branch' => '',
                'site_location' => '',
                'driver_name' => '',
                'employee_id' => '',
                'job_title' => '',
                'nationality' => '',
                'salary' => '0',
                'sponsor' => 'كفاله شركة',
                'notes' => '',
            ],
        ];
    @endphp

    <div x-data="fleetModuleManager(@js($moduleConfig))" class="space-y-5">
        {{-- View Switcher Cards: جدول السائقين | ملخص السائقين | ملخص السائقين حسب نوع المعدة --}}
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <a
                href="{{ route('drivers.index', array_merge(request()->except('view', 'page'), ['view' => 'table'])) }}"
                class="group flex items-center justify-between gap-3 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ $currentView === 'table' ? 'border-slate-950 bg-slate-950 text-white shadow-lg ring-4 ring-slate-950/15' : 'border-slate-300 bg-white text-slate-900 shadow-xs hover:border-slate-900 hover:bg-slate-50 hover:shadow-md' }}"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $currentView === 'table' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-900 group-hover:bg-slate-900 group-hover:text-white transition-colors' }}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-base sm:text-lg font-black leading-tight">جدول السائقين</p>
                        <p class="mt-1 text-xs font-bold {{ $currentView === 'table' ? 'text-slate-300' : 'text-slate-500' }}">مربوط تلقائياً بتوزيعة المعدات حسب الكود</p>
                    </div>
                </div>
                <div class="shrink-0 rounded-2xl px-3.5 py-2 text-center {{ $currentView === 'table' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-950' }}">
                    <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format((int) ($kpis['total'] ?? 0)) }}</p>
                    <p class="mt-1 text-[11px] font-extrabold {{ $currentView === 'table' ? 'text-slate-200' : 'text-slate-500' }}">سائق</p>
                </div>
            </a>

            <a
                href="{{ route('drivers.index', array_merge(request()->except('view', 'page'), ['view' => 'summary'])) }}"
                class="group flex items-center justify-between gap-3 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ $currentView === 'summary' ? 'border-emerald-800 bg-emerald-700 text-white shadow-lg ring-4 ring-emerald-700/15' : 'border-emerald-300 bg-emerald-50/40 text-slate-900 shadow-xs hover:border-emerald-700 hover:bg-emerald-50 hover:shadow-md' }}"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $currentView === 'summary' ? 'bg-white/15 text-white' : 'bg-emerald-100 text-emerald-800 group-hover:bg-emerald-700 group-hover:text-white transition-colors' }}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-base sm:text-lg font-black leading-tight">ملخص السائقين</p>
                        <p class="mt-1 text-xs font-bold {{ $currentView === 'summary' ? 'text-emerald-100' : 'text-emerald-900/75' }}">حسب الفرع والجنسية والكفالة والمهنة</p>
                    </div>
                </div>
                <div class="shrink-0 rounded-2xl px-3.5 py-2 text-center {{ $currentView === 'summary' ? 'bg-white/15 text-white' : 'bg-emerald-100 text-emerald-900' }}">
                    <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format(count($options['branches'] ?? [])) }}</p>
                    <p class="mt-1 text-[11px] font-extrabold {{ $currentView === 'summary' ? 'text-emerald-100' : 'text-emerald-800' }}">فرع</p>
                </div>
            </a>

            <a
                href="{{ route('drivers.index', array_merge(request()->except('view', 'page'), ['view' => 'equipment_type_summary'])) }}"
                class="group flex items-center justify-between gap-3 rounded-2xl border-2 p-4 sm:p-5 transition-all {{ $currentView === 'equipment_type_summary' ? 'border-sky-800 bg-sky-700 text-white shadow-lg ring-4 ring-sky-700/15' : 'border-sky-300 bg-sky-50/40 text-slate-900 shadow-xs hover:border-sky-700 hover:bg-sky-50 hover:shadow-md' }}"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $currentView === 'equipment_type_summary' ? 'bg-white/15 text-white' : 'bg-sky-100 text-sky-800 group-hover:bg-sky-700 group-hover:text-white transition-colors' }}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-base sm:text-lg font-black leading-tight">ملخص السائقين حسب نوع المعدة</p>
                        <p class="mt-1 text-xs font-bold {{ $currentView === 'equipment_type_summary' ? 'text-sky-100' : 'text-sky-900/75' }}">توزيع السائقين والرواتب على أنواع المعدات</p>
                    </div>
                </div>
                <div class="shrink-0 rounded-2xl px-3.5 py-2 text-center {{ $currentView === 'equipment_type_summary' ? 'bg-white/15 text-white' : 'bg-sky-100 text-sky-900' }}">
                    <p class="text-2xl sm:text-3xl font-black tabular-nums leading-none">{{ number_format(count($summary['by_equipment_type'] ?? [])) }}</p>
                    <p class="mt-1 text-[11px] font-extrabold {{ $currentView === 'equipment_type_summary' ? 'text-sky-100' : 'text-sky-800' }}">نوع معدة</p>
                </div>
            </a>
        </section>

        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث بكود المعدة، اسم السائق، الرقم الوظيفي، المهنة، الجنسية، الفرع، أو الموقع..."
            :create-model="\App\Models\Driver::class"
            create-label="إضافة سائق جديد"
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
                <label class="block text-[11px] font-bold text-slate-600 mb-1">الموقع</label>
                <select x-model="filters.site_location" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المواقع</option>
                    @foreach ($options['site_locations'] as $loc)
                        <option value="{{ $loc }}">{{ $loc }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">المهنة</label>
                <select x-model="filters.job_title" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المهن</option>
                    @foreach ($options['job_titles'] as $job)
                        <option value="{{ $job }}">{{ $job }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">الجنسية</label>
                <select x-model="filters.nationality" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الجنسيات</option>
                    @foreach ($options['nationalities'] as $nat)
                        <option value="{{ $nat }}">{{ $nat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">الكفيل</label>
                <select x-model="filters.sponsor" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الكفلاء</option>
                    @foreach ($options['sponsors'] as $sponsor)
                        <option value="{{ $sponsor }}">{{ $sponsor }}</option>
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
                    @include('modules.drivers._summary', ['summary' => $summary])
                @elseif ($currentView === 'equipment_type_summary')
                    @include('modules.drivers._equipment_type_summary', ['summary' => $summary])
                @else
                    @include('modules.drivers._table', ['records' => $records, 'filteredSalaryTotal' => $filteredSalaryTotal])
                @endif
            </div>
        </section>

        {{-- Create / Edit Drawer --}}
        <x-module.drawer create-title="إضافة سائق جديد" edit-title="تعديل بيانات السائق">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">كود المعدة</label>
                    <input type="text" x-model="form.equipment_code" dir="ltr" placeholder="مثال: AP_000008" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">اسم السائق <span class="text-rose-600">*</span></label>
                    <input type="text" x-model="form.driver_name" required placeholder="اسم السائق الكامل" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الرقم الوظيفي</label>
                    <input type="text" x-model="form.employee_id" dir="ltr" placeholder="مثال: 3903" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">المهنة</label>
                    <input type="text" x-model="form.job_title" placeholder="مثال: سائق فرادة اسفلت" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الجنسية</label>
                    <input type="text" x-model="form.nationality" placeholder="مثال: India / Yemen / Pakistan" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الراتب</label>
                    <input type="number" step="0.01" min="0" x-model="form.salary" placeholder="2242" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm tabular-nums focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الكفيل</label>
                    <input type="text" x-model="form.sponsor" placeholder="كفاله شركة" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الفرع (يُسحب تلقائياً من توزيعة المعدات)</label>
                    <input type="text" x-model="form.branch" placeholder="يُترك فارغاً للربط التلقائي" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">الموقع (يُسحب تلقائياً من توزيعة المعدات)</label>
                    <input type="text" x-model="form.site_location" placeholder="يُترك فارغاً للربط التلقائي" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
            </div>
        </x-module.drawer>

        <x-module.import-modal
            :action="route('drivers.import.direct')"
            :template-url="route('drivers.import.template')"
            title="استيراد ملف السائقين"
        />

        <x-module.purge-modal
            :action="route('drivers.purge-all')"
            module-name="السائقين"
        />
    </div>
@endsection

