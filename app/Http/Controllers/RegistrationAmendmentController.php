<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesModuleSpreadsheetImport;
use App\Http\Requests\RegistrationAmendments\FilterRegistrationAmendmentsRequest;
use App\Http\Requests\RegistrationAmendments\StoreRegistrationAmendmentRequest;
use App\Http\Requests\RegistrationAmendments\UpdateRegistrationAmendmentRequest;
use App\Models\RegistrationAmendment;
use App\Services\RegistrationAmendmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationAmendmentController extends Controller
{
    use HandlesModuleSpreadsheetImport;

    public function __construct(
        private readonly RegistrationAmendmentService $service,
    ) {}

    protected function importModuleKey(): string
    {
        return 'registration-amendments';
    }

    protected function importModelClass(): string
    {
        return RegistrationAmendment::class;
    }

    public function index(FilterRegistrationAmendmentsRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $records = $this->service->paginate($filters);
        $kpis = $this->service->kpis();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.registration-amendments._table', [
                    'records' => $records,
                ])->render(),
                'export_url' => route('registration-amendments.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.registration-amendments.index', [
            'records' => $records,
            'kpis' => $kpis,
            'filters' => $filters,
            'options' => $this->service->filterOptions(),
        ]);
    }

    public function show(Request $request, RegistrationAmendment $registrationAmendment): JsonResponse
    {
        Gate::authorize('view', $registrationAmendment);

        return response()->json(
            $this->service->detailPayload(
                $registrationAmendment,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreRegistrationAmendmentRequest $request): JsonResponse|RedirectResponse
    {
        $amendment = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $amendment,
            ], 201);
        }

        return redirect()
            ->route('registration-amendments.index')
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateRegistrationAmendmentRequest $request, RegistrationAmendment $registrationAmendment): JsonResponse|RedirectResponse
    {
        $amendment = $this->service->update($registrationAmendment, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $amendment,
            ]);
        }

        return redirect()
            ->route('registration-amendments.index')
            ->with('status', 'تم التحديث بنجاح');
    }

    public function destroy(Request $request, RegistrationAmendment $registrationAmendment): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $registrationAmendment);

        $this->service->delete($registrationAmendment);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('registration-amendments.index')
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterRegistrationAmendmentsRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}

