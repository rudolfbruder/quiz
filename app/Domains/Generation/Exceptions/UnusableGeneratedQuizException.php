<?php

declare(strict_types=1);

namespace App\Domains\Generation\Exceptions;

use RuntimeException;

/**
 * Thrown when the model answered in the declared schema but the answer cannot
 * be turned into a quiz a person could sit.
 *
 * Every message names the specific fault and, where there is one, the question
 * it belongs to. The generation job records the message against the source as
 * its failure reason, so it is read by a person rather than only by a log.
 */
final class UnusableGeneratedQuizException extends RuntimeException
{
    public static function noQuestions(): self
    {
        return new self('The generated question set is empty: it contains no questions at all.');
    }

    public static function tooFewOptions(int $questionNumber, int $optionCount, int $minimum): self
    {
        return new self(sprintf(
            'Question %d carries %d %s, and every question needs at least %d.',
            $questionNumber,
            $optionCount,
            $optionCount === 1 ? 'option' : 'options',
            $minimum,
        ));
    }

    public static function noCorrectOption(int $questionNumber): self
    {
        return new self(sprintf(
            'Question %d has no option marked correct.',
            $questionNumber,
        ));
    }

    public static function severalCorrectOptions(int $questionNumber, int $correctCount): self
    {
        return new self(sprintf(
            'Question %d has %d options marked correct, and exactly one may be.',
            $questionNumber,
            $correctCount,
        ));
    }

    public static function duplicateOption(int $questionNumber, string $text): self
    {
        return new self(sprintf(
            'Question %d repeats the same option text twice: "%s".',
            $questionNumber,
            $text,
        ));
    }

    public static function missingExplanation(int $questionNumber): self
    {
        return new self(sprintf(
            'Question %d carries no explanation.',
            $questionNumber,
        ));
    }
}
