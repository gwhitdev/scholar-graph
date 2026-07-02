<?php

namespace App\Http\Controllers;

use App\Services\Admin\AdminMetricsService;
use App\Services\OpenRouterService;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function dashboard(AdminMetricsService $metrics, OpenRouterService $openRouter): Response
    {
        return Inertia::render('admin/dashboard', [
            'userCount' => $metrics->userCount(),
            'paperCount' => $metrics->paperCount(),
            'savedPaperCount' => $metrics->savedPaperCount(),
            'topSearchTerms' => $metrics->topSearchTerms(),
            'llmUsageTotals' => $metrics->llmUsageTotals(),
            'apiUsageBySource' => $metrics->apiUsageBySource(),
            'creditBalance' => $openRouter->getKeyUsage(),
        ]);
    }

    public function users(AdminMetricsService $metrics): Response
    {
        return Inertia::render('admin/users', [
            'perUserUsage' => $metrics->perUserUsage(),
        ]);
    }

    public function usage(AdminMetricsService $metrics): Response
    {
        return Inertia::render('admin/usage', [
            'apiUsageBySource' => $metrics->apiUsageBySource(),
            'llmUsageByModel' => $metrics->llmUsageByModel(),
            'llmUsageTotals' => $metrics->llmUsageTotals(),
        ]);
    }
}
