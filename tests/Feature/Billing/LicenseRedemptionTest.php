<?php

use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\CreditService;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
});

it('redeems a valid key and grants credits', function () {
    $user = User::factory()->create();
    $key = LicenseKey::factory()->create(['credits' => 100]);

    $this->actingAs($user)
        ->post(route('billing.redeem'), ['code' => $key->code])
        ->assertRedirect();

    expect($key->fresh()->isRedeemed())->toBeTrue()
        ->and(app(CreditService::class)->balance($user))->toBe(150); // 50 initial + 100 redeemed
});

it('redeems a key with a plan upgrade', function () {
    $proPlan = Plan::where('slug', 'pro')->first();
    $user = User::factory()->create();
    $key = LicenseKey::factory()->forPlan($proPlan)->create(['credits' => 200]);

    $this->actingAs($user)
        ->post(route('billing.redeem'), ['code' => $key->code])
        ->assertRedirect();

    expect($user->fresh()->plan_id)->toBe($proPlan->id)
        ->and(app(CreditService::class)->balance($user))->toBe(250);
});

it('rejects an already-redeemed key', function () {
    $user = User::factory()->create();
    $key = LicenseKey::factory()->redeemed()->create();

    $this->actingAs($user)
        ->post(route('billing.redeem'), ['code' => $key->code])
        ->assertRedirect()
        ->assertSessionHasErrors('code');
});

it('rejects an expired key', function () {
    $user = User::factory()->create();
    $key = LicenseKey::factory()->expired()->create();

    $this->actingAs($user)
        ->post(route('billing.redeem'), ['code' => $key->code])
        ->assertRedirect()
        ->assertSessionHasErrors('code');
});

it('rejects an invalid key', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('billing.redeem'), ['code' => 'INVALID-CODE-XXXX-XXXX'])
        ->assertRedirect()
        ->assertSessionHasErrors('code');
});

it('only an admin can mint keys', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    // Non-admin should be forbidden
    $this->actingAs($user)
        ->post(route('admin.licenses.store'), [
            'count' => 5,
            'credits' => 100,
        ])
        ->assertForbidden();

    // Admin should succeed
    $this->actingAs($admin)
        ->post(route('admin.licenses.store'), [
            'count' => 5,
            'credits' => 100,
        ])
        ->assertRedirect();

    expect(LicenseKey::count())->toBe(5);
});
