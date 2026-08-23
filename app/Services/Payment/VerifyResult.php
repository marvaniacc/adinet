<?php

namespace App\Services\Payment;

class VerifyResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $refId = null,
        public readonly ?string $message = null,
    ) {}
}
