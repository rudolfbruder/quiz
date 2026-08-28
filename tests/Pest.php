<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * A structured generation response of the shape the agent's schema declares.
 *
 * Every generation test starts from a payload that would pass, and spoils only
 * the part it is about, so a test that fails says which rule it broke.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function generationRunPayload(array $overrides = []): array
{
    return array_replace([
        'topic_name' => 'The Roman Republic',
        'title' => 'The fall of the Roman Republic',
        'language' => 'English',
        'page_count' => 12,
        'outline' => [
            ['title' => 'The Gracchi', 'focus' => 'Land reform and the first political violence'],
            ['title' => 'Sulla', 'focus' => 'The dictatorship and the precedent it set'],
        ],
        'questions' => [
            generationRunQuestion('Which office did Tiberius Gracchus hold in 133 BC?', 'Tribune of the plebs'),
            generationRunQuestion('Who marched on Rome in 88 BC?', 'Sulla'),
            generationRunQuestion('What did the Senate call its emergency decree?', 'The senatus consultum ultimum'),
        ],
    ], $overrides);
}

/**
 * One question of that payload, with exactly one option marked correct.
 *
 * @return array<string, mixed>
 */
function generationRunQuestion(string $text, string $answer): array
{
    return [
        'text' => $text,
        'explanation' => 'The document states this directly in the section it is drawn from.',
        'options' => [
            ['text' => $answer, 'is_correct' => true],
            ['text' => $answer.' (a decade later)', 'is_correct' => false],
            ['text' => $answer.' (in name only)', 'is_correct' => false],
            ['text' => 'None of the material supports this', 'is_correct' => false],
        ],
    ];
}
