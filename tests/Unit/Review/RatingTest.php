<?php

declare(strict_types=1);

use App\Domains\Review\Enums\Rating;

it('is integer backed with the four grades the review log stores', function (): void {
    expect(array_map(fn (Rating $case): int => $case->value, Rating::cases()))
        ->toBe([1, 2, 3, 4]);

    expect(Rating::Again->value)->toBe(1)
        ->and(Rating::Hard->value)->toBe(2)
        ->and(Rating::Good->value)->toBe(3)
        ->and(Rating::Easy->value)->toBe(4);
});

it('treats every grade but again as a recall', function (): void {
    expect(Rating::Again->isRecalled())->toBeFalse()
        ->and(Rating::Hard->isRecalled())->toBeTrue()
        ->and(Rating::Good->isRecalled())->toBeTrue()
        ->and(Rating::Easy->isRecalled())->toBeTrue();
});
