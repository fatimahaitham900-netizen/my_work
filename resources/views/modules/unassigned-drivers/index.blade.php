@extends('layouts.app')

@section('title', 'سائقين بدون معدات')
@section('page_title', 'سائقين بدون معدات')
@section('page_subtitle', 'بيان السائقين غير المكلّفين بمعدات، مهنهم، مواقعهم، وملاحظات العقود والجاهزية')

@section('content')
    @php
        $moduleConfig = [
            'baseUrl' => url('/unassigned-drivers'),
            'storeUrl' => route('unassigned-drivers.store'),
            'exportBaseUrl' => route('unassigned-drivers.export'),
            'initialExportUrl' => route('unassigned-drivers.export', array_filter($filters)),
            'initialFilters' => [
                'search' => $filters['search'] ?? '',
                'location' => $filters['location'] ?? '',
                'status' => $filters['status'] ?? '',
                'license_state' => $filters['license_state'] ?? '',
                'focus' => $filters['focus'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            'defaultFilters' => [
                'search' => '',
                'location' => '',
                'status' => '',
                'license_state' => '',
                'focus' => '',
                'date_from' => '',
                'date_to' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'driver_name' => '',
                'employee_id' => '',
                'job_title' => '',
                'location' => '',
                'license_type' => 'رخصة معدات ثقيلة',
                'license_expiry' => now()->addYear()->format('Y-m-d'),
                'unassigned_since' => now()->format('Y-m-d'),
                'status' => 'ready',
                'notes' => '',
            ],
        ];
    @endphp

    <div x-data="fleetModuleManager(@js($moduleConfig))" class="space-y-5">
        {{-- KPI Mini-Header --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium text-slate-500">إجمالي السائقين</p>
                <p class="mt-1.5 text-2xl font-bold text-slate-900 tabular-nums" x-text="kpis.total">{{ $kpis['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200/90 bg-amber-50/60 p-4 shadow-xs">
                <p class="text-xs font-medium text-amber-800">جاهزون للتكليف</p>
                <p class="mt-1.5 text-2xl font-bold text-amber-800 tabular-nums" x-text="kpis.ready">{{ $kpis['ready'] }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200/90 bg-rose-50/60 p-4 shadow-xs">
                <p class="text-xs font-medium text-rose-700">رخص منتهية / قاربت</p>
                <p class="mt-1.5 text-2xl font-bold text-rose-700 tabular-nums" x-text="kpis.license_alerts">{{ $kpis['license_alerts'] }}</p>
            </div>
            <div class="rounded-2xl border border-sky-200/90 bg-sky-50/60 p-4 shadow-xs">
                <p class="text-xs font-medium text-sky-800">إجازة / تحت التقييم</p>
                <p class="mt-1.5 text-2xl font-bold text-sky-800 tabular-nums" x-text="kpis.other">{{ $kpis['other'] }}</p>
            </div>
        </section>

        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث باسم السائق، المهنة، الرقم الوظيفي، الموقع، أو الملاحظات..."
            :create-model="\App\Models\UnassignedDriver::class"
        >
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">الموقع</label>
                <select x-model="filters.location" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع المواقع</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc }}">{{ $loc }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">حالة السائق</label>
                <select x-model="filters.status" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">جميع الحالات</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">صلاحية الرخصة</label>
                <select x-model="filters.license_state" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:border-slate-900 focus:outline-none">
                    <option value="">الكل</option>
                    <option value="expired">رخصة منتهية</option>
                    <option value="expiring_soon">قاربت على الانتهاء (≤ 30 يوم)</option>
                    <option value="valid">سارية (&gt; 30 يوم)</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">تاريخ التوقف (من)</label>
                <input type="date" x-model="filters.date_from" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-900 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">تاريخ التوقف (إلى)</label>
                <input type="date" x-model="filters.date_to" @change="applyFilters()" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-900 focus:outline-none">
            </div>
        </x-module.toolbar>

        {{-- Ruled Data Table --}}
        <div class="relative overflow-hidden rounded-2xl border border-slate-300/80 bg-white shadow-sm">
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
                @include('modules.unassigned-drivers._table', ['records' => $records, 'statuses' => $statuses])
            </div>
        </div>

        {{-- Slide-Over Create/Edit Drawer --}}
        <x-module.drawer
            create-title="إضافة سائق بدون معدة"
            edit-title="تعديل بيانات السائق"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">اسم السائق</label>
                    <input type="text" x-model="form.driver_name" placeholder="مثال: ابو الوفا محمد ابو الوفا احمد" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('driver_name')" x-text="firstError('driver_name')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">الرقم الوظيفي</label>
                    <input type="text" dir="ltr" x-model="form.employee_id" placeholder="مثال: 2312" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('employee_id')" x-text="firstError('employee_id')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">المهنة</label>
                    <input type="text" list="unassigned-job-presets" x-model="form.job_title" placeholder="مثال: سائق شيول / سائق تريلا" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="unassigned-job-presets">
                        <option value="سائق شيول"></option>
                        <option value="سائق بوبكات"></option>
                        <option value="سائق بلدوزر"></option>
                        <option value="سائق تريلا"></option>
                        <option value="سائق قلاب"></option>
                    </datalist>
                    <p x-show="firstError('job_title')" x-text="firstError('job_title')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">الموقع</label>
                    <input type="text" list="unassigned-location-presets" x-model="form.location" placeholder="مثال: نجران / المدينة / حائل" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="unassigned-location-presets">
                        <option value="نجران"></option>
                        <option value="المدينة"></option>
                        <option value="حائل"></option>
                        <option value="مكة"></option>
                        <option value="الرياض"></option>
                    </datalist>
                    <p x-show="firstError('location')" x-text="firstError('location')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">نوع الرخصة</label>
                    <input type="text" x-model="form.license_type" placeholder="مثال: رخصة معدات ثقيلة" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('license_type')" x-text="firstError('license_type')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">تاريخ انتهاء الرخصة</label>
                    <input type="date" x-model="form.license_expiry" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('license_expiry')" x-text="firstError('license_expiry')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">تاريخ التوقف</label>
                    <input type="date" x-model="form.unassigned_since" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('unassigned_since')" x-text="firstError('unassigned_since')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">الحالة</label>
                    <select x-model="form.status" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <p x-show="firstError('status')" x-text="firstError('status')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">ملاحظات</label>
                <textarea rows="3" x-model="form.notes" placeholder="ملاحظات الجاهزية، انتهاء العقد، أو خطة التكليف..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                <p x-show="firstError('notes')" x-text="firstError('notes')" class="mt-1 text-xs text-rose-600"></p>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />
    </div>
@endsection
