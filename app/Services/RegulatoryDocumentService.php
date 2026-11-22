<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ComplianceStatus;
use App\Enums\RegulatoryEntityType;
use App\Models\RegulatoryDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegulatoryDocumentService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<RegulatoryDocument>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = RegulatoryDocument::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('entity_identifier', 'like', "%{$search}%")
                    ->orWhere('equipment_type', 'like', "%{$search}%")
                    ->orWhere('plate_number', 'like', "%{$search}%")
                    ->orWhere('transport_type', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('hijri_expiry_date', 'like', "%{$search}%")
                    ->orWhere('renewal_status', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $entityType = (string) ($filters['entity_type'] ?? '');
        if ($entityType !== '' && RegulatoryEntityType::tryFrom($entityType) !== null) {
            $query->where('entity_type', $entityType);
        }

        $complianceStatus = (string) ($filters['compliance_status'] ?? '');
        if ($complianceStatus !== '' && ComplianceStatus::tryFrom($complianceStatus) !== null) {
            $query->where('compliance_status', $complianceStatus);
        }

        foreach (['document_type', 'branch', 'transport_type', 'equipment_type', 'renewal_status'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        $focus = (string) ($filters['focus'] ?? '');
        if ($focus === 'critical') {
            $query->critical();
        } elseif ($focus === 'active') {
            $query->deviations();
        }

        $dateFrom = (string) ($filters['date_from'] ?? '');
        if ($dateFrom !== '') {
            $query->whereDate('expiry_date', '>=', $dateFrom);
        }

        $dateTo = (string) ($filters['date_to'] ?? '');
        if ($dateTo !== '') {
            $query->whereDate('expiry_date', '<=', $dateTo);
        }

        return $query
            ->orderByRaw("CASE compliance_status WHEN 'missing' THEN 1 WHEN 'expired' THEN 2 WHEN 'expiring_soon' THEN 3 ELSE 4 END")
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 0 ELSE 1 END, expiry_date ASC')
            ->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, RegulatoryDocument>
     */
    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total: int, critical: int, expiring_soon: int, valid: int, renewal_required: int, end_of_life: int}
     */
    public function kpis(array $filters = []): array
    {
        $baseQuery = RegulatoryDocument::query();
        $documentType = trim((string) ($filters['document_type'] ?? ''));

        if ($documentType !== '') {
            $baseQuery->where('document_type', $documentType);
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'critical' => (clone $baseQuery)->critical()->count(),
            'expiring_soon' => (clone $baseQuery)->expiringSoon()->count(),
            'valid' => (clone $baseQuery)->where('compliance_status', ComplianceStatus::Valid->value)->count(),
            'renewal_required' => (clone $baseQuery)->where('renewal_status', 'مطلوب تجديد')->count(),
            'end_of_life' => (clone $baseQuery)->where('renewal_status', 'عمر افتراضي')->count(),
        ];
    }

    /**
     * Build sub-page navigation tabs inside Regulatory Documents.
     *
     * @return list<array{value: string, label: string, count: int}>
     */
    public function documentTabs(): array
    {
        $countsByType = RegulatoryDocument::query()
            ->selectRaw('document_type, COUNT(*) as total_rows')
            ->groupBy('document_type')
            ->pluck('total_rows', 'document_type');

        $totalAll = (int) $countsByType->sum();

        return [
            [
                'value' => 'فحص دوري',
                'label' => 'الفحص الدوري المنتهي',
                'count' => (int) ($countsByType->get('فحص دوري') ?? 0),
            ],
            [
                'value' => 'كرت تشغيل',
                'label' => 'كروت التشغيل المنتهية',
                'count' => (int) ($countsByType->get('كرت تشغيل') ?? 0),
            ],
            [
                'value' => 'استمارة',
                'label' => 'الاستمارات المنتهية',
                'count' => (int) ($countsByType->get('استمارة') ?? 0),
            ],
            [
                'value' => '',
                'label' => 'جميع الأوراق النظامية',
                'count' => $totalAll,
            ],
        ];
    }

    /**
     * @return array{
     *     document_types: list<string>,
     *     branches: list<string>,
     *     transport_types: list<string>,
     *     equipment_types: list<string>,
     *     renewal_statuses: list<string>
     * }
     */
    public function filterOptions(): array
    {
        $distinct = static fn (string $column): array => RegulatoryDocument::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'document_types' => $distinct('document_type'),
            'branches' => $distinct('branch'),
            'transport_types' => $distinct('transport_type'),
            'equipment_types' => $distinct('equipment_type'),
            'renewal_statuses' => $distinct('renewal_status'),
        ];
    }

    /**
     * Automatically classify compliance_status from expiry_date (or missing flag).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applySmartComplianceStatus(array $data): array
    {
        $requestedStatus = isset($data['compliance_status']) && is_string($data['compliance_status'])
            ? ComplianceStatus::tryFrom($data['compliance_status'])
            : null;

        $expiryDate = null;
        if (! empty($data['expiry_date']) && is_string($data['expiry_date'])) {
            $expiryDate = Carbon::parse($data['expiry_date']);
        }

        $resolved = RegulatoryDocument::resolveComplianceStatus($expiryDate, $requestedStatus);
        $data['compliance_status'] = $resolved->value;

        if ($resolved === ComplianceStatus::Missing && empty($data['expiry_date'])) {
            $data['expiry_date'] = null;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): RegulatoryDocument
    {
        return RegulatoryDocument::query()->create(self::applySmartComplianceStatus($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(RegulatoryDocument $document, array $data): RegulatoryDocument
    {
        $document->update(self::applySmartComplianceStatus($data));

        return $document->refresh();
    }

    public function delete(RegulatoryDocument $document): void
    {
        $document->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $records = $this->filteredQuery($filters)->get();

        $headers = [
            'م',
            'كود المعدة',
            'نوع المعدة',
            'رقم اللوحة',
            'نوع النقل',
            'الفرع الحالي',
            'نوع الوثيقة',
            'الرقم التسلسلي',
            'تاريخ الوثيقة (هـ)',
            'الميلادي',
            'الأيام المتبقية',
            'حالة الامتثال',
            'الموقف من التجديد',
            'تكلفة التجديد (ر.س)',
            'ملاحظات',
        ];

        $rows = $records->values()->map(static function (RegulatoryDocument $item, int $index): array {
            $days = $item->days_remaining;
            $daysText = match (true) {
                $days === null => 'وثيقة مفقودة',
                $days < 0 => 'منتهي منذ '.abs($days).' يوم',
                $days === 0 => 'ينتهي اليوم',
                default => 'متبقي '.$days.' يوم',
            };

            return [
                $index + 1,
                $item->entity_identifier,
                $item->equipment_type,
                $item->plate_number ?: '-',
                $item->transport_type,
                $item->branch,
                $item->document_type,
                $item->document_number,
                $item->hijri_expiry_date,
                $item->expiry_date?->format('d/m/Y'),
                $daysText,
                $item->compliance_status->label(),
                $item->renewal_status ?: '-',
                (float) $item->renewal_cost,
                $item->notes,
            ];
        });

        return $this->csvExportService->stream(
            filename: 'regulatory-documents-'.now()->format('Y-m-d').'.csv',
            auditableClass: RegulatoryDocument::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(RegulatoryDocument $document, bool $canViewAuditLogs): array
    {
        $daysRemaining = $document->days_remaining;
        $daysLabel = match (true) {
            $daysRemaining === null => 'غير محدد (وثيقة مفقودة)',
            $daysRemaining < 0 => sprintf('منتهي منذ %d يوم', abs($daysRemaining)),
            $daysRemaining === 0 => 'ينتهي اليوم',
            default => sprintf('متبقي %d يوم', $daysRemaining),
        };

        return [
            'id' => $document->id,
            'title' => sprintf('%s — %s', $document->document_type ?: 'وثيقة', $document->entity_identifier ?: '—'),
            'subtitle' => implode(' • ', array_filter([
                $document->equipment_type,
                $document->branch,
                $document->plate_number,
            ])) ?: ($document->entity_type?->label() ?? '—'),
            'status_label' => $document->compliance_status->label(),
            'status_badge' => $document->compliance_status->badgeClasses(),
            'fields' => [
                ['label' => 'كود المعدة / المعرّف', 'value' => $document->entity_identifier ?: '—'],
                ['label' => 'نوع المعدة', 'value' => $document->equipment_type ?: '—'],
                ['label' => 'رقم اللوحة', 'value' => $document->plate_number ?: '—'],
                ['label' => 'نوع النقل', 'value' => $document->transport_type ?: '—'],
                ['label' => 'الفرع الحالي', 'value' => $document->branch ?: '—'],
                ['label' => 'نوع الوثيقة', 'value' => $document->document_type ?: '—'],
                ['label' => 'الرقم التسلسلي / رقم الوثيقة', 'value' => $document->document_number ?: '—'],
                ['label' => 'التاريخ الهجري', 'value' => $document->hijri_expiry_date ?: '—'],
                ['label' => 'التاريخ الميلادي', 'value' => $document->expiry_date?->format('d/m/Y') ?? '—'],
                ['label' => 'الأيام المتبقية', 'value' => $daysLabel],
                ['label' => 'الموقف من التجديد', 'value' => $document->renewal_status ?: '—'],
                ['label' => 'تكلفة التجديد', 'value' => number_format((float) $document->renewal_cost, 2).' ر.س'],
                ['label' => 'ملاحظات', 'value' => $document->notes ?: '—', 'full' => true],
            ],
            'record' => [
                'id' => $document->id,
                'entity_type' => $document->entity_type?->value ?? 'equipment',
                'entity_identifier' => $document->entity_identifier ?? '',
                'equipment_type' => $document->equipment_type ?? '',
                'plate_number' => $document->plate_number ?? '',
                'transport_type' => $document->transport_type ?? '',
                'branch' => $document->branch ?? '',
                'document_type' => $document->document_type ?? '',
                'document_number' => $document->document_number ?? '',
                'issue_date' => $document->issue_date?->format('Y-m-d') ?? '',
                'hijri_expiry_date' => $document->hijri_expiry_date ?? '',
                'expiry_date' => $document->expiry_date?->format('Y-m-d') ?? '',
                'compliance_status' => $document->compliance_status->value,
                'renewal_status' => $document->renewal_status ?? '',
                'renewal_cost' => (string) $document->renewal_cost,
                'notes' => $document->notes ?? '',
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($document) : [],
        ];
    }
}
