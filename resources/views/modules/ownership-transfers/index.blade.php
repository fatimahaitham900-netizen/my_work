@extends('layouts.app')

@section('title', 'نقل ملكيات المعدات')
@section('page_title', 'نقل ملكيات المعدات')
@section('page_subtitle', 'بيان المعدات المطلوب نقل ملكيتها، أرقام الهياكل، الفروع، وحالة البطاقات الجمركية')

@section('content')
    @php
        $moduleConfig = [
            'baseUrl' => url('/ownership-transfers'),
            'storeUrl' => route('ownership-transfers.store'),
            'exportBaseUrl' => route('ownership-transfers.export'),
            'initialExportUrl' => route('ownership-transfers.export', array_filter($filters)),
            'initialFilters' => [
                'search' => $filters['search'] ?? '',
                'branch' => $filters['branch'] ?? '',
                'status' => $filters['status'] ?? '',
                'focus' => $filters['focus'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            'defaultFilters' => [
                'search' => '',
                'branch' => '',
                'status' => '',
                'focus' => '',
                'date_from' => '',
                'date_to' => '',
            ],
            'initialKpis' => $kpis,
            'defaultForm' => [
                'equipment_code' => '',
                'equipment_type' => '',
                'branch' => '',
                'chassis_number' => '',
                'current_owner' => '',
                'target_owner' => 'الإدارة العامة للمعدات',
                'request_date' => now()->format('Y-m-d'),
                'expected_date' => now()->addDays(7)->format('Y-m-d'),
                'actual_date' => '',
                'status' => 'pending',
                'blocker_reason' => '',
                'notes' => '',
            ],
        ];
    @endphp

    <div
        x-data="{
            ...fleetModuleManager(@js($moduleConfig)),
            isAutoDelayed() {
                if (this.form.actual_date) return false;
                if (!this.form.expected_date) return false;
                const todayStr = new Date().toISOString().slice(0, 10);
                return this.form.expected_date < todayStr;
            },
            requiresBlockerReason() {
                return this.form.status === 'delayed' || this.isAutoDelayed();
            }
        }"
        class="space-y-5"
    >
        {{-- Action & Filter Toolbar --}}
        <x-module.toolbar
            search-placeholder="ابحث بكود المعدة، البيان، الفرع، رقم الهيكل، أو الملاحظات..."
            :create-model="\App\Models\OwnershipTransfer::class"
            :show-import-purge="true"
        >
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">الفرع</label>
                <select
                    x-model="filters.branch"
                    @change="applyFilters()"
                    class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:border-slate-900 focus:outline-none"
                >
                    <option value="">جميع الفروع</option>
                    @foreach ($branches as $branchName)
                        <option value="{{ $branchName }}">{{ $branchName }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">الحالة</label>
                <select
                    x-model="filters.status"
                    @change="applyFilters()"
                    class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:border-slate-900 focus:outline-none"
                >
                    <option value="">جميع الحالات</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">التاريخ المتوقع (من)</label>
                <input
                    type="date"
                    x-model="filters.date_from"
                    @change="applyFilters()"
                    class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-900 focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">التاريخ المتوقع (إلى)</label>
                <input
                    type="date"
                    x-model="filters.date_to"
                    @change="applyFilters()"
                    class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-900 focus:outline-none"
                >
            </div>
        </x-module.toolbar>

        {{-- Ruled Data Table Container --}}
        <div class="relative overflow-hidden rounded-2xl border-2 border-slate-900 bg-white shadow-md">
            <div
                x-show="loadingTable"
                x-cloak
                class="absolute inset-0 z-20 flex items-center justify-center bg-white/70 backdrop-blur-[1px]"
            >
                <span class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-lg">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span>جاري تحديث البيانات...</span>
                </span>
            </div>

            <div x-ref="tableContainer">
                @include('modules.ownership-transfers._table', ['records' => $records])
            </div>
        </div>

        {{-- Slide-Over Create/Edit Drawer --}}
        <x-module.drawer
            create-title="إضافة سجل نقل ملكية معدة"
            edit-title="تعديل سجل نقل الملكية"
        >
            <div
                x-show="isAutoDelayed()"
                x-cloak
                class="rounded-2xl border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-900"
            >
                <p class="font-bold">تصنيف تلقائي: متأخر</p>
                <p class="mt-0.5">بما أن التاريخ المتوقع قد مضى ولم يُسجّل تاريخ إنجاز فعلي، سيُصنّف السجل تلقائياً كـ «متأخر» عند الحفظ.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">كود المعدة</label>
                    <input type="text" x-model="form.equipment_code" placeholder="مثال: BK-005" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('equipment_code')" x-text="firstError('equipment_code')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">البيان (نوع المعدة)</label>
                    <input type="text" x-model="form.equipment_type" placeholder="مثال: باك هو لودر / بوبكات / كرين" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('equipment_type')" x-text="firstError('equipment_type')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">الفرع</label>
                    <input type="text" list="branch-presets" x-model="form.branch" placeholder="مثال: المدينة المنوره" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <datalist id="branch-presets">
                        <option value="المدينة المنوره"></option>
                        <option value="الرياض"></option>
                        <option value="الجوف"></option>
                        <option value="نجران"></option>
                        <option value="الحائر"></option>
                    </datalist>
                    <p x-show="firstError('branch')" x-text="firstError('branch')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">رقم الهيكل</label>
                    <input type="text" dir="ltr" x-model="form.chassis_number" placeholder="مثال: CTA0422EAMAW01178" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('chassis_number')" x-text="firstError('chassis_number')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">المالك / الجهة الحالية</label>
                    <input type="text" x-model="form.current_owner" placeholder="مثال: فرع المدينة المنوره" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('current_owner')" x-text="firstError('current_owner')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">المالك / الجهة المستلمة</label>
                    <input type="text" x-model="form.target_owner" placeholder="مثال: الإدارة العامة للمعدات" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('target_owner')" x-text="firstError('target_owner')" class="mt-1 text-xs text-rose-600"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">تاريخ الطلب</label>
                    <input type="date" x-model="form.request_date" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('request_date')" x-text="firstError('request_date')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">التاريخ المتوقع</label>
                    <input type="date" x-model="form.expected_date" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('expected_date')" x-text="firstError('expected_date')" class="mt-1 text-xs text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">تاريخ الإنجاز الفعلي</label>
                    <input type="date" x-model="form.actual_date" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-900 focus:outline-none">
                    <p x-show="firstError('actual_date')" x-text="firstError('actual_date')" class="mt-1 text-xs text-rose-600"></p>
                </div>
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

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">سبب التأخير / التعثر</label>
                <textarea
                    rows="2"
                    x-model="form.blocker_reason"
                    placeholder="مثال: لا يوجد بطاقة جمركية..."
                    class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"
                ></textarea>
                <p x-show="firstError('blocker_reason')" x-text="firstError('blocker_reason')" class="mt-1 text-xs text-rose-600"></p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">ملاحظات</label>
                <textarea rows="2" x-model="form.notes" placeholder="مثال: لا يوجد بطاقة جمركية..." class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                <p x-show="firstError('notes')" x-text="firstError('notes')" class="mt-1 text-xs text-rose-600"></p>
            </div>
        </x-module.drawer>

        <x-module.details-modal />
        <x-module.delete-modal />
        <x-module.import-modal
            :action="route('ownership-transfers.import.direct')"
            :template-url="route('ownership-transfers.import.template')"
            title="استيراد وإسقاط بيانات نقل ملكيات المعدات"
        />
        <x-module.purge-modal
            :action="route('ownership-transfers.purge-all')"
            module-name="نقل ملكيات المعدات"
            :count="$kpis['total'] ?? 0"
        />
    </div>
@endsection
