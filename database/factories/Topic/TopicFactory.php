<?php

declare(strict_types=1);

namespace Database\Factories\Topic;

use App\Domains\Topic\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    /** @var class-string<Topic> */
    protected $model = Topic::class;

    /**
     * Define the model's default state.
     *
     * The slug is derived from whatever name the topic ends up with, so overriding
     * the name keeps the two in step.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => Str::title(rtrim(fake()->unique()->sentence(3), '.')),
            'slug' => fn (array $attributes) => Str::slug(Arr::string($attributes, 'name')),
        ];
    }
}
