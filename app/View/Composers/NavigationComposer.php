<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Services\DashboardService;
use Illuminate\View\View;

class NavigationComposer
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function compose(View $view): void
    {
        $view->with('navAlerts', $this->dashboardService->getNavigationAlertSummary());
    }
}
