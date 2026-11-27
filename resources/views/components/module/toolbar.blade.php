@props([
    'searchPlaceholder' => 'بحث فوري...',
    'createModel' => null,
    'createLabel' => 'إضافة سجل جديد',
    'showImportPurge' => false,
])

<div class="rounded-2xl border border-slate-200/90 bg-white p-3.5 sm:p-5 shadow-xs space-y-3.5 sm:space-y-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        {{-- Search Input --}}
        <div class="relative w-full lg:flex-1 lg:max-w-md">
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-slate-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </span>
            <input
                type="search"
                x-model="filters.search"
                @input="onSearchInput()"
                placeholder="{{ $searchPlaceholder }}"
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/70 py-2.5 ps-10 pe-4 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:bg-white focus:ring-2 focus:ring-slate-900/10 focus:outline-none transition"
            >
        </div>

        {{-- Primary & Secondary CTAs --}}
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button
                type="button"
                x-show="hasActiveFilters()"
                x-cloak
                @click="resetFilters()"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span>إعادة ضبط الفلاتر</span>
            </button>

            <a
                :href="exportUrl"
                class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50/80 px-4 py-2.5 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>تصدير Excel</span>
            </a>

            @if ($createModel && auth()->user()?->can('create', $createModel))
                <button
                    type="button"
                    @click="openCreate()"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 transition cursor-pointer"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>{{ $createLabel }}</span>
                </button>
            @endif

            @if ($showImportPurge && $createModel && auth()->user()?->can('create', $createModel))
                <button
                    type="button"
                    @click="$dispatch('open-purge-modal')"
                    class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50/90 px-4 py-2.5 text-xs font-bold text-rose-800 hover:bg-rose-100 transition cursor-pointer"
                >
                    <svg class="h-4 w-4 text-rose-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    <span>حذف جميع البيانات</span>
                </button>

                <button
                    type="button"
                    @click="$dispatch('open-import-modal')"
                    class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50/90 px-4 py-2.5 text-xs font-bold text-indigo-900 hover:bg-indigo-100 transition cursor-pointer"
                >
                    <svg class="h-4 w-4 text-indigo-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    <span>استيراد وإسقاط (CSV/Excel)</span>
                </button>
            @endif

            {{ $actions ?? '' }}
        </div>
    </div>

    {{-- Filter Controls Row --}}
    <div class="grid grid-cols-1 gap-3 border-t border-slate-100 pt-3.5 sm:grid-cols-2 lg:grid-cols-5">
        {{ $slot }}
    </div>

    {{-- Active Deep-Link Focus Indicator --}}
    <div
        x-show="filters.focus"
        x-cloak
        class="flex items-center justify-between rounded-xl border border-rose-200 bg-rose-50/70 px-3.5 py-2 text-xs text-rose-800"
    >
        <span class="font-semibold">
            تصفية مسبقة نشطة:
            <span x-text="filters.focus === 'critical' ? 'الحالات الحرجة / المتأخرة فقط' : 'الانحرافات النشطة فقط'"></span>
        </span>
        <button
            type="button"
            @click="filters.focus = ''; applyFilters()"
            class="font-bold underline hover:text-rose-950 cursor-pointer"
        >
            عرض جميع السجلات
        </button>
    </div>
</div>

