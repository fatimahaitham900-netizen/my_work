<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BreakdownCategory;
use App\Enums\BreakdownSeverity;
use App\Enums\DriverStoppageStatus;
use App\Enums\RepairStatus;
use App\Http\Requests\EquipmentBreakdowns\FilterEquipmentBreakdownsRequest;
use App\Http\Requests\EquipmentBreakdowns\StoreEquipmentBreakdownRequest;
use App\Http\Requests\EquipmentBreakdowns\UpdateEquipmentBreakdownRequest;
use App\Models\EquipmentBreakdown;
use App\Services\EquipmentBreakdownService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EquipmentBreakdownController extends Controller
{
    public function __construct(
        private readonly EquipmentBreakdownService $service,
    ) {}

    public function index(FilterEquipmentBreakdownsRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $records = $this->service->paginate($filters);
        $kpis = $this->service->kpis();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.equipment-breakdowns._table', [
                    'records' => $records,
                ])->render(),
                'export_url' => route('equipment-breakdowns.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.equipment-breakdowns.index', [
            'records' => $records,
            'kpis' => $kpis,
            'filters' => $filters,
            'workshops' => $this->service->workshops(),
            'workshopStatuses' => $this->service->workshopStatuses(),
            'equipmentNames' => $this->service->equipmentNames(),
            'brands' => $this->service->brands(),
            'brandMatrix' => $this->service->buildWorkshopMatrix('brand'),
            'equipmentMatrix' => $this->service->buildWorkshopMatrix('equipment_name'),
            'categories' => BreakdownCategory::cases(),
            'severities' => BreakdownSeverity::cases(),
            'repairStatuses' => RepairStatus::cases(),
        ]);
    }

    public function show(Request $request, EquipmentBreakdown $equipmentBreakdown): JsonResponse
    {
        Gate::authorize('view', $equipmentBreakdown);

        return response()->json(
            $this->service->detailPayload(
                $equipmentBreakdown,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreEquipmentBreakdownRequest $request): JsonResponse|RedirectResponse
    {
        $breakdown = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $breakdown,
            ], 201);
        }

        return redirect()
            ->route('equipment-breakdowns.index')
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateEquipmentBreakdownRequest $request, EquipmentBreakdown $equipmentBreakdown): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $resolveLinkedStoppages = (bool) ($validated['resolve_linked_stoppages'] ?? false);
        $linkedStatus = DriverStoppageStatus::from((string) ($validated['linked_stoppage_status'] ?? DriverStoppageStatus::Resolved->value));

        $result = $this->service->update(
            $equipmentBreakdown,
            Arr::except($validated, ['resolve_linked_stoppages', 'linked_stoppage_status']),
            $resolveLinkedStoppages,
            $linkedStatus,
        );

        $message = $result['resolved_stoppages_count'] > 0
            ? sprintf('تم التحديث بنجاح وتم تحديث حالة %d سائق مرتبط بالعطل', $result['resolved_stoppages_count'])
            : 'تم التحديث بنجاح';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => $result['breakdown'],
                'resolved_stoppages_count' => $result['resolved_stoppages_count'],
            ]);
        }

        return redirect()
            ->route('equipment-breakdowns.index')
            ->with('status', $message);
    }

    public function destroy(Request $request, EquipmentBreakdown $equipmentBreakdown): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $equipmentBreakdown);

        $this->service->delete($equipmentBreakdown);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('equipment-breakdowns.index')
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterEquipmentBreakdownsRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
