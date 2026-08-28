<?php

declare(strict_types=1);

use App\Domains\Source\Models\Source;
use App\Models\User;

it('returns only the user\'s own sources, most recent first', function (): void {
    $user = User::factory()->create();

    $oldest = Source::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(3)]);
    $newest = Source::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDay()]);
    $somebodyElses = Source::factory()->create();

    $sources = $user->sources()->get();

    expect($sources->pluck('id')->all())->toBe([$newest->id, $oldest->id])
        ->and($sources->pluck('id'))->not->toContain($somebodyElses->id);
});
