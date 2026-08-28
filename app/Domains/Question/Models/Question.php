<?php

declare(strict_types=1);

namespace App\Domains\Question\Models;

use App\Domains\Question\Enums\QuestionType;
use App\Domains\Quiz\Models\Quiz;
use Carbon\CarbonImmutable;
use Database\Factories\Question\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One question of a quiz, with its answer options and explanation.
 *
 * @property int $id
 * @property int $quiz_id
 * @property QuestionType $type
 * @property string $text
 * @property string $explanation
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[UseFactory(QuestionFactory::class)]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * The quiz this question belongs to.
     *
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * The answer options offered for this question, in their stored order.
     *
     * @return HasMany<Option, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(Option::class)->orderBy('position');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'position' => 'integer',
        ];
    }
}
