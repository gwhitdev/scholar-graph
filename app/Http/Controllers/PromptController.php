<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromptController extends Controller
{
    /**
     * Update the project's system prompt settings.
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'system_prompt' => ['nullable', 'string', 'max:10000'],
            'use_global_prompt' => ['boolean'],
            'negative_prompt' => ['nullable', 'string', 'max:5000'],
        ]);

        $project->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Prompt updated.']);

        return redirect()->back();
    }
}
