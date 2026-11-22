<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SparePartTransfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SparePartTransferService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<SparePartTransfer>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = SparePartTransfer::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('item_description', 'like', "%{$search}%")
                    ->orWhere('from_equipment', 'like', "%{$search}%")
                    ->orWhere('to_equipment', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        foreach (['from_equipment', 'to_equipment'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        $sourceStatus = (string) ($filters['source_status'] ?? '');
        if ($sourceStatus === 'unknown') {
            $query->unknownSource();
        } elseif ($sourceStatus === 'known') {
            $query->knownSource();
        }

        $dateFrom = (string) ($filters['date_from'] ?? '');
        if ($dateFrom !== '') {
            $query->whereDate('transfer_date', '>=', $dateFrom);
        }

        $dateTo = (string) ($filters['date_to'] ?? '');
        if ($dateTo !== '') {
            $query->whereDate('transfer_date', '<=', $dateTo);
        }

        return $query->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, SparePartTransfer>
     */
    public function paginate(array $filters, int $perPage = 100): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @return array{
     *     total: int,
     *     unknown_source: int,
     *     with_notes: int,
     *     unique_targets: int
     * }
     */
    public function kpis(): array
    {
        return [
            'total' => SparePartTransfer::query()->count(),
            'unknown_source' => SparePartTransfer::query()->unknownSource()->count(),
            'with_notes' => SparePartTransfer::query()->whereNotNull('notes')->where('notes', '!=', '')->count(),
            'unique_targets' => SparePartTransfer::query()
                ->whereNotNull('to_equipment')
                ->where('to_equipment', '!=', '')
                ->distinct('to_equipment')
                ->count('to_equipment'),
        ];
    }

    /**
     * @return array{
     *     from_equipments: list<string>,
     *     to_equipments: list<string>
     * }
     */
    public function filterOptions(): array
    {
        $distinct = static fn (string $column): array => SparePartTransfer::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'from_equipments' => $distinct('from_equipment'),
            'to_equipments' => $distinct('to_equipment'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SparePartTransfer
    {
        return SparePartTransfer::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SparePartTransfer $transfer, array $data): SparePartTransfer
    {
        $transfer->update($data);

        return $transfer->refresh();
    }

    public function delete(SparePartTransfer $transfer): void
    {
        $transfer->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $records = $this->filteredQuery($filters)->get();

        $headers = [
            'م',
            'البند',
            'المعدة المنقول منها',
            'المعدة المنقول إليها',
            'التاريخ',
            'ملاحظة',
        ];

        $rows = $records->values()->map(static fn (SparePartTransfer $item, int $index): array => [
            $index + 1,
            $item->item_description,
            $item->from_equipment ?: 'غير معروفة',
            $item->to_equipment,
            $item->transfer_date?->format('d/m/Y'),
            $item->notes,
        ]);

        return $this->csvExportService->stream(
            filename: 'spare-part-transfers-'.now()->format('Y-m-d').'.csv',
            auditableClass: SparePartTransfer::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(SparePartTransfer $transfer, bool $canViewAuditLogs): array
    {
        $unknownSource = $transfer->hasUnknownSource();

        return [
            'id' => $transfer->id,
            'title' => $transfer->item_description ?: 'عملية نقل قطعة غيار',
            'subtitle' => sprintf('التاريخ: %s', $transfer->transfer_date?->format('d/m/Y') ?? '—'),
            'status_label' => $unknownSource ? 'مصدر غير معروف' : 'موثّق المصدر والوجهة',
            'status_badge' => $unknownSource
                ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20'
                : 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
            'fields' => [
                ['label' => 'البند', 'value' => $transfer->item_description ?: '—', 'full' => true],
                ['label' => 'المعدة المنقول منها', 'value' => $transfer->from_equipment ?: 'غير معروفة'],
                ['label' => 'المعدة المنقول إليها', 'value' => $transfer->to_equipment ?: '—'],
                ['label' => 'التاريخ', 'value' => $transfer->transfer_date?->format('d/m/Y') ?? '—'],
                ['label' => 'ملاحظة', 'value' => $transfer->notes ?: '—', 'full' => true],
            ],
            'record' => [
                'id' => $transfer->id,
                'item_description' => $transfer->item_description ?? '',
                'from_equipment' => $transfer->from_equipment ?? '',
                'to_equipment' => $transfer->to_equipment ?? '',
                'transfer_date' => $transfer->transfer_date?->format('Y-m-d') ?? '',
                'notes' => $transfer->notes ?? '',
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($transfer) : [],
        ];
    }
}
