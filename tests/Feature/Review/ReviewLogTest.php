<?php

declare(strict_types=1);

use App\Domains\Review\Enums\Rating;
use App\Domains\Review\Models\ReviewLog;
use Carbon\CarbonInterface;

it('refuses to update a log entry that has already been written', function (): void {
    $log = ReviewLog::factory()->create();

    expect(fn () => $log->update(['rating' => Rating::Again]))
        ->toThrow(LogicException::class);

    expect($log->fresh()->rating)->toBe(Rating::Good);
});

it('refuses to delete a log entry', function (): void {
    $log = ReviewLog::factory()->create();

    expect(fn () => $log->delete())->toThrow(LogicException::class)
        ->and(ReviewLog::query()->count())->toBe(1);
});

it('casts the rating and the numeric columns', function (): void {
    $log = ReviewLog::factory()->create()->fresh();

    expect($log->rating)->toBe(Rating::Good)
        ->and($log->stability_after)->toBeFloat()
        ->and($log->difficulty_after)->toBeFloat()
        ->and($log->scheduled_days)->toBeFloat()
        ->and($log->reviewed_at)->toBeInstanceOf(CarbonInterface::class);
});

it('records a first review with no prior state', function (): void {
    $log = ReviewLog::factory()->first()->create()->fresh();

    expect($log->stability_before)->toBeNull()
        ->and($log->difficulty_before)->toBeNull()
        ->and($log->elapsed_days)->toBeNull()
        ->and($log->stability_after)->toBeFloat();
});

it('carries the explicit reviewed-at column instead of timestamps', function (): void {
    $log = ReviewLog::factory()->create();

    expect($log->timestamps)->toBeFalse()
        ->and($log->getAttributes())->not->toHaveKey('created_at')
        ->and($log->getAttributes())->not->toHaveKey('updated_at');
});
