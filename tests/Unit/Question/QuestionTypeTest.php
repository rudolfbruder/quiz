<?php

declare(strict_types=1);

use App\Domains\Question\Enums\QuestionType;

it('defines single choice as the only question type', function (): void {
    expect(array_map(fn (QuestionType $case): string => $case->value, QuestionType::cases()))
        ->toBe(['single_choice']);
});
