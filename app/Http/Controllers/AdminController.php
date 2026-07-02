<?php

namespace App\Http\Controllers;

use App\Actions\Billing\GenerateLicenseKeysAction;
use App\Http\Requests\MintLicenseKeysRequest;
use App\Models\LicenseKey;
use App\Services\Admin\AdminMetricsService;
use App\Services\OpenRouterService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
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

    public function licenses(): Response
    {
        return Inertia::render('admin/licenses', [
            'licenseKeys' => LicenseKey::with('plan', 'redeemedByUser')->latest()->limit(100)->get(),
        ]);
    }

    public function mintLicenseKeys(MintLicenseKeysRequest $request, GenerateLicenseKeysAction $action): RedirectResponse
    {
        $action->handle(
            count: $request->validated('count'),
            planId: $request->validated('plan_id'),
            credits: $request->validated('credits'),
            expiresAt: $request->validated('expires_at') ? Carbon::parse($request->validated('expires_at')) : null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Licence keys minted successfully.']);

        return to_route('admin.licenses.index');
    }
}
