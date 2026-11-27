<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CarRegulatoryDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CarRegulatoryDocumentService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<CarRegulatoryDocument>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = CarRegulatoryDocument::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('car_code', 'like', "%{$search}%")
                    ->orWhere('plate_number', 'like', "%{$search}%")
                    ->orWhere('car_type_model', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('project_location', 'like', "%{$search}%")
                    ->orWhere('hijri_expiry_date', 'like', "%{$search}%")
                    ->orWhere('status_label', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        foreach (['document_type', 'branch', 'project_location'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        return $query
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')
            ->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, CarRegulatoryDocument>
     */
    public function paginate(array $filters, int $perPage = 100): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total: int}
     */
    public function kpis(array $filters = []): array
    {
        $baseQuery = CarRegulatoryDocument::query();
        $documentType = trim((string) ($filters['document_type'] ?? ''));

        if ($documentType !== '') {
            $baseQuery->where('document_type', $documentType);
        }

        return [
            'total' => $baseQuery->count(),
        ];
    }

    /**
     * @return list<array{value: string, label: string, count: int}>
     */
    public function documentTabs(): array
    {
        $countsByType = CarRegulatoryDocument::query()
            ->selectRaw('document_type, COUNT(*) as total_rows')
            ->groupBy('document_type')
            ->pluck('total_rows', 'document_type');

        $totalAll = (int) $countsByType->sum();

        return [
            [
                'value' => CarRegulatoryDocument::TYPE_INSPECTION,
                'label' => 'الفحص الدوري المنتهي',
                'count' => (int) ($countsByType->get(CarRegulatoryDocument::TYPE_INSPECTION) ?? 0),
            ],
            [
                'value' => CarRegulatoryDocument::TYPE_REGISTRATION,
                'label' => 'الاستمارات المنتهية',
                'count' => (int) ($countsByType->get(CarRegulatoryDocument::TYPE_REGISTRATION) ?? 0),
            ],
            [
                'value' => CarRegulatoryDocument::TYPE_INSURANCE,
                'label' => 'التأمين المنتهي',
                'count' => (int) ($countsByType->get(CarRegulatoryDocument::TYPE_INSURANCE) ?? 0),
            ],
            [
                'value' => '',
                'label' => 'جميع أوراق السيارات',
                'count' => $totalAll,
            ],
        ];
    }

    /**
     * @return array{
     *     branches: list<string>,
     *     project_locations: list<string>
     * }
     */
    public function filterOptions(): array
    {
        $distinct = static fn (string $column): array => CarRegulatoryDocument::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'branches' => $distinct('branch'),
            'project_locations' => $distinct('project_location'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CarRegulatoryDocument
    {
        $record = CarRegulatoryDocument::query()->create($data);
        DashboardService::clearCache();

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CarRegulatoryDocument $document, array $data): CarRegulatoryDocument
    {
        $document->update($data);
        DashboardService::clearCache();

        return $document->refresh();
    }

    public function delete(CarRegulatoryDocument $document): void
    {
        $document->delete();
        DashboardService::clearCache();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $records = $this->filteredQuery($filters)->get();

        $headers = [
            'م',
            'نوع الوثيقة',
            'كود السيارة',
            'رقم اللوحة',
            'نوع السيارة والموديل',
            'الفرع',
            'المشروع / الموقع',
            'تاريخ الانتهاء (هجري)',
            'تاريخ الانتهاء (ميلادي)',
            'الأيام والحالة',
        ];

        $rows = $records->values()->map(static function (CarRegulatoryDocument $item, int $index): array {
            $days = $item->days_remaining;
            $status = $days !== null
                ? ($days < 0 ? 'منتهي منذ '.abs($days).' يوم' : ($days === 0 ? 'ينتهي اليوم' : 'متبقي '.$days.' يوم'))
                : ($item->status_label ?: '—');

            return [
                $index + 1,
                $item->document_type,
                $item->car_code,
                $item->plate_number,
                $item->car_type_model,
                $item->branch,
                $item->project_location,
                $item->hijri_expiry_date,
                $item->expiry_date?->format('Y-m-d') ?? '',
                $status,
            ];
        });

        return $this->csvExportService->stream(
            filename: 'car-regulatory-documents-'.now()->format('Y-m-d').'.csv',
            auditableClass: CarRegulatoryDocument::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(CarRegulatoryDocument $document, bool $canViewAuditLogs): array
    {
        $days = $document->days_remaining;
        $statusText = $days !== null
            ? ($days < 0 ? 'منتهي منذ '.abs($days).' يوم' : ($days === 0 ? 'ينتهي اليوم' : 'متبقي '.$days.' يوم'))
            : ($document->status_label ?: 'غير محدد');

        return [
            'id' => $document->id,
            'title' => sprintf('%s — %s', $document->car_code ?: ($document->plate_number ?: 'سيارة'), $document->document_type),
            'subtitle' => sprintf('%s • %s', $document->car_type_model ?: '—', $document->branch ?: '—'),
            'status_label' => $statusText,
            'status_badge' => ($days ?? -1) < 0
                ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20'
                : 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
            'fields' => [
                ['label' => 'نوع الوثيقة', 'value' => $document->document_type ?: '—'],
                ['label' => 'كود السيارة', 'value' => $document->car_code ?: '—'],
                ['label' => 'رقم اللوحة', 'value' => $document->plate_number ?: '—'],
                ['label' => 'نوع السيارة والموديل', 'value' => $document->car_type_model ?: '—'],
                ['label' => 'الفرع', 'value' => $document->branch ?: '—'],
                ['label' => 'المشروع / الموقع', 'value' => $document->project_location ?: '—'],
                ['label' => 'تاريخ الانتهاء (هجري)', 'value' => $document->hijri_expiry_date ?: '—'],
                ['label' => 'تاريخ الانتهاء (ميلادي)', 'value' => $document->expiry_date?->format('Y-m-d') ?? '—'],
                ['label' => 'الأيام والحالة', 'value' => $statusText],
                ['label' => 'ملاحظات', 'value' => $document->notes ?: '—', 'full' => true],
            ],
            'record' => [
                'id' => $document->id,
                'document_type' => $document->document_type ?: CarRegulatoryDocument::TYPE_INSPECTION,
                'car_code' => $document->car_code ?? '',
                'plate_number' => $document->plate_number ?? '',
                'car_type_model' => $document->car_type_model ?? '',
                'branch' => $document->branch ?? '',
                'project_location' => $document->project_location ?? '',
                'hijri_expiry_date' => $document->hijri_expiry_date ?? '',
                'expiry_date' => $document->expiry_date?->format('Y-m-d') ?? '',
                'status_label' => $document->status_label ?? '',
                'notes' => $document->notes ?? '',
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($document) : [],
        ];
    }
}
