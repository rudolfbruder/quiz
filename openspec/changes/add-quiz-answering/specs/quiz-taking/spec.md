## Purpose

Covers what happens once a day's questions have been chosen: how they are served and recorded, how an
answer is graded and fed back into the learner's memory model, and what the learner sees at every
state including failure and an empty day.

## ADDED Requirements

### Requirement: Starting a day's quiz records every question as served

When a learner starts their daily quiz, the system SHALL record each selected question as served at
that moment, before any answer exists. A question served but never answered SHALL remain recorded as
served. Starting the quiz a second time on the same day for the same topic SHALL return the existing
attempt rather than creating a second one.

#### Scenario: A learner abandons the quiz immediately

- **WHEN** a learner starts their daily quiz and closes the page without answering anything
- **THEN** every question in that set is recorded as having been served, with no selected option, no
  correctness verdict and no answer time

#### Scenario: A learner returns to an attempt in progress

- **WHEN** a learner who has answered two of today's five questions opens the quiz again
- **THEN** the same attempt is resumed, with the two answers intact and the remaining three unanswered

### Requirement: Answering a question records the choice and grades it

When a learner selects an option, the system SHALL record that option against the question within the
attempt, together with whether it was correct and how long the answer took. An answer SHALL be
accepted only for a question that belongs to the learner's own attempt, and only once — a question
already answered within an attempt MUST NOT be answered again.

#### Scenario: A question is answered

- **WHEN** a learner selects an option for a served question
- **THEN** the existing served record gains that option, a correctness verdict, the time taken, and
  the moment of answering

#### Scenario: An answer is submitted twice

- **WHEN** an answer is submitted for a question the learner has already answered in this attempt
- **THEN** the original answer stands and is not overwritten

#### Scenario: An answer is submitted for someone else's attempt

- **WHEN** an answer is submitted for an attempt that does not belong to the requesting learner
- **THEN** the system refuses it and records nothing

### Requirement: A graded answer updates the learner's memory of that question

Every answered question SHALL produce a grade, and that grade SHALL update the learner's review state
for that question and append an entry to the review log. A question answered for the first time SHALL
gain a review state; one answered again SHALL have its existing state updated rather than duplicated.
The interval granted for a daily quiz SHALL never be shorter than one day, since a quiz served once a
day cannot honour a shorter one.

#### Scenario: A first answer creates review state

- **WHEN** a learner answers a question they have never seen before
- **THEN** a review state is created for that learner and question, and a log entry is written whose
  before-values are empty

#### Scenario: A repeat answer updates the existing state

- **WHEN** a learner answers a question they have answered before
- **THEN** their single existing review state is updated and a further log entry is appended

#### Scenario: A wrong answer brings the question back soon

- **WHEN** a learner answers incorrectly a question they had held for weeks
- **THEN** its stability collapses, its lapse count rises, and it becomes due again within days rather
  than weeks — but no sooner than the following day

#### Scenario: A correct answer pushes the question further out

- **WHEN** a learner answers correctly a question that was due
- **THEN** its stability increases and its next due moment is further away than the last interval

### Requirement: The learner sees why an answer was right or wrong

After answering, the system SHALL show which option was correct and the explanation stored with the
question. The explanation SHALL be shown for a correct answer as well as an incorrect one.

#### Scenario: An incorrect answer

- **WHEN** a learner selects a wrong option
- **THEN** they are shown their choice, the correct option, and the question's explanation

#### Scenario: A correct answer

- **WHEN** a learner selects the correct option
- **THEN** they are told it was correct and are still shown the explanation

### Requirement: A completed attempt reports its result

When every served question in an attempt has been answered, the system SHALL mark the attempt complete
and record how many were answered correctly. The learner SHALL be shown that result together with the
state of their material after the session.

#### Scenario: The last question is answered

- **WHEN** a learner answers the final question of their daily set
- **THEN** the attempt is marked complete, its correct count is recorded, and the learner is shown
  their score for the session

#### Scenario: An incomplete attempt is not scored

- **WHEN** a learner has answered three of five questions
- **THEN** the attempt remains incomplete and carries no final score

### Requirement: The learner can always tell which source a question came from

Every question shown SHALL be traceable to the uploaded source it was generated from, and the learner
SHALL be able to see that attribution from the quiz surfaces.

#### Scenario: A learner questions where something came from

- **WHEN** a learner is answering a question and wants to know its origin
- **THEN** the source document that produced it is identifiable from the surface they are on

### Requirement: Failure states are shown, never swallowed

Where a quiz cannot be served or an answer cannot be recorded, the system SHALL state what happened
and what the learner can do next. The system MUST NOT present a failure as an empty or unchanged
screen.

#### Scenario: An answer cannot be recorded

- **WHEN** submitting an answer fails
- **THEN** the learner is told the answer was not saved and is offered a way to retry, with their
  selection preserved

#### Scenario: A topic has no questions at all

- **WHEN** a learner opens a topic whose quiz has not finished generating
- **THEN** they are told the material is not ready yet and what state it is in, rather than being
  shown an empty quiz
