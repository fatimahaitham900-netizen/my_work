@props([
    'action',
    'templateUrl',
    'title' => 'رفع ملف التحديث وإسقاط البيانات',
    'badge' => 'تحديث مباشر بالملف',
    'hiddenFields' => [],
])

<div
    x-show="importModalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
    @keydown.escape.window="importModalOpen = false"
>
    {{-- Backdrop --}}
    <div
        x-show="importModalOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs"
        @click="importModalOpen = false"
    ></div>

    {{-- Dialog Card --}}
    <div
        x-show="importModalOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-2xl space-y-6"
    >
        {{-- Header --}}
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800 ring-1 ring-inset ring-emerald-600/20 mb-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    <span>{{ $badge }}</span>
                </div>
                <h3 class="text-xl font-black text-slate-900">{{ $title }}</h3>
                <p class="mt-1 text-xs sm:text-sm text-slate-500">
                    ارفع ملف (CSV أو Excel) وسيتم إسقاط وتحديث البيانات فوراً على الجدول أدناه مع حذف البيانات القديمة بالكامل.
                </p>
            </div>

            <button
                type="button"
                @click="importModalOpen = false"
                class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Upload Form --}}
        <form
            method="POST"
            action="{{ parse_url($action, PHP_URL_PATH) ?: $action }}"
            enctype="multipart/form-data"
            class="space-y-5"
            x-data="{
                fileName: '',
                fileSize: '',
                isDragging: false,
                submitting: false,
                handleFileSelect(files) {
                    if (files && files.length > 0) {
                        this.fileName = files[0].name;
                        this.fileSize = (files[0].size / 1024).toFixed(1) + ' KB';
                    }
                }
            }"
            @submit="submitting = true"
        >
            @csrf

            @foreach ($hiddenFields as $fieldName => $fieldValue)
                @if ($fieldValue !== null && $fieldValue !== '')
                    <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
                @endif
            @endforeach

            @if ($errors->has('file'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-semibold text-rose-800">
                    {{ $errors->first('file') }}
                </div>
            @endif

            {{-- Drag & Drop Area --}}
            <div
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop="isDragging = false"
                :class="isDragging ? 'border-emerald-600 bg-emerald-50/60 ring-4 ring-emerald-100' : (fileName ? 'border-emerald-500 bg-emerald-50/30' : 'border-slate-300 bg-slate-50/70 hover:bg-slate-50')"
                class="relative rounded-2xl border-2 border-dashed p-6 sm:p-8 text-center transition cursor-pointer"
            >
                <input
                    x-ref="fileInput"
                    type="file"
                    name="file"
                    accept=".csv,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                    class="absolute inset-0 z-20 h-full w-full opacity-0 cursor-pointer"
                    required
                    @change="handleFileSelect($event.target.files)"
                >

                <div class="pointer-events-none">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-800 shadow-sm mb-3">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>

                    <template x-if="!fileName">
                        <div>
                            <p class="text-sm font-black text-slate-900">اضغط لاختيار ملف أو اسحب الملف وأفلته هنا</p>
                            <p class="mt-1 text-xs text-slate-500">صيغ الملفات المدعومة: CSV، Excel (.xlsx, .xls) حتى 10 ميجابايت</p>
                        </div>
                    </template>

                    <template x-if="fileName">
                        <div class="space-y-1">
                            <p class="text-sm font-black text-emerald-900 flex items-center justify-center gap-1.5">
                                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                <span x-text="fileName"></span>
                            </p>
                            <p class="text-xs text-slate-500" x-text="'الحجم: ' + fileSize"></p>
                            <span class="inline-block mt-2 text-[11px] font-bold text-emerald-700 bg-emerald-100/70 px-2.5 py-0.5 rounded-full">تم اختيار الملف بنجاح (اضغط لتغييره)</span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Helper Link: Download Sample CSV Template --}}
            <div class="flex items-center justify-between rounded-xl bg-sky-50 px-4 py-3 text-xs text-sky-900 border border-sky-100">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-sky-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <span>هل تريد قالب جاهز لنفس الأعمدة؟</span>
                </div>
                <a
                    href="{{ $templateUrl }}"
                    class="font-black text-sky-700 hover:text-sky-950 underline inline-flex items-center gap-1"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>تحميل نموذج CSV</span>
                </a>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <button
                    type="button"
                    @click="importModalOpen = false"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                >
                    إلغاء
                </button>

                <button
                    type="submit"
                    :disabled="submitting"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs sm:text-sm font-black text-white shadow-md hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer"
                >
                    <svg x-show="!submitting" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span x-text="submitting ? 'جاري الإسقاط وتحديث الجدول...' : 'إسقاط وتحديث البيانات على الجدول مباشرة'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

