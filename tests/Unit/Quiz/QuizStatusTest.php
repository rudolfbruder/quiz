<?php

declare(strict_types=1);

use App\Domains\Quiz\Enums\QuizStatus;

it('is string backed and holds exactly the quiz lifecycle cases', function (): void {
    expect(array_map(fn (QuizStatus $case): string => $case->value, QuizStatus::cases()))
        ->toBe(['pending', 'processing', 'ready', 'failed']);
});
