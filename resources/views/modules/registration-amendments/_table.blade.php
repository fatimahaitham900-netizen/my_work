<div class="overflow-x-auto max-h-[76vh]">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-950 text-[13px] font-black text-white shadow-sm">
            <tr>
                <th scope="col" class="w-14 whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">الكود</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">نوع المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">الفرع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">تاريخ التوقف</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-rose-500/80 bg-rose-900/60 px-4 py-4 text-center text-rose-100">أيام التوقف</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $item)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $days = $item->stoppage_days;
                    $daysBadge = match (true) {
                        $days === null => 'bg-slate-100 text-slate-600',
                        $days >= 7 => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/40',
                        $days >= 4 => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/35',
                        default => 'bg-emerald-100 text-emerald-900 ring-1 ring-inset ring-emerald-600/35',
                    };
                @endphp
                <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                        <span dir="ltr" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1 font-mono text-xs font-black text-white shadow-xs">
                            {{ $item->equipment_code ?: '—' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center text-sm sm:text-base font-black text-slate-950">
                        {{ $item->equipment_type ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                        @if ($item->branch)
                            <span class="inline-flex items-center rounded-full bg-sky-100 px-3.5 py-1 text-xs font-black text-sky-950 ring-1 ring-inset ring-sky-600/30">
                                {{ $item->branch }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center font-mono text-sm font-black text-slate-950 tabular-nums">
                        {{ $item->stoppage_date?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap bg-rose-50/40 px-4 py-3.5 text-center">
                        @if ($days !== null)
                            <span class="inline-flex min-w-14 items-center justify-center rounded-lg px-3 py-1 text-sm font-black tabular-nums {{ $daysBadge }}">
                                {{ $days }} يوم
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <p class="text-base font-black text-slate-900">لا توجد معدات تعديل استمارة مسجلة حالياً</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">يمكنك استيراد ملف Excel/CSV أو إضافة سجل جديد من الأعلى.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />

