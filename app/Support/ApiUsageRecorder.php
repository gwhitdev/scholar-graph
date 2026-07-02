<?php

namespace App\Support;

use App\Models\ApiUsageLog;
use App\Models\User;

class ApiUsageRecorder
{
    public static function record(
        string $service,
        string $endpoint,
        ?int $status,
        int $durationMs,
        ?User $user = null,
        ?string $method = null,
    ): void {
        ApiUsageLog::create([
            'user_id' => $user?->id,
            'source' => 'external',
            'service' => $service,
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $status,
            'duration_ms' => $durationMs,
        ]);
    }
}
