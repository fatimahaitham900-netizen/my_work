@props(['headers' => [], 'record'])

@foreach ($headers as $extraHeader)
    @php
        $extraVal = is_array($record->extra_columns ?? null) ? ($record->extra_columns[$extraHeader] ?? '') : '';
    @endphp
    <td class="whitespace-nowrap border-s-2 border-slate-400 px-3.5 py-3.5 text-center text-sm font-extrabold text-slate-900">
        {{ $extraVal !== '' ? $extraVal : '—' }}
    </td>
@endforeach

