<div class="overflow-x-auto max-h-[76vh]">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-950 text-[13px] font-black text-white shadow-sm">
            <tr>
                <th scope="col" class="w-14 whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">اسم المقاول</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">النوع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-emerald-500/80 bg-emerald-900/60 px-4 py-4 text-center text-emerald-100">مبلغ الإيجار</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">نوع الإيجار</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">الفرع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-slate-700 px-4 py-4 text-center">المشروع</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $item)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                @endphp
                <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center text-sm sm:text-base font-black text-slate-950">
                        {{ $item->contractor_name ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center text-sm sm:text-base font-black text-slate-950">
                        {{ $item->equipment_type ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-emerald-50/50 px-4 py-3.5 text-center text-base font-black text-emerald-900 tabular-nums">
                        {{ number_format((float) $item->rental_amount, 0) }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                        @if ($item->rental_type)
                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-3.5 py-1 text-xs font-black text-indigo-950 ring-1 ring-inset ring-indigo-600/30">
                                {{ $item->rental_type }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
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
                    <td class="whitespace-nowrap px-4 py-3.5 text-center text-sm sm:text-base font-black text-slate-950">
                        {{ $item->project ?: '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <p class="text-base font-black text-slate-900">لا توجد معدات إيجار مسجلة حالياً</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">يمكنك استيراد ملف Excel/CSV أو إضافة سجل جديد من الأعلى.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($records->total() > 0)
            <tfoot class="sticky bottom-0 z-10 bg-slate-950 text-sm sm:text-base font-black text-white">
                <tr class="border-t-2 border-slate-700">
                    <td colspan="3" class="border-e-2 border-slate-700 px-4 py-4 text-center">
                        الإجمالي ({{ number_format($records->total()) }} معدة)
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-700 bg-emerald-700 px-4 py-4 text-center text-base sm:text-lg font-black text-white tabular-nums">
                        {{ number_format((float) $filteredAmountTotal, 0) }}
                    </td>
                    <td colspan="3" class="px-4 py-4 text-center text-xs text-slate-300">
                        ريال سعودي
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<x-module.pagination :paginator="$records" />

