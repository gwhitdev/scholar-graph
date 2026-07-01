<?php

namespace App\Providers;

use App\Services\OpenRouterService;
use App\Services\SemanticScholarService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SemanticScholarService::class, function () {
            return new SemanticScholarService(
                config('services.semantic_scholar.base_url') ?? 'https://api.semanticscholar.org',
            );
        });

        $this->app->singleton(OpenRouterService::class, function () {
            return new OpenRouterService(
                config('services.openrouter.key') ?? '',
                config('services.openrouter.model') ?? 'qwen-plus',
                config('services.openrouter.base_url') ?? 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1',
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->validateRequiredServices();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Validate that required third-party service credentials are configured.
     *
     * @throws ValidationException
     */
    protected function validateRequiredServices(): void
    {
        $missing = [];

        if (blank(config('services.openrouter.key'))) {
            $missing[] = 'services.openrouter.key';
        }

        if (blank(config('services.openrouter.model'))) {
            $missing[] = 'services.openrouter.model';
        }

        if ($missing === []) {
            return;
        }

        $message = 'Missing required service configuration: '.implode(', ', $missing);

        if (app()->isProduction()) {
            throw ValidationException::withMessages([
                'services' => $message,
            ]);
        }

        Log::warning($message);
    }
}
