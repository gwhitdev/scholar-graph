<?php

namespace App\Services;

class OpenAlexSearchService
{
    public function __construct(
        protected string $baseUrl,
        protected string $mailto,
    ) {}
}
