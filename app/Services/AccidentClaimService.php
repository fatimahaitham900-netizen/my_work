<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AccidentClaim;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccidentClaimService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<AccidentClaim>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = AccidentClaim::query();

        $recordType = trim((string) ($filters['record_type'] ?? ''));
        if (in_array($recordType, [AccidentClaim::TYPE_ACCIDENT, AccidentClaim::TYPE_INSURANCE_CLAIM], true)) {
            $query->where('record_type', $recordType);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('vehicle_code', 'like', "%{$search}%")
                    ->orWhere('user_code', 'like', "%{$search}%")
                    ->orWhere('plate_number', 'like', "%{$search}%")
                    ->orWhere('accident_location', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('record_holder', 'like', "%{$search}%")
                    ->orWhere('driver_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('najm_report_number', 'like', "%{$search}%")
                    ->orWhere('claim_number', 'like', "%{$search}%")
                    ->orWhere('insurance_company', 'like', "%{$search}%")
                    ->orWhere('claim_status', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        foreach (['branch', 'insurance_company', 'claim_status'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        return $query->orderByDesc('accident_date')->orderByDesc('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, AccidentClaim>
     */
    public function paginate(array $filters, int $perPage = 100): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @return array{total: int, accidents_count: int, claims_count: int, total_cost: string, total_compensation: string, open_claims: int}
     */
    public function kpis(): array
    {
        $accidentsCount = AccidentClaim::query()->where('record_type', AccidentClaim::TYPE_ACCIDENT)->count();
        $claimsCount = AccidentClaim::query()->where('record_type', AccidentClaim::TYPE_INSURANCE_CLAIM)->count();
        $total = $accidentsCount + $claimsCount;
        $totalCost = (float) AccidentClaim::query()->where('record_type', AccidentClaim::TYPE_INSURANCE_CLAIM)->sum('estimated_cost');
        $totalCompensation = (float) AccidentClaim::query()->where('record_type', AccidentClaim::TYPE_INSURANCE_CLAIM)->sum('compensation_amount');
        $openClaims = AccidentClaim::query()
            ->where('record_type', AccidentClaim::TYPE_INSURANCE_CLAIM)
            ->where(function (Builder $q): void {
                $q->whereNull('claim_status')
                    ->orWhere(function (Builder $sub): void {
                        $sub->where('claim_status', 'not like', '%مغلق%')
                            ->where('claim_status', 'not like', '%تم التعويض%');
                    });
            })
            ->count();

        return [
            'total' => $total,
            'accidents_count' => $accidentsCount,
            'claims_count' => $claimsCount,
            'total_cost' => number_format($totalCost, 0),
            'total_compensation' => number_format($totalCompensation, 0),
            'open_claims' => $openClaims,
        ];
    }

    /**
     * @return array{branches: list<string>, insurance_companies: list<string>, claim_statuses: list<string>}
     */
    public function filterOptions(): array
    {
        $distinct = static fn (string $column): array => AccidentClaim::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'branches' => $distinct('branch'),
            'insurance_companies' => $distinct('insurance_company'),
            'claim_statuses' => $distinct('claim_status'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AccidentClaim
    {
        $data['estimated_cost'] = $data['estimated_cost'] ?? 0;
        $data['compensation_amount'] = $data['compensation_amount'] ?? 0;

        $record = AccidentClaim::query()->create($data);
        DashboardService::clearCache();

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AccidentClaim $record, array $data): AccidentClaim
    {
        $data['estimated_cost'] = $data['estimated_cost'] ?? 0;
        $data['compensation_amount'] = $data['compensation_amount'] ?? 0;

        $record->update($data);
        DashboardService::clearCache();

        return $record->refresh();
    }

    public function delete(AccidentClaim $record): void
    {
        $record->delete();
        DashboardService::clearCache();
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(AccidentClaim $record, bool $includeAudit): array
    {
        return [
            'id' => $record->id,
            'record_type' => $record->record_type ?? AccidentClaim::TYPE_INSURANCE_CLAIM,
            'vehicle_code' => $record->vehicle_code ?? '',
            'user_code' => $record->user_code ?? '',
            'plate_number' => $record->plate_number ?? '',
            'accident_date' => $record->accident_date?->format('Y-m-d') ?? '',
            'accident_location' => $record->accident_location ?? '',
            'branch' => $record->branch ?? '',
            'record_holder' => $record->record_holder ?? '',
            'driver_name' => $record->driver_name ?? '',
            'employee_id' => $record->employee_id ?? '',
            'najm_report_number' => $record->najm_report_number ?? '',
            'claim_number' => $record->claim_number ?? '',
            'insurance_company' => $record->insurance_company ?? '',
            'fault_percentage' => $record->fault_percentage !== null ? (string) $record->fault_percentage : '',
            'estimated_cost' => (string) ((float) $record->estimated_cost),
            'compensation_amount' => (string) ((float) $record->compensation_amount),
            'claim_status' => $record->claim_status ?? '',
            'notes' => $record->notes ?? '',
            'audit_logs' => $includeAudit ? $this->auditLogPresenter->forModel($record) : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $headers = [
            'كود السيارة',
            'رقم اللوحة',
            'تاريخ الحادث',
            'موقع الحادث',
            'الفرع',
            'السائق',
            'الرقم الوظيفي',
            'رقم تقرير نجم',
            'رقم المطالبة',
            'شركة التأمين',
            'نسبة المسؤولية %',
            'التكلفة التقديرية',
            'مبلغ التعويض',
            'حالة الحادث / المطالبة',
            'ملاحظات',
        ];

        $rows = [];
        foreach ($this->filteredQuery($filters)->cursor() as $record) {
            $rows[] = [
                $record->vehicle_code ?? '',
                $record->plate_number ?? '',
                $record->accident_date?->format('Y-m-d') ?? '',
                $record->accident_location ?? '',
                $record->branch ?? '',
                $record->driver_name ?? '',
                $record->employee_id ?? '',
                $record->najm_report_number ?? '',
                $record->claim_number ?? '',
                $record->insurance_company ?? '',
                $record->fault_percentage !== null ? $record->fault_percentage.'%' : '',
                (float) $record->estimated_cost,
                (float) $record->compensation_amount,
                $record->claim_status ?? '',
                $record->notes ?? '',
            ];
        }

        return $this->csvExportService->stream(
            'accident-claims-'.now()->format('Y-m-d').'.csv',
            AccidentClaim::class,
            $headers,
            $rows,
        );
    }
}
