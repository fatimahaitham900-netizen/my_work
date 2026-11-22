@props([
    'createTitle' => 'إضافة سجل جديد',
    'editTitle' => 'تعديل السجل',
    'subtitle' => 'يمكنك تعديل أو حفظ أي حقل بحرية دون حقول إلزامية، وسيتم توثيق التعديلات في سجل التدقيق تلقائياً',
])

<div
    x-show="drawerOpen"
    x-cloak
    @keydown.escape.window="if (drawerOpen) closeDrawer()"
    class="fixed inset-0 z-50 overflow-hidden"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="drawerOpen"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeDrawer()"
        class="fixed inset-0 bg-slate-950/50 backdrop-blur-xs"
    ></div>

    {{-- Slide-over Panel (from end edge in RTL) --}}
    <div class="fixed inset-y-0 end-0 flex max-w-full ps-10">
        <div
            x-show="drawerOpen"
            x-transition:enter="transform transition ease-out duration-250"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="w-screen max-w-xl"
        >
            <form
                @submit.prevent="submitForm()"
                class="flex h-full flex-col bg-white shadow-2xl border-s border-slate-200"
            >
                {{-- Header --}}
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50/80 px-6 py-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900" x-text="drawerMode === 'edit' ? '{{ $editTitle }}' : '{{ $createTitle }}'"></h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $subtitle }}</p>
                    </div>
                    <button
                        type="button"
                        @click="closeDrawer()"
                        class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-200/70 hover:text-slate-700 transition cursor-pointer"
                        aria-label="إغلاق"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-5">
                    <div x-show="loadingEdit" class="flex items-center justify-center py-16 text-sm text-slate-500 gap-2">
                        <svg class="h-5 w-5 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span>جاري تحميل بيانات السجل...</span>
                    </div>

                    <div x-show="!loadingEdit" class="space-y-4">
                        {{ $slot }}
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50/80 px-6 py-4">
                    <button
                        type="button"
                        @click="closeDrawer()"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                    >
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        :disabled="submitting || loadingEdit"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 transition cursor-pointer"
                    >
                        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-text="drawerMode === 'edit' ? 'حفظ التعديلات' : 'حفظ السجل'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

