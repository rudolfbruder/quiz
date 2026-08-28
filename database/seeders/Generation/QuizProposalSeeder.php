<?php

declare(strict_types=1);

namespace Database\Seeders\Generation;

use App\Domains\Generation\Models\QuizProposal;
use App\Domains\Source\Models\Source;
use Database\Seeders\Source\SourceSeeder;
use Database\Seeders\Topic\TopicSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * The candidate outlines analysis produced for each demo document.
 *
 * Every source receives the configured number of proposals and exactly one of them is
 * accepted, which is the invariant the schema cannot express with a portable index.
 */
class QuizProposalSeeder extends Seeder
{
    /**
     * The proposals offered per document, in the order they were suggested.
     *
     * `accepted_position` names the one the demo user chose; it is clamped to the
     * configured proposal count so that lowering the count cannot leave a source with
     * no accepted proposal at all.
     *
     * @var array<string, array{accepted_position: int, proposals: array<int, array{title: string, angle: string, summary: string, outline: array<int, array{title: string, focus: string}>}>}>
     */
    private const PROPOSALS = [
        'the-roman-constitution' => [
            'accepted_position' => 0,
            'proposals' => [
                [
                    'title' => 'Magistrates, Assemblies and the Checks Between Them',
                    'angle' => 'Institutions and the rules that bound them',
                    'summary' => 'Works through the offices of the Republic in the order a career passed through them, then sets each one against the council or assembly that could restrain it. Emphasises collegiality, annuality and the veto as the three devices that kept any single magistrate from acting alone.',
                    'outline' => [
                        ['title' => 'From kings to consuls', 'focus' => 'What the expulsion of the monarchy replaced and what it kept'],
                        ['title' => 'The cursus honorum', 'focus' => 'The ordered ladder of offices and the ages and intervals attached to it'],
                        ['title' => 'The Senate', 'focus' => 'Its composition, its advisory standing and the authority it exercised in practice'],
                        ['title' => 'The assemblies', 'focus' => 'Which body elected whom, passed which laws and voted in which units'],
                        ['title' => 'Checks and emergency powers', 'focus' => 'The veto, provocatio and the dictatorship as a bounded exception'],
                    ],
                ],
                [
                    'title' => 'The Republic in Its Own Words',
                    'angle' => 'Definitions and the vocabulary of Roman public life',
                    'summary' => 'A vocabulary-first pass over the same material: imperium, potestas, auctoritas, provocatio, intercessio and the rest of the terms a reader has to hold steady before the institutions make sense.',
                    'outline' => [
                        ['title' => 'Power and its names', 'focus' => 'The distinctions between imperium, potestas and auctoritas'],
                        ['title' => 'Orders and status', 'focus' => 'Patrician, plebeian, equestrian and the legal weight each carried'],
                        ['title' => 'Procedure', 'focus' => 'The technical terms for calling, vetoing and carrying a vote'],
                    ],
                ],
                [
                    'title' => 'The Conflict of the Orders',
                    'angle' => 'Causes, consequences and the reasoning between them',
                    'summary' => 'Follows the two-century argument between patricians and plebeians as a chain of concessions, treating each new office and each new law as an answer to a specific grievance rather than as an isolated reform.',
                    'outline' => [
                        ['title' => 'The first secession', 'focus' => 'The grievance that produced the tribunate'],
                        ['title' => 'Written law', 'focus' => 'Why the Twelve Tables mattered more as publication than as content'],
                        ['title' => 'Opening the offices', 'focus' => 'The laws that admitted plebeians to the consulship and the priesthoods'],
                        ['title' => 'The settlement of 287', 'focus' => 'What the Lex Hortensia settled and what it left unresolved'],
                    ],
                ],
            ],
        ],
        'the-punic-wars' => [
            'accepted_position' => 1,
            'proposals' => [
                [
                    'title' => 'Rome and Carthage: Three Wars',
                    'angle' => 'Key dates and the order events happened in',
                    'summary' => 'A chronological run through the three wars with Carthage, from the dispute over Messana to the destruction of the city, keeping the campaigns, the commanders and the treaties in sequence.',
                    'outline' => [
                        ['title' => 'The first war', 'focus' => 'Sicily, the fleet and the terms of 241'],
                        ['title' => 'The second war', 'focus' => 'Hannibal in Italy and the decision in Africa'],
                        ['title' => 'The third war', 'focus' => 'The siege and the settlement of 146'],
                    ],
                ],
                [
                    'title' => 'War, Reform and Collapse',
                    'angle' => 'Causes, consequences and the reasoning between them',
                    'summary' => 'Traces the line from the victory over Carthage to the end of the Republic: the land problem the wars created, the reform attempts that answered it, the armies that became loyal to their commanders, and the civil wars that followed.',
                    'outline' => [
                        ['title' => 'The wars with Carthage', 'focus' => 'The three conflicts and the empire they left Rome holding'],
                        ['title' => 'The land problem', 'focus' => 'How prolonged service and large estates hollowed out the smallholder class'],
                        ['title' => 'The Gracchi', 'focus' => 'What the two tribunes proposed and why both attempts ended in killing'],
                        ['title' => 'Marius and Sulla', 'focus' => 'Army recruitment, personal loyalty and the first march on Rome'],
                        ['title' => 'The end of the Republic', 'focus' => 'The alliances, the civil wars and the settlement that replaced the old constitution'],
                    ],
                ],
                [
                    'title' => 'The Men Who Ended the Republic',
                    'angle' => 'People, their roles and what they are remembered for',
                    'summary' => 'A biographical route through the same century, taking each major figure in turn and asking what office they held, what precedent they broke and what the breaking of it cost.',
                    'outline' => [
                        ['title' => 'The reformers', 'focus' => 'Tiberius and Gaius Gracchus'],
                        ['title' => 'The generals', 'focus' => 'Marius, Sulla, Pompey and Crassus'],
                        ['title' => 'The last contest', 'focus' => 'Caesar, Antony and Octavian'],
                    ],
                ],
            ],
        ],
    ];

