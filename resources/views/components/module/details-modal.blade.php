<div
    x-show="detailsOpen"
    x-cloak
    @keydown.escape.window="if (detailsOpen) detailsOpen = false"
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
>
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="detailsOpen"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            @click="detailsOpen = false"
            class="fixed inset-0 bg-slate-950/55 backdrop-blur-xs"
        ></div>

        <div
            x-show="detailsOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="relative w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
        >
            {{-- Loading --}}
            <div x-show="loadingDetails" class="flex items-center justify-center gap-2 py-20 text-sm text-slate-500">
                <svg class="h-5 w-5 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span>جاري تحميل التفاصيل وسجل التعديلات...</span>
            </div>

            {{-- Content --}}
            <template x-if="!loadingDetails && detailsData">
                <div>
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50/90 px-6 py-5">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900" x-text="detailsData.title"></h3>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="detailsData.status_badge"
                                    x-text="detailsData.status_label"
                                ></span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500" x-text="detailsData.subtitle"></p>
                        </div>
                        <button
                            type="button"
                            @click="detailsOpen = false"
                            class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-200/70 hover:text-slate-700 cursor-pointer"
                            aria-label="إغلاق"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[75vh] overflow-y-auto px-6 py-5 space-y-6">
                        {{-- Record Details Grid --}}
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <template x-for="(field, idx) in detailsData.fields" :key="idx">
                                <div
                                    :class="field.full ? 'sm:col-span-2' : ''"
                                    class="rounded-xl border border-slate-100 bg-slate-50/70 px-4 py-3"
                                >
                                    <p class="text-[11px] font-medium text-slate-500" x-text="field.label"></p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900 break-words" x-text="field.value"></p>
                                </div>
                            </template>
                        </div>

                        {{-- Linked Waiting Drivers (if present, Module 4) --}}
                        <template x-if="detailsData.linked_waiting_stoppages && detailsData.linked_waiting_stoppages.length > 0">
                            <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4">
                                <p class="text-xs font-bold text-amber-900">سائقون متوقفون مرتبطون بهذا العطل حالياً</p>
                                <ul class="mt-2 space-y-1.5 text-xs text-amber-800">
                                    <template x-for="drv in detailsData.linked_waiting_stoppages" :key="drv.id">
                                        <li class="flex items-center justify-between">
                                            <span x-text="drv.driver_name + ' (' + drv.employee_id + ')'"></span>
                                            <span class="font-bold tabular-nums" x-text="'متوقف ' + drv.idle_days + ' يوم'"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>

                        {{-- Audit History Timeline --}}
                        <div class="border-t border-slate-200 pt-5">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-bold text-slate-900">سجل التعديلات والتدقيق</h4>
                                <span
                                    x-show="detailsData.can_view_audit_logs"
                                    class="text-xs text-slate-500 tabular-nums"
                                    x-text="(detailsData.audit_logs || []).length + ' عملية مسجلة'"
                                ></span>
                            </div>

                            <template x-if="!detailsData.can_view_audit_logs">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
                                    الاطلاع على تفاصيل سجل التدقيق متاح لصلاحيات مدير النظام ومدير العمليات فقط.
                                </div>
                            </template>

                            <template x-if="detailsData.can_view_audit_logs && (!detailsData.audit_logs || detailsData.audit_logs.length === 0)">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-500">
                                    لا توجد عمليات تعديل مسجلة لهذا السجل بعد.
                                </div>
                            </template>

                            <template x-if="detailsData.can_view_audit_logs && detailsData.audit_logs && detailsData.audit_logs.length > 0">
                                <div class="space-y-3">
                                    <template x-for="log in detailsData.audit_logs" :key="log.id">
                                        <div class="rounded-2xl border border-slate-200/80 bg-white p-4">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                                        :class="log.event_badge"
                                                        x-text="log.event_label"
                                                    ></span>
                                                    <span class="text-xs font-semibold text-slate-800" x-text="log.user_name"></span>
                                                </div>
                                                <div class="flex items-center gap-2 text-[11px] text-slate-400 tabular-nums">
                                                    <span x-text="log.created_at"></span>
                                                    <span>•</span>
                                                    <span class="font-mono" x-text="log.ip_address"></span>
                                                </div>
                                            </div>

                                            <template x-if="log.changes && log.changes.length > 0">
                                                <div class="mt-3 space-y-1.5 border-t border-slate-100 pt-2.5 text-xs">
                                                    <template x-for="(change, cIdx) in log.changes" :key="cIdx">
                                                        <div class="flex flex-wrap items-center gap-1.5 text-slate-600">
                                                            <span class="font-semibold text-slate-800" x-text="change.field + ':'"></span>
                                                            <template x-if="log.event === 'updated'">
                                                                <span class="inline-flex items-center gap-1.5">
                                                                    <span class="rounded bg-rose-50 px-1.5 py-0.5 text-rose-700 line-through" x-text="change.old"></span>
                                                                    <span>←</span>
                                                                    <span class="rounded bg-emerald-50 px-1.5 py-0.5 font-semibold text-emerald-700" x-text="change.new"></span>
                                                                </span>
                                                            </template>
                                                            <template x-if="log.event !== 'updated'">
                                                                <span class="text-slate-700" x-text="change.new !== '—' ? change.new : change.old"></span>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-end border-t border-slate-200 bg-slate-50/80 px-6 py-3.5">
                        <button
                            type="button"
                            @click="detailsOpen = false"
                            class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800 cursor-pointer"
                        >
                            إغلاق
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

