<?php

declare(strict_types=1);

namespace App\Domains\Source\Data;

use App\Domains\Topic\Data\TopicSummaryData;
use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * Everything the sources panel renders for one person.
 *
 * This is the whole prop set of `source/Index`, so `sources` stays a top-level
 * key and the page can reload it on its own while generation is running.
 *
 * Both listings are scoped to the person building them, and both eager load the
 * counts they display: `SourceData` and `TopicSummaryData` prefer a count the
 * caller has already loaded, so the panel costs a fixed number of queries rather
 * than one per row.
 */
class SourcePanelData extends Data
{
    /**
     * @param  array<int, SourceData>  $sources
     * @param  array<int, TopicSummaryData>  $topics
     */
    public function __construct(
        #[DataCollectionOf(SourceData::class)]
        public readonly array $sources,
        #[DataCollectionOf(TopicSummaryData::class)]
        public readonly array $topics,
        public readonly UploadLimitsData $limits,
    ) {}

    /**
     * Build the panel for one person, and for nobody else.
     */
    public static function forUser(User $user): self
    {
        $sources = $user->sources()
            ->with(['quiz' => fn ($query) => $query->withCount('questions')])
            ->get();

        $topics = Topic::query()
            ->where('user_id', $user->id)
            ->withCount('quizzes')
            ->orderBy('name')
            ->get();

        return new self(
            sources: $sources->map(SourceData::fromModel(...))->all(),
            topics: $topics->map(TopicSummaryData::fromModel(...))->all(),
            limits: UploadLimitsData::fromConfig(),
        );
    }
}
