<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\SynthesisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromptSettingsController extends Controller
{
    /**
     * Show the global prompt settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/prompt', [
            'globalSystemPrompt' => $request->user()->global_system_prompt,
            'globalNegativePrompt' => $request->user()->global_negative_prompt,
            'defaultPrompt' => SynthesisService::DEFAULT_RESPONSE_GUIDELINES,
        ]);
    }

    /**
     * Update the user's global system prompt.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'global_system_prompt' => ['nullable', 'string', 'max:10000'],
            'global_negative_prompt' => ['nullable', 'string', 'max:5000'],
        ]);

        $request->user()->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Global prompt updated.']);

        return to_route('prompt.edit');
    }
}
