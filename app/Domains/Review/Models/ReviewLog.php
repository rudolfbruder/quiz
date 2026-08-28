<?php

declare(strict_types=1);

namespace App\Domains\Review\Models;

use App\Domains\Attempt\Models\Attempt;
use App\Domains\Attempt\Models\Response;
use App\Domains\Question\Models\Question;
use App\Domains\Review\Enums\Rating;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Review\ReviewLogFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One graded review, recorded once and never changed. The log is complete enough to
 * replay every review in order, so review states can be recomputed from it.
 *
 * @property int $id
 * @property int $user_id
 * @property int $question_id
 * @property int|null $attempt_id
 * @property int|null $response_id
 * @property Rating $rating
 * @property float|null $stability_before
 * @property float|null $difficulty_before
 * @property float $stability_after
 * @property float $difficulty_after
 * @property float|null $elapsed_days
 * @property float $scheduled_days
 * @property int|null $answered_in_milliseconds
 * @property CarbonImmutable $reviewed_at
 */
#[UseFactory(ReviewLogFactory::class)]
class ReviewLog extends Model
{
    /** @use HasFactory<ReviewLogFactory> */
    use HasFactory;

    /**
     * The explicit reviewed-at column replaces the usual pair of timestamps.
     */
    public $timestamps = false;

    protected $guarded = [];

    /**
     * The user who was reviewed.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The question that was reviewed.
     *
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * The attempt the review happened inside, if it came from one.
     *
     * @return BelongsTo<Attempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    /**
     * The response that was graded, if the review came from one.
     *
     * @return BelongsTo<Response, $this>
     */
    public function response(): BelongsTo
    {
        return $this->belongsTo(Response::class);
    }

    /**
     * Keep the log append-only by refusing every write to an entry that already exists.
     */
    protected static function booted(): void
    {
        static::updating(function (self $log): void {
            throw new LogicException('Review log entries are append-only and cannot be updated.');
        });

        static::deleting(function (self $log): void {
            throw new LogicException('Review log entries are append-only and cannot be deleted.');
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => Rating::class,
            'stability_before' => 'float',
            'difficulty_before' => 'float',
            'stability_after' => 'float',
            'difficulty_after' => 'float',
            'elapsed_days' => 'float',
            'scheduled_days' => 'float',
            'answered_in_milliseconds' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }
}
