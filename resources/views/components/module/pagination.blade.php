@props(['paginator'])

@if ($paginator->hasPages())
    <div class="flex flex-col gap-3 border-t border-slate-200/80 bg-slate-50/60 px-6 py-3.5 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-500 tabular-nums">
            عرض
            <span class="font-semibold text-slate-800">{{ $paginator->firstItem() }}</span>
            إلى
            <span class="font-semibold text-slate-800">{{ $paginator->lastItem() }}</span>
            من أصل
            <span class="font-semibold text-slate-800">{{ $paginator->total() }}</span>
            سجل
        </p>

        <nav class="inline-flex items-center gap-1" aria-label="التنقل بين الصفحات">
            @if ($paginator->onFirstPage())
                <span class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-400">السابق</span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    data-pagination-link
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition"
                >
                    السابق
                </a>
            @endif

            <span class="px-2.5 text-xs font-semibold text-slate-600 tabular-nums">
                صفحة {{ $paginator->currentPage() }} من {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    data-pagination-link
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition"
                >
                    التالي
                </a>
            @else
                <span class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-400">التالي</span>
            @endif
        </nav>
    </div>
@endif

