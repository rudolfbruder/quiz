<?php

declare(strict_types=1);

use App\Domains\Attempt\Enums\AttemptKind;

it('distinguishes daily and practice attempts', function (): void {
    expect(array_map(fn (AttemptKind $case): string => $case->value, AttemptKind::cases()))
        ->toBe(['daily', 'practice']);
});
