<?php

namespace App\Http\Middleware;

use App\Models\ApiUsageLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiUsage
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        if (! $request->user()) {
            return $response;
        }

        if ($this->shouldSkip($request)) {
            return $response;
        }

        ApiUsageLog::create([
            'user_id' => $request->user()->id,
            'source' => 'internal',
            'service' => 'app',
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
        ]);

        return $response;
    }

    protected function shouldSkip(Request $request): bool
    {
        $path = $request->path();

        return $path === 'up'
            || str_starts_with($path, '_debugbar')
            || str_starts_with($path, 'vendor')
            || str_starts_with($path, 'build')
            || str_starts_with($path, 'storage')
            || str_starts_with($path, 'hot');
    }
}
