<?php

declare(strict_types=1);

namespace App\Domains\Review\Models;

use App\Domains\Question\Models\Question;
use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\Review\ReviewStateFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The scheduling state of one question for one user. A missing row means the user
 * has never reviewed that question.
 *
 * @property int $id
 * @property int $user_id
 * @property int $question_id
 * @property int $topic_id
 * @property float $stability
 * @property float $difficulty
 * @property CarbonImmutable $due_at
 * @property CarbonImmutable $last_reviewed_at
 * @property int $reps
 * @property int $lapses
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[UseFactory(ReviewStateFactory::class)]
class ReviewState extends Model
{
    /** @use HasFactory<ReviewStateFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * The user this scheduling state belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The question being scheduled.
     *
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * The topic the question belongs to, denormalised to keep the due queue cheap.
     *
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Limit the query to states that have come due at or before the given moment.
     *
     * @param  Builder<self>  $query
     */
    public function scopeDueAt(Builder $query, CarbonInterface $moment): void
    {
        $query->where('due_at', '<=', $moment);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stability' => 'float',
            'difficulty' => 'float',
            'due_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
            'reps' => 'integer',
            'lapses' => 'integer',
        ];
    }
}
