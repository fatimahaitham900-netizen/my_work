@php
    $rows = $analysis['rows'] ?? [];
    $totals = $analysis['totals'] ?? [
        'rented_count' => 0,
        'rented_amount' => 0,
        'idle_no_work' => 0,
        'no_driver' => 0,
        'broken_workshop' => 0,
        'other_stopped' => 0,
        'total_alternatives' => 0,
        'replaceable_units' => 0,
        'potential_savings' => 0,
    ];
@endphp

<div class="space-y-6 p-4 sm:p-6">
    @if (count($rows) === 0)
        <div class="rounded-2xl border-2 border-slate-200 bg-slate-50 px-6 py-12 text-center">
            <p class="text-base font-black text-slate-900">لا توجد معدات إيجار لتحليل بدائلها حالياً</p>
            <p class="mt-1 text-xs font-bold text-slate-500">قم باستيراد أو إضافة معدات الإيجار وسيتم مطابقتها تلقائياً مع المعدات المتوقفة والمتعطلة في صفحة توزيعة المعدات.</p>
        </div>
    @else
        {{-- Executive Replacement KPIs --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            <div class="rounded-2xl border-2 border-slate-900 bg-white p-4 text-center shadow-xs">
                <p class="text-xs font-black text-slate-600">المعدات المستأجرة</p>
                <p class="mt-1 text-2xl sm:text-3xl font-black text-slate-950 tabular-nums">{{ number_format((int) $totals['rented_count']) }}</p>
                <p class="mt-0.5 text-[11px] font-extrabold text-slate-500 tabular-nums">{{ number_format((float) $totals['rented_amount'], 0) }} ر.س</p>
            </div>
            <div class="rounded-2xl border-2 border-emerald-700 bg-emerald-50/70 p-4 text-center shadow-xs">
                <p class="text-xs font-black text-emerald-900">متوقفة بدون عمل (بديل فوري)</p>
                <p class="mt-1 text-2xl sm:text-3xl font-black text-emerald-700 tabular-nums">{{ number_format((int) $totals['idle_no_work']) }}</p>
                <p class="mt-0.5 text-[11px] font-bold text-emerald-800">جاهزة للتشغيل مباشرة</p>
            </div>
            <div class="rounded-2xl border-2 border-amber-600 bg-amber-50/70 p-4 text-center shadow-xs">
                <p class="text-xs font-black text-amber-950">متوقفة بدون سائق</p>
                <p class="mt-1 text-2xl sm:text-3xl font-black text-amber-700 tabular-nums">{{ number_format((int) $totals['no_driver']) }}</p>
                <p class="mt-0.5 text-[11px] font-bold text-amber-800">تحتاج توجيه سائق فقط</p>
            </div>
            <div class="rounded-2xl border-2 border-rose-600 bg-rose-50/70 p-4 text-center shadow-xs">
                <p class="text-xs font-black text-rose-900">متعطلة في الورشة</p>
                <p class="mt-1 text-2xl sm:text-3xl font-black text-rose-700 tabular-nums">{{ number_format((int) $totals['broken_workshop']) }}</p>
                <p class="mt-0.5 text-[11px] font-bold text-rose-800">أولوية إصلاح عاجلة</p>
            </div>
            <div class="col-span-2 lg:col-span-1 rounded-2xl border-2 border-sky-800 bg-slate-950 p-4 text-center text-white shadow-xs">
                <p class="text-xs font-black text-sky-300">وفر مالي متوقع (ر.س)</p>
                <p class="mt-1 text-2xl sm:text-3xl font-black text-emerald-400 tabular-nums">{{ number_format((float) $totals['potential_savings'], 0) }}</p>
                <p class="mt-0.5 text-[11px] font-bold text-slate-300">إمكانية إحلال {{ number_format((int) $totals['replaceable_units']) }} معدة</p>
            </div>
        </div>

        {{-- Analysis Table --}}
        <div class="overflow-hidden rounded-2xl border-2 border-black bg-white shadow-sm" x-data="{ openIdx: null, activeCategory: 'all' }">
            <div class="bg-slate-950 px-5 py-3.5 text-center text-base sm:text-lg font-black text-white">
                تحليل بدائل الإيجار من توزيعة المعدات (بدون عمل / بدون سائق / متعطلة بالورشة)
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-sm">
                    <thead class="bg-slate-900 text-xs sm:text-[13px] font-black text-white">
                        <tr>
                            <th scope="col" class="w-12 border-b-2 border-e-2 border-slate-700 px-3 py-3.5 text-center">م</th>
                            <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-3.5 text-center">نوع المعدة المستأجرة</th>
                            <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-3.5 text-center">عدد الإيجار</th>
                            <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-3.5 text-center">مبلغ الإيجار</th>
                            <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-emerald-500/70 bg-emerald-900/50 px-3 py-3.5 text-center text-emerald-100">متوقفة بدون عمل</th>
                            <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-amber-500/70 bg-amber-900/50 px-3 py-3.5 text-center text-amber-100">متوقفة بدون سائق</th>
                            <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-rose-500/70 bg-rose-900/50 px-3 py-3.5 text-center text-rose-100">متعطلة بالورشة</th>
                            <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-sky-500/70 bg-sky-900/50 px-3 py-3.5 text-center text-sky-100">إجمالي البدائل</th>
                            <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-3.5 text-center">الوفر المتوقع</th>
                            <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-3.5 text-center">التوصية التنفيذية</th>
                            <th scope="col" class="whitespace-nowrap border-b-2 border-slate-700 px-3 py-3.5 text-center">التفاصيل</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach ($rows as $idx => $row)
                            @php
                                $recBadge = match ($row['recommendation_tone']) {
                                    'emerald' => 'bg-emerald-100 text-emerald-950 ring-1 ring-inset ring-emerald-600/35',
                                    'amber' => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/35',
                                    'rose' => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/35',
                                    'sky' => 'bg-sky-100 text-sky-950 ring-1 ring-inset ring-sky-600/35',
                                    default => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-300',
                                };
                            @endphp
                            <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/60 transition-colors">
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center font-black text-slate-950 tabular-nums">
                                    {{ $loop->iteration }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                                    <p class="text-sm font-black text-slate-950">{{ $row['type'] }}</p>
                                    @if (count($row['rented_branches']) > 0)
                                        <p class="mt-0.5 text-[11px] font-bold text-slate-500">{{ implode('، ', $row['rented_branches']) }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 px-3 py-3.5 text-center text-base font-black text-slate-950 tabular-nums">
                                    {{ number_format((int) $row['rented_count']) }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                                    {{ number_format((float) $row['rented_amount'], 0) }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-emerald-50/40 px-3 py-3.5 text-center text-base font-black text-emerald-700 tabular-nums">
                                    @if ((int) $row['idle_no_work'] > 0)
                                        <button
                                            type="button"
                                            @click="openIdx = {{ $idx }}; activeCategory = 'idle_no_work'"
                                            class="inline-flex min-w-9 items-center justify-center rounded-lg bg-emerald-100 px-2.5 py-0.5 text-emerald-900 hover:bg-emerald-700 hover:text-white transition cursor-pointer"
                                            title="عرض المعدات المتوقفة بدون عمل"
                                        >
                                            {{ number_format((int) $row['idle_no_work']) }}
                                        </button>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-amber-50/40 px-3 py-3.5 text-center text-base font-black text-amber-700 tabular-nums">
                                    @if ((int) $row['no_driver'] > 0)
                                        <button
                                            type="button"
                                            @click="openIdx = {{ $idx }}; activeCategory = 'no_driver'"
                                            class="inline-flex min-w-9 items-center justify-center rounded-lg bg-amber-100 px-2.5 py-0.5 text-amber-950 hover:bg-amber-600 hover:text-white transition cursor-pointer"
                                            title="عرض تفاصيل المعدات المتوقفة بدون سائق من نفس النوع"
                                        >
                                            {{ number_format((int) $row['no_driver']) }}
                                        </button>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-rose-50/40 px-3 py-3.5 text-center text-base font-black text-rose-700 tabular-nums">
                                    @if ((int) $row['broken_workshop'] > 0)
                                        <button
                                            type="button"
                                            @click="openIdx = {{ $idx }}; activeCategory = 'broken_workshop'"
                                            class="inline-flex min-w-9 items-center justify-center rounded-lg bg-rose-100 px-2.5 py-0.5 text-rose-900 hover:bg-rose-700 hover:text-white transition cursor-pointer"
                                            title="عرض المعدات المتعطلة بالورشة"
                                        >
                                            {{ number_format((int) $row['broken_workshop']) }}
                                        </button>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-sky-50/50 px-3 py-3.5 text-center">
                                    @if ((int) $row['total_alternatives'] > 0)
                                        <span class="text-base font-black text-sky-950 tabular-nums">{{ number_format((int) $row['total_alternatives']) }}</span>
                                        @if ((int) $row['same_branch_count'] > 0)
                                            <span class="mt-0.5 block text-[10px] font-black text-emerald-700">({{ $row['same_branch_count'] }} بنفس الفرع)</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-emerald-50/60 px-3.5 py-3.5 text-center text-sm font-black text-emerald-900 tabular-nums">
                                    {{ (float) $row['potential_savings'] > 0 ? number_format((float) $row['potential_savings'], 0).' ر.س' : '' }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-black {{ $recBadge }}">
                                        {{ $row['recommendation'] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-center">
                                    @if (count($row['candidates']) > 0)
                                        <button
                                            type="button"
                                            @click="openIdx = openIdx === {{ $idx }} ? null : {{ $idx }}; activeCategory = 'all'"
                                            class="inline-flex items-center gap-1 rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-black text-white hover:bg-sky-700 transition cursor-pointer"
                                        >
                                            <span x-text="openIdx === {{ $idx }} ? 'إخفاء' : 'عرض البدائل ({{ count($row['candidates']) }})'">عرض البدائل ({{ count($row['candidates']) }})</span>
                                        </button>
                                    @else
                                        <span class="text-xs font-bold text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>

                            {{-- Expandable Candidates Sub-Table (with Category Tabs + Matching Unassigned Drivers) --}}
                            @if (count($row['candidates']) > 0)
                                <tr x-show="openIdx === {{ $idx }}" x-cloak class="border-b-2 border-slate-900 bg-slate-50">
                                    <td colspan="11" class="p-4 space-y-4">
                                        <div class="overflow-hidden rounded-xl border border-slate-300 bg-white">
                                            <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-800 px-4 py-2.5 text-xs font-black text-white">
                                                <span>المعدات المملوكة المتاحة كبديل لـ ({{ $row['type'] }}) من صفحة توزيعة المعدات</span>
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <button type="button" @click="activeCategory = 'all'" :class="activeCategory === 'all' ? 'bg-white text-slate-950' : 'bg-white/15 text-white hover:bg-white/25'" class="rounded-lg px-2.5 py-1 text-[11px] font-black transition cursor-pointer">
                                                        الكل ({{ count($row['candidates']) }})
                                                    </button>
                                                    @if ((int) $row['idle_no_work'] > 0)
                                                        <button type="button" @click="activeCategory = 'idle_no_work'" :class="activeCategory === 'idle_no_work' ? 'bg-emerald-500 text-white' : 'bg-emerald-500/20 text-emerald-200 hover:bg-emerald-500/35'" class="rounded-lg px-2.5 py-1 text-[11px] font-black transition cursor-pointer">
                                                            بدون عمل ({{ $row['idle_no_work'] }})
                                                        </button>
                                                    @endif
                                                    @if ((int) $row['no_driver'] > 0)
                                                        <button type="button" @click="activeCategory = 'no_driver'" :class="activeCategory === 'no_driver' ? 'bg-amber-500 text-white' : 'bg-amber-500/20 text-amber-200 hover:bg-amber-500/35'" class="rounded-lg px-2.5 py-1 text-[11px] font-black transition cursor-pointer">
                                                            بدون سائق ({{ $row['no_driver'] }})
                                                        </button>
                                                    @endif
                                                    @if ((int) $row['broken_workshop'] > 0)
                                                        <button type="button" @click="activeCategory = 'broken_workshop'" :class="activeCategory === 'broken_workshop' ? 'bg-rose-500 text-white' : 'bg-rose-500/20 text-rose-200 hover:bg-rose-500/35'" class="rounded-lg px-2.5 py-1 text-[11px] font-black transition cursor-pointer">
                                                            متعطلة بالورشة ({{ $row['broken_workshop'] }})
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full border-collapse text-xs">
                                                    <thead class="bg-slate-100 font-black text-slate-900">
                                                        <tr class="border-b border-slate-300">
                                                            <th class="border-e border-slate-300 px-3 py-2 text-center">كود المعدة</th>
                                                            <th class="border-e border-slate-300 px-3 py-2 text-center">النوع بالتوزيعة</th>
                                                            <th class="border-e border-slate-300 px-3 py-2 text-center">الفرع</th>
                                                            <th class="border-e border-slate-300 px-3 py-2 text-center">الموقع</th>
                                                            <th class="border-e border-slate-300 px-3 py-2 text-center">حالة المعدة</th>
                                                            <th class="border-e border-slate-300 px-3 py-2 text-center">العطل (من أعطال المعدات)</th>
                                                            <th class="border-e border-slate-300 px-3 py-2 text-center">تاريخ التوقف</th>
                                                            <th class="border-e border-slate-300 px-3 py-2 text-center">أيام التوقف</th>
                                                            <th class="px-3 py-2 text-center">ملاحظة</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($row['candidates'] as $cand)
                                                            @php
                                                                $candBadge = match ($cand['category']) {
                                                                    'idle_no_work' => 'bg-emerald-100 text-emerald-950',
                                                                    'no_driver' => 'bg-amber-100 text-amber-950',
                                                                    'broken_workshop' => 'bg-rose-100 text-rose-900',
                                                                    default => 'bg-slate-100 text-slate-800',
                                                                };
                                                            @endphp
                                                            <tr
                                                                x-show="activeCategory === 'all' || activeCategory === '{{ $cand['category'] }}'"
                                                                class="border-b border-slate-200 even:bg-slate-50/70"
                                                            >
                                                                <td dir="ltr" class="border-e border-slate-200 px-3 py-2 text-center font-mono font-black text-slate-950">
                                                                    {{ $cand['equipment_code'] }}
                                                                </td>
                                                                <td class="border-e border-slate-200 px-3 py-2 text-center font-bold text-slate-900">{{ $cand['equipment_type'] }}</td>
                                                                <td class="border-e border-slate-200 px-3 py-2 text-center font-black text-slate-900">
                                                                    <span>{{ $cand['branch'] }}</span>
                                                                    @if ($cand['same_branch'])
                                                                        <span class="ms-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-black text-emerald-900">نفس الفرع</span>
                                                                    @endif
                                                                </td>
                                                                <td class="border-e border-slate-200 px-3 py-2 text-center font-bold text-slate-800">{{ $cand['site_location'] }}</td>
                                                                <td class="border-e border-slate-200 px-3 py-2 text-center">
                                                                    <span class="inline-flex rounded-full px-2.5 py-0.5 font-black {{ $candBadge }}">{{ $cand['equipment_status'] }}</span>
                                                                    @if (! empty($cand['workshop_status']))
                                                                        <span class="mt-1 block text-[10px] font-bold text-slate-600">{{ $cand['workshop_status'] }}</span>
                                                                    @endif
                                                                </td>
                                                                <td class="border-e border-slate-200 px-3 py-2 text-center font-bold text-slate-900 max-w-xs">
                                                                    {{ ($cand['fault_description'] ?? '') !== '' ? $cand['fault_description'] : '—' }}
                                                                </td>
                                                                <td dir="ltr" class="border-e border-slate-200 px-3 py-2 text-center font-mono font-bold text-slate-900 tabular-nums">
                                                                    {{ ($cand['breakdown_date'] ?? '') !== '' ? $cand['breakdown_date'] : '—' }}
                                                                </td>
                                                                <td class="border-e border-slate-200 px-3 py-2 text-center tabular-nums">
                                                                    @if (($cand['downtime_days'] ?? null) !== null)
                                                                        <span class="inline-flex rounded-lg px-2 py-0.5 text-[11px] font-black {{ $cand['downtime_days'] >= 30 ? 'bg-rose-100 text-rose-900' : 'bg-amber-100 text-amber-950' }}">
                                                                            {{ $cand['downtime_days'] }} يوم
                                                                        </span>
                                                                    @else
                                                                        <span class="font-bold text-slate-400">—</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-3 py-2 text-center font-bold text-slate-700">{{ $cand['notes'] !== '' && $cand['notes'] !== '0' ? $cand['notes'] : '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        {{-- Matching Unassigned Drivers of the Same Equipment Type --}}
                                        @if (count($row['matching_drivers'] ?? []) > 0)
                                            <div class="overflow-hidden rounded-xl border border-amber-300 bg-white">
                                                <div class="flex items-center justify-between bg-amber-600 px-4 py-2 text-xs font-black text-white">
                                                    <span>سائقين متاحين بدون معدات من نفس النوع ({{ $row['type'] }}) — جاهزون للتوجيه لتشغيل المعدات المتوقفة بدون سائق</span>
                                                    <span>{{ count($row['matching_drivers']) }} سائق</span>
                                                </div>
                                                <div class="overflow-x-auto">
                                                    <table class="min-w-full border-collapse text-xs">
                                                        <thead class="bg-amber-50 font-black text-amber-950">
                                                            <tr class="border-b border-amber-200">
                                                                <th class="border-e border-amber-200 px-3 py-2 text-center">اسم السائق</th>
                                                                <th class="border-e border-amber-200 px-3 py-2 text-center">الرقم الوظيفي</th>
                                                                <th class="border-e border-amber-200 px-3 py-2 text-center">المهنة</th>
                                                                <th class="border-e border-amber-200 px-3 py-2 text-center">الموقع</th>
                                                                <th class="px-3 py-2 text-center">ملاحظات</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($row['matching_drivers'] as $mDriver)
                                                                <tr class="border-b border-amber-100 even:bg-amber-50/40">
                                                                    <td class="border-e border-amber-100 px-3 py-2 text-center font-black text-slate-950">{{ $mDriver['driver_name'] }}</td>
                                                                    <td dir="ltr" class="border-e border-amber-100 px-3 py-2 text-center font-mono font-black text-slate-900">{{ $mDriver['employee_id'] }}</td>
                                                                    <td class="border-e border-amber-100 px-3 py-2 text-center font-bold text-slate-900">{{ $mDriver['job_title'] }}</td>
                                                                    <td class="border-e border-amber-100 px-3 py-2 text-center font-bold text-slate-800">{{ $mDriver['location'] }}</td>
                                                                    <td class="px-3 py-2 text-center font-bold text-slate-700">{{ $mDriver['notes'] !== '' ? $mDriver['notes'] : '—' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-950 text-sm font-black text-white">
                        <tr class="border-t-2 border-slate-700">
                            <td colspan="2" class="border-e-2 border-slate-700 px-4 py-3.5 text-center">الإجمالي العام</td>
                            <td class="border-e-2 border-slate-700 px-3 py-3.5 text-center tabular-nums">{{ number_format((int) $totals['rented_count']) }}</td>
                            <td class="border-e-2 border-slate-700 px-3.5 py-3.5 text-center tabular-nums">{{ number_format((float) $totals['rented_amount'], 0) }}</td>
                            <td class="border-e-2 border-slate-700 bg-emerald-900/60 px-3 py-3.5 text-center tabular-nums">{{ (int) $totals['idle_no_work'] > 0 ? number_format((int) $totals['idle_no_work']) : '' }}</td>
                            <td class="border-e-2 border-slate-700 bg-amber-900/60 px-3 py-3.5 text-center tabular-nums">{{ (int) $totals['no_driver'] > 0 ? number_format((int) $totals['no_driver']) : '' }}</td>
                            <td class="border-e-2 border-slate-700 bg-rose-900/60 px-3 py-3.5 text-center tabular-nums">{{ (int) $totals['broken_workshop'] > 0 ? number_format((int) $totals['broken_workshop']) : '' }}</td>
                            <td class="border-e-2 border-slate-700 bg-sky-800 px-3 py-3.5 text-center tabular-nums">{{ number_format((int) $totals['total_alternatives']) }}</td>
                            <td class="border-e-2 border-slate-700 bg-emerald-700 px-3.5 py-3.5 text-center tabular-nums">{{ number_format((float) $totals['potential_savings'], 0) }} ر.س</td>
                            <td colspan="2" class="px-4 py-3.5 text-center text-xs text-slate-300">إمكانية إحلال {{ number_format((int) $totals['replaceable_units']) }} من {{ number_format((int) $totals['rented_count']) }} معدة</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>

