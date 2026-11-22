<div
    x-show="deleteOpen"
    x-cloak
    @keydown.escape.window="if (deleteOpen) deleteOpen = false"
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
>
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="deleteOpen"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            @click="deleteOpen = false"
            class="fixed inset-0 bg-slate-950/55 backdrop-blur-xs"
        ></div>

        <div
            x-show="deleteOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl"
        >
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-slate-900">تأكيد حذف السجل</h3>
                    <p class="mt-1.5 text-sm text-slate-600 leading-relaxed">
                        هل أنت متأكد من رغبتك في حذف السجل
                        <span class="font-bold text-slate-900" x-text="deleteTarget.label"></span>؟
                        سيتم أرشفة السجل (حذف ناعم) وتوثيق العملية في سجل التدقيق.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button
                    type="button"
                    @click="deleteOpen = false"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer"
                >
                    إلغاء
                </button>
                <button
                    type="button"
                    @click="executeDelete()"
                    :disabled="deleting"
                    class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50 cursor-pointer"
                >
                    <svg x-show="deleting" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span>تأكيد الحذف</span>
                </button>
            </div>
        </div>
    </div>
</div>