    /**
     * Seed the proposals for both demo documents.
     */
    public function run(): void
    {
        self::demoProposals();
    }

    /**
     * The accepted proposal of each demo document, created on first call.
     *
     * @return Collection<int, QuizProposal>
     */
    public static function demoProposals(): Collection
    {
        $accepted = new Collection;

        foreach (SourceSeeder::demoSources() as $source) {
            $accepted->push(self::seedSource($source));
        }

        return $accepted;
    }

    /**
     * Give one source its full set of proposals and return the accepted one.
     */
    private static function seedSource(Source $source): QuizProposal
    {
        $key = SourceSeeder::keyFor($source);
        $fixture = self::PROPOSALS[$key] ?? throw new RuntimeException("No proposal fixture is defined for source \"{$key}\".");

        $count = (int) config('generation.proposal_count');
        $proposals = array_slice($fixture['proposals'], 0, $count);
        $acceptedPosition = min($fixture['accepted_position'], count($proposals) - 1);

        $accepted = null;

        foreach ($proposals as $position => $proposal) {
            $stored = QuizProposal::query()->firstOrCreate(
                [
                    'source_id' => $source->getKey(),
                    'position' => $position,
                ],
                [
                    'title' => $proposal['title'],
                    'angle' => $proposal['angle'],
                    'summary' => $proposal['summary'],
                    'suggested_topic_name' => TopicSeeder::DEMO_NAME,
                    'suggested_question_count' => count($proposal['outline']) * (int) config('generation.questions_per_section'),
                    'outline' => $proposal['outline'],
                    'language' => 'en',
                    'accepted_at' => $position === $acceptedPosition ? now()->subDays(29) : null,
                ],
            );

            if ($position === $acceptedPosition) {
                $accepted = $stored;
            }
        }

        return $accepted ?? throw new RuntimeException("No proposal was accepted for source {$source->getKey()}.");
    }
}
