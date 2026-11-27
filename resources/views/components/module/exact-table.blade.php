@props([
    'records',
    'headers' => [],
    'emptyMessage' => 'لا توجد سجلات مطابقة لمعايير البحث',
    'footerTotalLabel' => null,
    'footerTotalValue' => null,
])

<div class="overflow-x-auto max-h-[76vh]">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-950 text-[13px] font-black text-white shadow-sm">
            <tr>
                @foreach ($headers as $header)
                    <th
                        scope="col"
                        class="whitespace-nowrap border-b-2 {{ ! $loop->last ? 'border-e-2 border-slate-700' : 'border-slate-700' }} px-4 py-4 text-center"
                    >
                        {{ $header }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $record)
                <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                    @foreach ($headers as $header)
                        @php
                            $displayValue = \App\Support\ExtraColumns::exactValue($record, $header);
                            $isLtr = $displayValue !== '—' && (bool) preg_match('/^[A-Za-z0-9_\-\/\.,:\s%]+$/', $displayValue);
                        @endphp
                        <td
                            @if ($isLtr) dir="ltr" @endif
                            class="{{ ! $loop->last ? 'border-e-2 border-slate-400' : '' }} {{ $loop->first ? 'bg-slate-200/50' : '' }} px-4 py-3.5 text-center text-sm font-black text-slate-950 whitespace-nowrap tabular-nums"
                        >
                            @if ($displayValue === '—')
                                <span class="font-bold text-slate-400">—</span>
                            @else
                                {{ $displayValue }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(1, count($headers)) }}" class="px-6 py-12 text-center">
                        <p class="text-base font-black text-slate-900">{{ $emptyMessage }}</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($footerTotalLabel !== null && $footerTotalValue !== null && $records->total() > 0)
            <tfoot class="sticky bottom-0 z-10 bg-slate-950 text-sm font-black text-white">
                <tr class="border-t-2 border-slate-700">
                    <td colspan="{{ max(1, count($headers)) }}" class="px-4 py-3.5 text-center">
                        <span>{{ $footerTotalLabel }}:</span>
                        <span class="ms-2 inline-flex items-center rounded-lg bg-emerald-700 px-3 py-1 text-sm font-black text-white tabular-nums">
                            {{ $footerTotalValue }}
                        </span>
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<x-module.pagination :paginator="$records" />

