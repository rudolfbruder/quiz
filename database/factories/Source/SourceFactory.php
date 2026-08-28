<?php

declare(strict_types=1);

namespace Database\Factories\Source;

use App\Domains\Source\Enums\SourceStatus;
use App\Domains\Source\Models\Source;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    /** @var class-string<Source> */
    protected $model = Source::class;

    /**
     * Define the model's default state.
     *
     * A freshly uploaded source is pending, has no page count yet, and carries no
     * failure reason.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::slug(rtrim(fake()->sentence(3), '.'));

        return [
            'user_id' => User::factory(),
            'topic_id' => null,
            'disk' => 'local',
            'path' => 'sources/'.fake()->uuid().'.pdf',
            'original_name' => $name.'.pdf',
            'media_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(50_000, 4_000_000),
            'page_count' => null,
            'extracted_text' => null,
            'provider_file_id' => null,
            'status' => SourceStatus::Pending,
            'failure_reason' => null,
            'analysed_at' => null,
        ];
    }

    /**
     * A source that has been analysed successfully.
     */
    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SourceStatus::Ready,
            'page_count' => fake()->numberBetween(1, (int) config('generation.max_pages')),
            'failure_reason' => null,
            'analysed_at' => now(),
        ]);
    }

    /**
     * A source whose analysis broke.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SourceStatus::Failed,
            'failure_reason' => 'The document could not be analysed because the provider returned an error.',
            'analysed_at' => now(),
        ]);
    }

    /**
     * A source refused after analysis for exceeding the page limit.
     */
    public function rejected(): static
    {
        $limit = (int) config('generation.max_pages');
        $pageCount = $limit + fake()->numberBetween(1, 20);

        return $this->state(fn (array $attributes) => [
            'status' => SourceStatus::Rejected,
            'page_count' => $pageCount,
            'failure_reason' => "The document has {$pageCount} pages, which is above the limit of {$limit}.",
            'analysed_at' => now(),
        ]);
    }
}
