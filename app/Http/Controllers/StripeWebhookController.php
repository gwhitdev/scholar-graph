<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Billing\CreditService;
use Illuminate\Http\Response;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class StripeWebhookController extends WebhookController
{
    public function __construct(
        protected CreditService $creditService,
    ) {}

    /**
     * Handle a checkout session completed event.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCheckoutSessionCompleted(array $payload): SymfonyResponse
    {
        $session = $payload['data']['object'] ?? [];
        $metadata = $session['metadata'] ?? [];

        $userId = $metadata['user_id'] ?? null;
        $credits = (int) ($metadata['credits'] ?? 0);

        if (! $userId || $credits <= 0) {
            return new Response('Webhook handled', 200);
        }

        $user = User::find($userId);

        if (! $user) {
            return new Response('Webhook handled', 200);
        }

        $this->creditService->grant($user, $credits, 'purchase', [
            'stripe_session_id' => $session['id'] ?? null,
        ]);

        return new Response('Webhook handled', 200);
    }
}
