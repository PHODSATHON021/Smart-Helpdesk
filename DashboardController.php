<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\DashboardService;

final class DashboardController
{
    public function __construct(private DashboardService $service, private View $view) {}

    public function index(Request $r, array $p = []): Response
    {
        return $this->view->render('admin/dashboard', ['stats' => $this->service->getStatistics()]);
    }
}
