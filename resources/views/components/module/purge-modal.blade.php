@props([
    'action',
    'moduleName',
    'count' => 0,
    'hiddenFields' => [],
])

<div
    x-show="purgeModalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
    @keydown.escape.window="purgeModalOpen = false"
>
    {{-- Backdrop --}}
    <div
        x-show="purgeModalOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs"
        @click="purgeModalOpen = false"
    ></div>

    {{-- Dialog Card --}}
    <div
        x-show="purgeModalOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full max-w-md rounded-3xl border border-rose-200 bg-white p-6 sm:p-7 shadow-2xl space-y-5"
    >
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-black text-slate-900">تأكيد حذف جميع البيانات</h3>
                <p class="mt-1.5 text-xs sm:text-sm leading-relaxed text-slate-600">
                    هل أنت متأكد من رغبتك في حذف جميع سجلات <strong class="text-slate-900">{{ $moduleName }}</strong> الحالية
                    @if ($count > 0)
                        (<span class="font-extrabold text-rose-700 tabular-nums">{{ $count }}</span> سجل)
                    @endif
                    ؟ سيتم تفريغ الجدول بالكامل لتتمكن من رفع الملف الجديد وإسقاطه بشكل نظيف.
                </p>
            </div>
        </div>

        <form
            method="POST"
            action="{{ parse_url($action, PHP_URL_PATH) ?: $action }}"
            x-data="{ purging: false }"
            @submit="purging = true"
            class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4"
        >
            @csrf

            @foreach ($hiddenFields as $fieldName => $fieldValue)
                @if ($fieldValue !== null && $fieldValue !== '')
                    <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
                @endif
            @endforeach

            <button
                type="button"
                @click="purgeModalOpen = false"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer"
            >
                إلغاء
            </button>

            <button
                type="submit"
                :disabled="purging"
                class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-xs sm:text-sm font-black text-white shadow-md hover:bg-rose-700 disabled:opacity-50 transition cursor-pointer"
            >
                <svg x-show="!purging" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                <svg x-show="purging" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span x-text="purging ? 'جاري حذف جميع البيانات...' : 'نعم، احذف جميع البيانات'"></span>
            </button>
        </form>
    </div>
</div>

