<?php

declare(strict_types=1);

namespace App\Domains\Topic\Models;

use App\Domains\Quiz\Models\Quiz;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Topic\TopicFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A user-owned subject area. Daily quizzes and review scheduling are scoped per topic.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[UseFactory(TopicFactory::class)]
class Topic extends Model
{
    /** @use HasFactory<TopicFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * The user who owns this topic.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The quizzes filed under this topic.
     *
     * @return HasMany<Quiz, $this>
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }
}
