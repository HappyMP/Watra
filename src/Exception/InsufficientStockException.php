<?php

declare(strict_types=1);

namespace App\Exception;

final class InsufficientStockException extends \RuntimeException
{
    public function __construct(string $variantCode, int $available, int $requested)
    {
        parent::__construct(sprintf(
            'Insufficient stock for variant "%s": available=%d, requested=%d.',
            $variantCode,
            $available,
            $requested,
        ));
    }
}
