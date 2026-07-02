<?php

use App\Http\Controllers\StripeWebhookController;
use App\Models\User;
use App\Services\Billing\CreditService;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
});

it('requires authentication to create a checkout session', function () {
    $this->post(route('billing.checkout'), ['pack' => 'starter'])
        ->assertRedirect(route('login'));
});

it('validates the pack parameter', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('billing.checkout'), ['pack' => 'invalid'])
        ->assertSessionHasErrors('pack');
});

it('grants credits via webhook handler', function () {
    $user = User::factory()->create();
    $service = app(CreditService::class);

    // Directly test the webhook handler method (bypassing Stripe signature verification)
    $controller = app(StripeWebhookController::class);

    $payload = [
        'data' => [
            'object' => [
                'id' => 'cs_test_123',
                'metadata' => [
                    'user_id' => $user->id,
                    'credits' => 100,
                ],
            ],
        ],
    ];

    // Use reflection to call the protected method
    $method = new ReflectionMethod($controller, 'handleCheckoutSessionCompleted');
    $method->setAccessible(true);
    $method->invoke($controller, $payload);

    expect($service->balance($user))->toBe(150); // 50 initial + 100 purchase
});
