<?php

declare(strict_types=1);

namespace App\Domains\Quiz\Models;

use App\Domains\Generation\Models\QuizProposal;
use App\Domains\Question\Models\Question;
use App\Domains\Quiz\Enums\QuizStatus;
use App\Domains\Source\Models\Source;
use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Quiz\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An accepted proposal, expanded into a set of questions.
 *
 * @property int $id
 * @property int $user_id
 * @property int $topic_id
 * @property int $source_id
 * @property int $quiz_proposal_id
 * @property string $title
 * @property QuizStatus $status
 * @property string|null $failure_reason
 * @property CarbonImmutable|null $generated_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[UseFactory(QuizFactory::class)]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * The user who owns this quiz. Denormalised from the source to keep the due queue cheap.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The topic this quiz belongs to.
     *
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * The source this quiz was generated from.
     *
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * The proposal this quiz was accepted from.
     *
     * @return BelongsTo<QuizProposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(QuizProposal::class, 'quiz_proposal_id');
    }

    /**
     * The questions belonging to this quiz, in their stored order.
     *
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuizStatus::class,
            'generated_at' => 'datetime',
        ];
    }
}
