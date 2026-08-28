<?php

declare(strict_types=1);

namespace Database\Factories\Generation;

use App\Domains\Generation\Models\QuizProposal;
use App\Domains\Source\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Str;

/**
 * @extends Factory<QuizProposal>
 */
class QuizProposalFactory extends Factory
{
    /** @var class-string<QuizProposal> */
    protected $model = QuizProposal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $angle = fake()->randomElement([
            'Key dates and the order events happened in',
            'Definitions and the vocabulary of the field',
            'Causes, consequences and the reasoning between them',
            'People, their roles and what they are remembered for',
        ]);

        $outline = $this->outline();
        $topicName = Str::title(rtrim(fake()->sentence(2), '.'));

        return [
            'source_id' => Source::factory(),
            'title' => Str::title(rtrim(fake()->sentence(4), '.')),
            'angle' => $angle,
            'summary' => fake()->paragraph(),
            'suggested_topic_name' => $topicName,
            'suggested_question_count' => count($outline) * (int) config('generation.questions_per_section'),
            'outline' => $outline,
            'language' => 'en',
            'accepted_at' => null,
        ];
    }

    /**
     * Number the proposals of a source consecutively.
     *
     * A whole batch is built before any of it is stored, so the position has to
     * combine the rows already in the table with this model's place in the batch.
     */
    public function configure(): static
    {
        return $this->sequence(function (Sequence $sequence): array {
            $offset = $sequence->index;

            return [
                'position' => fn (array $attributes) => QuizProposal::query()
                    ->where('source_id', $attributes['source_id'])
                    ->count() + $offset,
            ];
        });
    }

    /**
     * A proposal the user has chosen to expand into a quiz.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => now(),
        ]);
    }

    /**
     * An ordered list of sections, each with a title and a stated focus.
     *
     * @return array<int, array{title: string, focus: string}>
     */
    private function outline(): array
    {
        $sections = [
            ['title' => 'Foundations', 'focus' => 'The core terms the rest of the material builds on'],
            ['title' => 'Key figures', 'focus' => 'Who did what, and why it mattered at the time'],
            ['title' => 'Turning points', 'focus' => 'The events that changed the direction of the story'],
            ['title' => 'Consequences', 'focus' => 'What followed, and how it is judged in hindsight'],
            ['title' => 'Open debates', 'focus' => 'Where the sources disagree and what is still contested'],
        ];

        return array_slice($sections, 0, fake()->numberBetween(3, count($sections)));
    }
}
