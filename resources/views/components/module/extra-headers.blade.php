@props(['headers' => []])

@foreach ($headers as $extraHeader)
    <th scope="col" class="whitespace-nowrap border-b-2 border-s-2 border-emerald-500/70 bg-emerald-950/70 px-3.5 py-4 text-center font-black text-emerald-100">
        {{ $extraHeader }}
    </th>
@endforeach

