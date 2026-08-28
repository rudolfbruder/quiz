<?php

declare(strict_types=1);

use App\Domains\Source\Enums\SourceStatus;

it('is string backed and holds exactly the source lifecycle cases', function (): void {
    expect(array_map(fn (SourceStatus $case): string => $case->value, SourceStatus::cases()))
        ->toBe(['pending', 'processing', 'ready', 'failed', 'rejected']);
});
