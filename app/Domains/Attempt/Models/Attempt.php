<?php

declare(strict_types=1);

namespace App\Domains\Attempt\Models;

use App\Domains\Attempt\Enums\AttemptKind;
use App\Domains\Quiz\Models\Quiz;
use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Attempt\AttemptFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One quiz served to one user, together with the questions it contained.
 *
 * @property int $id
 * @property int $user_id
 * @property int $topic_id
 * @property int|null $quiz_id
 * @property AttemptKind $kind
 * @property CarbonImmutable|null $served_on
 * @property int $question_count
 * @property int|null $correct_count
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[UseFactory(AttemptFactory::class)]
class Attempt extends Model
{
    /** @use HasFactory<AttemptFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * The user this attempt was served to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The topic this attempt covers.
     *
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * The quiz a practice attempt was launched from, if any.
     *
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Every question served within this attempt, answered or not.
     *
     * @return HasMany<Response, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class)->orderBy('position');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => AttemptKind::class,
            'served_on' => 'date',
            'question_count' => 'integer',
            'correct_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
