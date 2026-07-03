<?php

namespace App\Http\Controllers;

use App\Actions\Syntheses\CreateSynthesisAction;
use App\Exceptions\InsufficientCreditsException;
use App\Exceptions\OpenRouterException;
use App\Exceptions\OpenRouterTimeoutException;
use App\Http\Requests\StoreChatMessageRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function store(StoreChatMessageRequest $request, Project $project, CreateSynthesisAction $action): RedirectResponse
    {
        $this->authorize('update', $project);

        try {
            $action->handle($project, $request->validated('question'));
        } catch (InsufficientCreditsException) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Out of credits — upgrade or redeem a key.',
            ]);
        } catch (OpenRouterTimeoutException) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'The model took too long to respond. Please try again.',
            ]);
        } catch (OpenRouterException $e) {
            report($e);
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'The model failed to respond. Please try again.',
            ]);
        }

        return redirect()->back();
    }
}
