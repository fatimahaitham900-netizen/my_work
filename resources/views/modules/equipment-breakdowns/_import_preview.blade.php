@props([
    'preview' => null,
])

@if ($preview)
    <section
        x-data="{
            filter: 'all',
            search: '',
            matches(row) {
                if (this.filter !== 'all' && row.status !== this.filter) {
                    return false;
                }
                if (this.search.trim() === '') {
                    return true;
                }
                const term = this.search.toLowerCase();
                return (row.equipment_code && row.equipment_code.toLowerCase().includes(term))
                    || (row.equipment_name && row.equipment_name.toLowerCase().includes(term))
                    || (row.brand && row.brand.toLowerCase().includes(term))
                    || (row.workshop_name && row.workshop_name.toLowerCase().includes(term))
                    || (row.workshop_status && row.workshop_status.toLowerCase().includes(term))
                    || (row.fault_description && row.fault_description.toLowerCase().includes(term));
            }
        }"
        class="overflow-hidden rounded-3xl border-2 border-slate-900 bg-white shadow-xl space-y-0"
    >
        {{-- Header Bar --}}
        <div class="bg-slate-900 px-6 py-5 text-white">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-amber-500/20 px-3 py-1 text-xs font-black text-amber-300 ring-1 ring-inset ring-amber-500/30 mb-2">
                        <span class="h-2 w-2 rounded-full bg-amber-400 animate-pulse"></span>
                        <span>معاينة ومقارنة قبل التطبيق (جلسة مؤقتة لم تُحفظ بعد)</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                        معاينة ملف التحديث اليومي لمعدات الورش
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-300 mt-1">
                        الملف: <span class="font-mono text-amber-200 font-bold" dir="ltr">{{ $preview['filename'] }}</span> • تاريخ المعالجة: <span class="tabular-nums">{{ $preview['uploaded_at'] }}</span>
                    </p>
                </div>

                {{-- Action Buttons (تطبيق أو إلغاء) --}}
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <form method="POST" action="{{ route('equipment-breakdowns.import.cancel') }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-2.5 text-xs sm:text-sm font-bold text-slate-200 hover:border-rose-500/50 hover:bg-rose-500/10 hover:text-rose-300 transition cursor-pointer"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            <span>إلغاء المعاينة</span>
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('equipment-breakdowns.import.apply') }}"
                        onsubmit="return confirm('هل أنت متأكد من اعتماد وحفظ التحديثات في قاعدة البيانات وتحديث كافة التقارير؟');"
                    >
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs sm:text-sm font-black text-white shadow-lg shadow-emerald-950/40 hover:bg-emerald-500 focus:ring-2 focus:ring-emerald-400 focus:outline-none transition cursor-pointer"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <span>تطبيق التحديثات (اعتماد نهائي)</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Counter Badges & Summary Statistics --}}
            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-5 mt-5 pt-5 border-t border-slate-800 text-center">
                <div class="rounded-xl bg-slate-800/70 p-3 ring-1 ring-inset ring-white/10">
                    <p class="text-[11px] font-bold text-slate-400">إجمالي الصفوف بالملف</p>
                    <p class="mt-1 text-xl sm:text-2xl font-black text-white tabular-nums">{{ $preview['total_count'] }}</p>
                </div>
                <div class="rounded-xl bg-emerald-500/15 p-3 ring-1 ring-inset ring-emerald-500/30">
                    <p class="text-[11px] font-bold text-emerald-300">سجلات جديدة (سيتم إضافتها)</p>
                    <p class="mt-1 text-xl sm:text-2xl font-black text-emerald-300 tabular-nums">{{ $preview['new_count'] }}</p>
                </div>
                <div class="rounded-xl bg-amber-500/15 p-3 ring-1 ring-inset ring-amber-500/30">
                    <p class="text-[11px] font-bold text-amber-300">سجلات متغيرة (سيتم تحديثها)</p>
                    <p class="mt-1 text-xl sm:text-2xl font-black text-amber-300 tabular-nums">{{ $preview['changed_count'] }}</p>
                </div>
                <div class="rounded-xl bg-slate-700/30 p-3 ring-1 ring-inset ring-white/5">
                    <p class="text-[11px] font-bold text-slate-400">سجلات مطابقة (بدون تغيير)</p>
                    <p class="mt-1 text-xl sm:text-2xl font-black text-slate-300 tabular-nums">{{ $preview['unchanged_count'] }}</p>
                </div>
                <div class="rounded-xl bg-rose-500/15 p-3 ring-1 ring-inset ring-rose-500/30 col-span-2 sm:col-span-1">
                    <p class="text-[11px] font-bold text-rose-300">سجلات قديمة (سيتم حذفها)</p>
                    <p class="mt-1 text-xl sm:text-2xl font-black text-rose-300 tabular-nums">{{ $preview['deleted_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        {{-- Filter & Search Sub-Toolbar --}}
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            {{-- Filter Pills --}}
            <div class="flex flex-wrap items-center gap-1.5">
                <button
                    type="button"
                    @click="filter = 'all'"
                    :class="filter === 'all' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-200/70 border border-slate-200'"
                    class="rounded-xl px-3 py-1.5 text-xs font-black transition cursor-pointer"
                >
                    الكل ({{ $preview['total_count'] }})
                </button>
                <button
                    type="button"
                    @click="filter = 'new'"
                    :class="filter === 'new' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-emerald-50 text-emerald-900 hover:bg-emerald-100 border border-emerald-200'"
                    class="rounded-xl px-3 py-1.5 text-xs font-black transition cursor-pointer"
                >
                    جديد ({{ $preview['new_count'] }})
                </button>
                <button
                    type="button"
                    @click="filter = 'changed'"
                    :class="filter === 'changed' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-900 hover:bg-amber-100 border border-amber-200'"
                    class="rounded-xl px-3 py-1.5 text-xs font-black transition cursor-pointer"
                >
                    متغير ({{ $preview['changed_count'] }})
                </button>
                <button
                    type="button"
                    @click="filter = 'unchanged'"
                    :class="filter === 'unchanged' ? 'bg-slate-700 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                    class="rounded-xl px-3 py-1.5 text-xs font-black transition cursor-pointer"
                >
                    بدون تغيير ({{ $preview['unchanged_count'] }})
                </button>
                @if (($preview['deleted_count'] ?? 0) > 0)
                    <button
                        type="button"
                        @click="filter = 'deleted'"
                        :class="filter === 'deleted' ? 'bg-rose-700 text-white shadow-xs' : 'bg-rose-50 text-rose-900 hover:bg-rose-100 border border-rose-200'"
                        class="rounded-xl px-3 py-1.5 text-xs font-black transition cursor-pointer"
                    >
                        سيتم حذفها ({{ $preview['deleted_count'] }})
                    </button>
                @endif
            </div>

            {{-- Quick Search within preview --}}
            <div class="relative w-full sm:w-72">
                <input
                    type="text"
                    x-model="search"
                    placeholder="ابحث بالكود، المعدة، الورشة..."
                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none"
                >
            </div>
        </div>

        {{-- Preview & Diff Comparison Table --}}
        <div class="overflow-x-auto max-h-[60vh] bg-white">
            <table class="min-w-full border-collapse text-xs">
                <thead class="sticky top-0 z-10 bg-slate-100 font-black text-slate-900 border-b border-slate-300 shadow-xs">
                    <tr>
                        <th scope="col" class="border-e border-slate-200 px-3 py-3 text-center">م</th>
                        <th scope="col" class="border-e border-slate-200 px-3.5 py-3 text-center">الحالة</th>
                        <th scope="col" class="border-e border-slate-200 px-3 py-3 text-center">الكود</th>
                        <th scope="col" class="border-e border-slate-200 px-3.5 py-3 text-start">المعدة والنوع</th>
                        <th scope="col" class="border-e border-slate-200 px-3 py-3 text-center">الورشة</th>
                        <th scope="col" class="border-e border-slate-200 px-3 py-3 text-center">تاريخ التوقف</th>
                        <th scope="col" class="border-e border-slate-200 px-3 py-3 text-center">حجم العطل</th>
                        <th scope="col" class="border-e border-slate-200 px-4 py-3 text-start min-w-48">العطل والموقف</th>
                        <th scope="col" class="border-e border-slate-200 px-3 py-3 text-center">المبلغ</th>
                        <th scope="col" class="border-e border-slate-200 px-3 py-3 text-center">تاريخ الإصلاح</th>
                        <th scope="col" class="px-4 py-3 text-start min-w-48">ملاحظات الموقف</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($preview['rows'] as $row)
                        @php
                            $status = $row['status'];
                            $diffs = $row['diffs'];
                            $rowBg = match ($status) {
                                'new' => 'bg-emerald-50/70 hover:bg-emerald-100/60',
                                'changed' => 'bg-amber-50/70 hover:bg-amber-100/60',
                                default => 'bg-white hover:bg-slate-50',
                            };
                        @endphp
                        <tr
                            x-show="matches(@js($row))"
                            class="{{ $rowBg }} transition-colors"
                        >
                            {{-- Index --}}
                            <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center font-bold text-slate-600 tabular-nums">
                                {{ $row['index'] }}
                            </td>

                            {{-- Status Badge --}}
                            <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center">
                                @if ($status === 'new')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-black text-emerald-900 ring-1 ring-inset ring-emerald-600/30">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        <span>جديد</span>
                                    </span>
                                @elseif ($status === 'changed')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-black text-amber-950 ring-1 ring-inset ring-amber-600/30" title="{{ count($diffs) }} حقول متغيرة">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                        </svg>
                                        <span>متغير ({{ count($diffs) }})</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 ring-1 ring-inset ring-slate-300">
                                        مطابق
                                    </span>
                                @endif
                            </td>

                            {{-- Equipment Code --}}
                            <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center">
                                <code dir="ltr" class="rounded-md bg-slate-100 px-2 py-1 font-mono font-bold text-slate-900 ring-1 ring-inset ring-slate-200">
                                    {{ $row['equipment_code'] }}
                                </code>
                            </td>

                            {{-- Equipment Name & Brand --}}
                            <td class="whitespace-nowrap border-e border-slate-200 px-3.5 py-3 text-start">
                                @if (isset($diffs['equipment_name']) || isset($diffs['brand']))
                                    <div class="space-y-0.5">
                                        <div class="text-rose-700/70 text-[11px] line-through">
                                            {{ $diffs['equipment_name']['old'] ?? $row['equipment_name'] }} • {{ $diffs['brand']['old'] ?? $row['brand'] }}
                                        </div>
                                        <div class="font-extrabold text-emerald-900">
                                            {{ $row['equipment_name'] ?: '—' }} <span dir="ltr">({{ $row['brand'] ?: '—' }})</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="font-bold text-slate-900">{{ $row['equipment_name'] ?: '—' }}</span>
                                    <span class="text-slate-500 font-semibold" dir="ltr">({{ $row['brand'] ?: '—' }})</span>
                                @endif
                            </td>

                            {{-- Workshop Name --}}
                            <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center font-bold">
                                @if (isset($diffs['workshop_name']))
                                    <div class="space-y-0.5">
                                        <span class="block text-rose-700/70 text-[11px] line-through">{{ $diffs['workshop_name']['old'] }}</span>
                                        <span class="block font-black text-emerald-900">{{ $diffs['workshop_name']['new'] }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-800">{{ $row['workshop_name'] ?: '—' }}</span>
                                @endif
                            </td>

                            {{-- Breakdown Date --}}
                            <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center tabular-nums font-bold">
                                @if (isset($diffs['breakdown_date']))
                                    <div class="space-y-0.5">
                                        <span class="block text-rose-700/70 text-[11px] line-through">{{ $diffs['breakdown_date']['old'] }}</span>
                                        <span class="block font-black text-emerald-900">{{ $diffs['breakdown_date']['new'] }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-800">{{ $row['breakdown_date'] ?: '—' }}</span>
                                @endif
                            </td>

                            {{-- Severity --}}
                            <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center">
                                @if (isset($diffs['severity']))
                                    <div class="space-y-0.5">
                                        <span class="block text-rose-700/70 text-[11px] line-through">{{ $diffs['severity']['old'] }}</span>
                                        <span class="block font-black text-emerald-900">{{ $diffs['severity']['new'] }}</span>
                                    </div>
                                @else
                                    <span class="font-bold text-slate-800">{{ $row['severity_label'] }}</span>
                                @endif
                            </td>

                            {{-- Fault Description & Status --}}
                            <td class="border-e border-slate-200 px-4 py-3 text-start min-w-48 leading-relaxed">
                                @if (isset($diffs['fault_description']) || isset($diffs['workshop_status']))
                                    <div class="space-y-1">
                                        <div class="text-[11px] text-rose-700/70 line-through">
                                            {{ $diffs['fault_description']['old'] ?? $row['fault_description'] }}
                                            @if (isset($diffs['workshop_status']))
                                                [{{ $diffs['workshop_status']['old'] }}]
                                            @endif
                                        </div>
                                        <div class="font-extrabold text-emerald-950">
                                            {{ $row['fault_description'] ?: '—' }}
                                            <span class="inline-block rounded-md bg-emerald-100 px-1.5 py-0.5 text-[10px] font-black text-emerald-900">
                                                {{ $row['workshop_status'] ?: '—' }}
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <p class="font-bold text-slate-900">{{ $row['fault_description'] ?: '—' }}</p>
                                    @if ($row['workshop_status'])
                                        <span class="mt-1 inline-block rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-700">
                                            {{ $row['workshop_status'] }}
                                        </span>
                                    @endif
                                @endif
                            </td>

                            {{-- Cost --}}
                            <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center font-bold">
                                @if (isset($diffs['cost']))
                                    <div class="space-y-0.5">
                                        <span class="block text-rose-700/70 text-[11px] line-through">{{ $diffs['cost']['old'] }}</span>
                                        <span class="block font-black text-emerald-900">{{ $diffs['cost']['new'] }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-800">{{ $row['cost_display'] }}</span>
                                @endif
                            </td>

                            {{-- Expected Repair Date --}}
                            <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center tabular-nums font-bold">
                                @if (isset($diffs['expected_repair_date']))
                                    <div class="space-y-0.5">
                                        <span class="block text-rose-700/70 text-[11px] line-through">{{ $diffs['expected_repair_date']['old'] }}</span>
                                        <span class="block font-black text-emerald-900">{{ $diffs['expected_repair_date']['new'] }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-800">{{ $row['expected_repair_date'] ?: '—' }}</span>
                                @endif
                            </td>

                            {{-- Notes --}}
                            <td class="px-4 py-3 text-start min-w-48 leading-relaxed">
                                @if (isset($diffs['notes']))
                                    <div class="space-y-0.5">
                                        <p class="text-rose-700/70 text-[11px] line-through">{{ $diffs['notes']['old'] }}</p>
                                        <p class="font-bold text-emerald-950">{{ $diffs['notes']['new'] }}</p>
                                    </div>
                                @else
                                    <span class="text-slate-600">{{ $row['notes'] ?: '—' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    {{-- Rows to be deleted because they are not present in the new incoming file --}}
                    @if (! empty($preview['to_delete_rows']))
                        @foreach ($preview['to_delete_rows'] as $delRow)
                            <tr
                                x-show="filter === 'all' || filter === 'deleted'"
                                class="bg-rose-50/80 hover:bg-rose-100/70 border-b border-rose-200 transition-colors"
                            >
                                <td class="whitespace-nowrap border-e border-rose-200 px-3 py-3 text-center font-bold text-rose-800 tabular-nums">
                                    —
                                </td>
                                <td class="whitespace-nowrap border-e border-rose-200 px-3 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-200 px-2.5 py-1 text-[11px] font-black text-rose-950 ring-1 ring-inset ring-rose-600/30">
                                        <svg class="h-3 w-3 text-rose-700" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                        <span>سيتم حذفه</span>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap border-e border-rose-200 px-3 py-3 text-center">
                                    <code dir="ltr" class="rounded-md bg-rose-100 px-2 py-1 font-mono font-bold text-rose-950 ring-1 ring-inset ring-rose-300">
                                        {{ $delRow['equipment_code'] }}
                                    </code>
                                </td>
                                <td class="whitespace-nowrap border-e border-rose-200 px-3.5 py-3 text-start text-rose-950 font-bold">
                                    <span class="line-through">{{ $delRow['equipment_name'] }} ({{ $delRow['brand'] }})</span>
                                </td>
                                <td class="whitespace-nowrap border-e border-rose-200 px-3 py-3 text-center text-rose-900 font-bold">
                                    <span class="line-through">{{ $delRow['workshop_name'] }}</span>
                                </td>
                                <td class="whitespace-nowrap border-e border-rose-200 px-3 py-3 text-center text-rose-900 tabular-nums">
                                    <span class="line-through">{{ $delRow['breakdown_date'] }}</span>
                                </td>
                                <td class="whitespace-nowrap border-e border-rose-200 px-3 py-3 text-center text-rose-800">
                                    —
                                </td>
                                <td class="border-e border-rose-200 px-4 py-3 text-start text-rose-950 font-semibold">
                                    <span class="line-through">الموقف السابق: {{ $delRow['workshop_status'] }}</span>
                                </td>
                                <td class="whitespace-nowrap border-e border-rose-200 px-3 py-3 text-center text-rose-800">
                                    —
                                </td>
                                <td class="whitespace-nowrap border-e border-rose-200 px-3 py-3 text-center text-rose-800">
                                    —
                                </td>
                                <td class="px-4 py-3 text-start text-rose-900 font-bold text-xs">
                                    <span>⚠️ غير موجود في الملف اليومي — سيتم حذفه بالكامل من قاعدة البيانات</span>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Bottom Status Note --}}
        <div class="bg-slate-100 px-6 py-3 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-slate-600 gap-2">
            <span>💡 <strong>ملاحظة هامة:</strong> عند الضغط على <strong>"تطبيق التحديثات"</strong>، سيتم تحديث البيانات الجديدة بالكامل وحذف كافة السجلات القديمة غير المتواجدة في هذا الملف من قاعدة البيانات بشكل كامل.</span>
            <span class="text-slate-500 font-semibold">الملف: {{ $preview['total_count'] }} سجل • الحذف: {{ $preview['deleted_count'] ?? 0 }} سجل</span>
        </div>
    </section>
@endif

