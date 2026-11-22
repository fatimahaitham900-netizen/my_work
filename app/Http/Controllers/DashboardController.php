<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DashboardMetric;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(Request $request): View
    {
        $forceRefresh = $request->boolean('refresh');
        $summary = $this->dashboardService->getSummary($forceRefresh);

        return view('dashboard.index', [
            'summary' => $summary,
        ]);
    }

    public function refresh(): RedirectResponse
    {
        DashboardService::clearCache();

        return redirect()
            ->route('dashboard')
            ->with('status', 'تم تحديث مؤشرات لوحة القيادة بنجاح.');
    }

    public function updateMetric(Request $request, string $key): JsonResponse|RedirectResponse
    {
        abort_unless((bool) $request->user()?->canManageDeviations(), 403);

        $allowedKeys = [
            DashboardMetric::KEY_REGISTRATION_AMENDMENT => 'معدات تحتاج إلى تعديل استمارة',
            DashboardMetric::KEY_DIESEL_CONTROL => 'معدلات استهلاك الديزل والرقابة على الصرف',
            DashboardMetric::KEY_CARS_EXPIRED_INSPECTION => 'سيارات منتهي فحصها الدوري',
            DashboardMetric::KEY_HIGH_OIL_CONSUMPTION => 'تزويد زيوت للمعدات الذي صرفها عالي',
            DashboardMetric::KEY_TOTAL_RENTAL_EQUIPMENT => 'إجمالي معدات الإيجار بالمشاريع',
            DashboardMetric::KEY_EQUIPMENT_INVESTIGATIONS => 'تحقيقات إدارة المعدات',
            DashboardMetric::KEY_MONTHLY_CERTIFICATE_DELAYS => 'التأخر في المستخلصات الشهرية',
            DashboardMetric::KEY_MONTH_12_TARGETS => 'مستهدفات المشاريع شهر 12',
            DashboardMetric::KEY_DEPARTMENT_PLAN => 'خطة إدارة المعدات',
            DashboardMetric::KEY_DIESEL_FUNDING_PROJECTS => 'المشاريع التي تحتاج لها تمويل ديزل',
        ];

        $validated = $request->validate([
            'value' => ['nullable', 'string', 'max:120'],
        ]);

        abort_unless(array_key_exists($key, $allowedKeys), 404);

        $rawValue = trim((string) ($validated['value'] ?? ''));
        if ($rawValue === '') {
            $rawValue = $key === DashboardMetric::KEY_DIESEL_CONTROL ? 'لا يوجد' : '0';
        }

        $metric = DashboardMetric::query()->updateOrCreate(
            ['key' => $key],
            [
                'title' => $allowedKeys[$key],
                'value' => $rawValue,
            ],
        );

        DashboardService::clearCache();

        $displayValue = is_numeric($metric->value)
            ? number_format((float) $metric->value)
            : $metric->value;

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم تحديث المؤشر بنجاح',
                'raw_value' => $metric->value,
                'display_value' => $displayValue,
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'تم تحديث المؤشر بنجاح.');
    }
}
