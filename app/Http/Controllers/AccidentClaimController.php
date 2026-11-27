<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AccidentClaims\FilterAccidentClaimsRequest;
use App\Http\Requests\AccidentClaims\StoreAccidentClaimRequest;
use App\Http\Requests\AccidentClaims\UpdateAccidentClaimRequest;
use App\Models\AccidentClaim;
use App\Services\AccidentClaimService;
use App\Services\ModuleSpreadsheetImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AccidentClaimController extends Controller
{
    public function __construct(
        private readonly AccidentClaimService $service,
        private readonly ModuleSpreadsheetImportService $importService,
    ) {}

    public function index(FilterAccidentClaimsRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $activeTab = ($filters['record_type'] ?? AccidentClaim::TYPE_ACCIDENT) === AccidentClaim::TYPE_INSURANCE_CLAIM
            ? AccidentClaim::TYPE_INSURANCE_CLAIM
            : AccidentClaim::TYPE_ACCIDENT;
        $filters['record_type'] = $activeTab;

        $records = $this->service->paginate($filters);
        $kpis = $this->service->kpis();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.accident-claims._table', [
                    'records' => $records,
                    'activeTab' => $activeTab,
                ])->render(),
                'export_url' => route('accident-claims.export', array_filter($filters)),
                'total' => $records->total(),
            ]);
        }

        return view('modules.accident-claims.index', [
            'records' => $records,
            'kpis' => $kpis,
            'filters' => $filters,
            'activeTab' => $activeTab,
            'options' => $this->service->filterOptions(),
        ]);
    }

    public function show(Request $request, AccidentClaim $accidentClaim): JsonResponse
    {
        Gate::authorize('view', $accidentClaim);

        return response()->json(
            $this->service->detailPayload(
                $accidentClaim,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreAccidentClaimRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json([
            'message' => 'تم تسجيل الحادث ومطالبة التأمين بنجاح',
            'data' => $this->service->detailPayload($record, false),
        ], 201);
    }

    public function update(UpdateAccidentClaimRequest $request, AccidentClaim $accidentClaim): JsonResponse
    {
        $record = $this->service->update($accidentClaim, $request->validated());

        return response()->json([
            'message' => 'تم تحديث سجل الحادث ومطالبة التأمين بنجاح',
            'data' => $this->service->detailPayload($record, false),
        ]);
    }

    public function destroy(AccidentClaim $accidentClaim): JsonResponse
    {
        Gate::authorize('delete', $accidentClaim);

        $this->service->delete($accidentClaim);

        return response()->json([
            'message' => 'تم حذف السجل بنجاح',
        ]);
    }

    public function export(FilterAccidentClaimsRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }

    public function downloadTemplate(): StreamedResponse
    {
        Gate::authorize('manage-deviations');

        return $this->importService->generateTemplateStream('accident-claims');
    }

    public function directImport(Request $request): RedirectResponse
    {
        Gate::authorize('manage-deviations');

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
            'record_type' => ['nullable', 'string', 'in:accident,insurance_claim'],
        ], [
            'file.required' => 'يرجى اختيار ملف CSV أو Excel لرفع البيانات.',
            'file.mimes' => 'صيغة الملف غير مدعومة. الصيغ المدعومة: CSV, XLSX, XLS.',
        ]);

        $recordType = $validated['record_type'] ?? AccidentClaim::TYPE_ACCIDENT;

        try {
            $result = $this->importService->importDirect('accident-claims', $validated['file'], [
                'record_type' => $recordType,
            ]);
        } catch (Throwable $e) {
            return redirect()
                ->route('accident-claims.index', ['record_type' => $recordType])
                ->withErrors(['file' => 'تعذر قراءة الملف المرفق: '.$e->getMessage()]);
        }

        if ($result['imported'] === 0) {
            return redirect()
                ->route('accident-claims.index', ['record_type' => $recordType])
                ->withErrors(['file' => 'لم يتم العثور على صفوف صالحة للاستيراد داخل الملف.']);
        }

        $label = $recordType === AccidentClaim::TYPE_ACCIDENT ? 'الحوادث' : 'مطالبات التأمين';

        return redirect()
            ->route('accident-claims.index', ['record_type' => $recordType])
            ->with('status', "تم استيراد {$result['imported']} سجل في {$label} بنجاح (وحذف {$result['deleted']} سجل قديم).");
    }

    public function purgeAll(Request $request): RedirectResponse
    {
        Gate::authorize('delete-deviations');

        $recordType = $request->input('record_type');
        $context = in_array($recordType, [AccidentClaim::TYPE_ACCIDENT, AccidentClaim::TYPE_INSURANCE_CLAIM], true)
            ? ['record_type' => $recordType]
            : [];

        $deleted = $this->importService->purgeAll('accident-claims', $context);

        return redirect()
            ->route('accident-claims.index', $context)
            ->with('status', "تم تفريغ الجدول وحذف جميع البيانات ({$deleted} سجل) بنجاح.");
    }
}
