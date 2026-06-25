<?php

namespace App\Services;

use RuntimeException;

class MediaInUseException extends RuntimeException
{
    /**
     * @param  array<int, array<string, mixed>>  $usages
     */
    public function __construct(
        string $message,
        public readonly array $usages = [],
    ) {
        parent::__construct($message);
    }
}
