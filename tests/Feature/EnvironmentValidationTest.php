<?php

use App\Providers\AppServiceProvider;
use Illuminate\Validation\ValidationException;

test('openrouter and semantic scholar config values are readable', function () {
    config(['services.openrouter.key' => 'test-openrouter-key']);
    config(['services.openrouter.model' => 'qwen/test-model']);
    config(['services.semantic_scholar.base_url' => 'https://test.semanticscholar.org']);

    expect(config('services.openrouter.key'))->toBe('test-openrouter-key');
    expect(config('services.openrouter.model'))->toBe('qwen/test-model');
    expect(config('services.semantic_scholar.base_url'))->toBe('https://test.semanticscholar.org');
});

test('boot guard throws when openrouter key is missing in production', function () {
    config(['services.openrouter.key' => null]);
    config(['services.openrouter.model' => 'qwen/test-model']);
    app()->detectEnvironment(fn () => 'production');

    $provider = new AppServiceProvider(app());
    $method = new ReflectionMethod($provider, 'validateRequiredServices');

    $method->invoke($provider);
})->throws(ValidationException::class);

test('boot guard throws when openrouter model is missing in production', function () {
    config(['services.openrouter.key' => 'test-openrouter-key']);
    config(['services.openrouter.model' => null]);
    app()->detectEnvironment(fn () => 'production');

    $provider = new AppServiceProvider(app());
    $method = new ReflectionMethod($provider, 'validateRequiredServices');

    $method->invoke($provider);
})->throws(ValidationException::class);
