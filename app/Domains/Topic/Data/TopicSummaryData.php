<?php

declare(strict_types=1);

namespace App\Domains\Topic\Data;

use App\Domains\Question\Models\Question;
use App\Domains\Topic\Models\Topic;
use Spatie\LaravelData\Data;

/**
 * A topic as the sources panel lists it, with the amount of material it now holds.
 */
class TopicSummaryData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly int $quizCount,
        public readonly int $questionCount,
    ) {}

    /**
     * Build the summary for one topic.
     *
     * A `quizzes_count` loaded by the caller is preferred over counting again, so a
     * listing can eager load its counts rather than paying for one query per row.
     */
    public static function fromModel(Topic $topic): self
    {
        $quizCount = $topic->getAttribute('quizzes_count') ?? $topic->quizzes()->count();

        return new self(
            id: $topic->id,
            name: $topic->name,
            slug: $topic->slug,
            quizCount: (int) $quizCount,
            questionCount: Question::query()
                ->whereIn('quiz_id', $topic->quizzes()->select('quizzes.id'))
                ->count(),
        );
    }
}
