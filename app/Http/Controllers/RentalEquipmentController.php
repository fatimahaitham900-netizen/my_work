<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesModuleSpreadsheetImport;
use App\Http\Requests\RentalEquipment\FilterRentalEquipmentRequest;
use App\Http\Requests\RentalEquipment\StoreRentalEquipmentRequest;
use App\Http\Requests\RentalEquipment\UpdateRentalEquipmentRequest;
use App\Models\RentalEquipment;
use App\Services\RentalEquipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RentalEquipmentController extends Controller
{
    use HandlesModuleSpreadsheetImport;

    public function __construct(
        private readonly RentalEquipmentService $service,
    ) {}

    protected function importModuleKey(): string
    {
        return 'rental-equipment';
    }

    protected function importModelClass(): string
    {
        return RentalEquipment::class;
    }

    public function index(FilterRentalEquipmentRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $records = $this->service->paginate($filters);
        $filteredAmountTotal = $this->service->filteredAmountTotal($filters);
        $kpis = $this->service->kpis();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.rental-equipment._table', [
                    'records' => $records,
                    'filteredAmountTotal' => $filteredAmountTotal,
                ])->render(),
                'export_url' => route('rental-equipment.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.rental-equipment.index', [
            'records' => $records,
            'filteredAmountTotal' => $filteredAmountTotal,
            'kpis' => $kpis,
            'filters' => $filters,
            'options' => $this->service->filterOptions(),
        ]);
    }

    public function show(Request $request, RentalEquipment $rentalEquipment): JsonResponse
    {
        Gate::authorize('view', $rentalEquipment);

        return response()->json(
            $this->service->detailPayload(
                $rentalEquipment,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreRentalEquipmentRequest $request): JsonResponse|RedirectResponse
    {
        $equipment = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $equipment,
            ], 201);
        }

        return redirect()
            ->route('rental-equipment.index')
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateRentalEquipmentRequest $request, RentalEquipment $rentalEquipment): JsonResponse|RedirectResponse
    {
        $equipment = $this->service->update($rentalEquipment, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $equipment,
            ]);
        }

        return redirect()
            ->route('rental-equipment.index')
            ->with('status', 'تم التحديث بنجاح');
    }

    public function destroy(Request $request, RentalEquipment $rentalEquipment): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $rentalEquipment);

        $this->service->delete($rentalEquipment);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('rental-equipment.index')
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterRentalEquipmentRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
