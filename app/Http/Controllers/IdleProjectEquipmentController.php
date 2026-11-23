<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesModuleSpreadsheetImport;
use App\Http\Requests\IdleProjectEquipment\FilterIdleProjectEquipmentRequest;
use App\Http\Requests\IdleProjectEquipment\StoreIdleProjectEquipmentRequest;
use App\Http\Requests\IdleProjectEquipment\UpdateIdleProjectEquipmentRequest;
use App\Models\IdleProjectEquipment;
use App\Services\IdleProjectEquipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IdleProjectEquipmentController extends Controller
{
    use HandlesModuleSpreadsheetImport;

    public function __construct(
        private readonly IdleProjectEquipmentService $service,
    ) {}

    protected function importModuleKey(): string
    {
        return 'idle-project-equipment';
    }

    protected function importModelClass(): string
    {
        return IdleProjectEquipment::class;
    }

    public function index(FilterIdleProjectEquipmentRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $records = $this->service->paginate($filters);
        $filteredIdleDaysTotal = $this->service->filteredIdleDaysTotal($filters);
        $kpis = $this->service->kpis();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.idle-project-equipment._table', [
                    'records' => $records,
                    'filteredIdleDaysTotal' => $filteredIdleDaysTotal,
                ])->render(),
                'export_url' => route('idle-project-equipment.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.idle-project-equipment.index', [
            'records' => $records,
            'filteredIdleDaysTotal' => $filteredIdleDaysTotal,
            'kpis' => $kpis,
            'filters' => $filters,
            'options' => $this->service->filterOptions(),
        ]);
    }

    public function show(Request $request, IdleProjectEquipment $idleProjectEquipment): JsonResponse
    {
        Gate::authorize('view', $idleProjectEquipment);

        return response()->json(
            $this->service->detailPayload(
                $idleProjectEquipment,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreIdleProjectEquipmentRequest $request): JsonResponse|RedirectResponse
    {
        $equipment = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $equipment,
            ], 201);
        }

        return redirect()
            ->route('idle-project-equipment.index')
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateIdleProjectEquipmentRequest $request, IdleProjectEquipment $idleProjectEquipment): JsonResponse|RedirectResponse
    {
        $equipment = $this->service->update($idleProjectEquipment, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $equipment,
            ]);
        }

        return redirect()
            ->route('idle-project-equipment.index')
            ->with('status', 'تم التحديث بنجاح');
    }

    public function destroy(Request $request, IdleProjectEquipment $idleProjectEquipment): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $idleProjectEquipment);

        $this->service->delete($idleProjectEquipment);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('idle-project-equipment.index')
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterIdleProjectEquipmentRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
