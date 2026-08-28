## Purpose

Decides when a user should next see a given question, by tracking how strongly they remember it and how hard they find it, so that questions are shown just as they are about to be forgotten rather than on a fixed rotation.

## ADDED Requirements

### Requirement: Stability expresses the interval at which recall is still likely

The system SHALL model a user's memory of a question as a stability, measured in days, defined as the interval after which the probability of recalling that question falls to the configured desired retention. At the default desired retention, a question's next due moment SHALL therefore be its last review plus its stability in days.

#### Scenario: Due date follows directly from stability

- **WHEN** a question is reviewed at a given moment and the resulting stability is 12 days, at the default desired retention
- **THEN** the question becomes due 12 days after that moment

#### Scenario: Raising desired retention shortens intervals

- **WHEN** the desired retention is raised above the default while stability is unchanged
- **THEN** the granted interval is shorter than the stability, because the question must be revisited sooner to keep recall probability that high

#### Scenario: Lowering desired retention lengthens intervals

- **WHEN** the desired retention is lowered below the default while stability is unchanged
- **THEN** the granted interval is longer than the stability

### Requirement: Recall probability decays with elapsed time

The system SHALL compute a question's current recall probability from the time elapsed since its last review and its stability, following a curve that decays smoothly from certainty at zero elapsed time and never reaches zero. The curve MUST pass exactly through the desired retention at an elapsed time equal to the stability, so that stability and recall probability stay mutually consistent by construction.

#### Scenario: Recall probability immediately after review

- **WHEN** recall probability is computed with zero days elapsed
- **THEN** it is 1.0

#### Scenario: Recall probability at the due moment

- **WHEN** recall probability is computed at an elapsed time exactly equal to the question's stability
- **THEN** it equals the default desired retention of 0.9

#### Scenario: Recall probability well past the due moment

- **WHEN** recall probability is computed at an elapsed time far greater than the stability
- **THEN** it is below the desired retention but remains greater than zero

### Requirement: A recalled question gains stability, moderated by three factors

When a user answers a question correctly, the system SHALL increase that question's stability. The size of the increase SHALL be moderated so that: a question the user finds more difficult gains less; a question that already has high stability gains proportionally less; and a question recalled when its recall probability had fallen further gains more. A grade of `Easy` SHALL produce a larger increase than `Good`, which SHALL produce a larger increase than `Hard`.

#### Scenario: Repeated successful reviews lengthen intervals progressively

- **WHEN** a question is first graded `Good` and then graded `Good` again at each due moment, with difficulty at its midpoint and default configuration
- **THEN** its stability follows an increasing sequence of approximately 3.0, 6.6, 12.7, 22.5, 37.1 and 57.8 days, each value within 5% of those figures

#### Scenario: A harder question grows more slowly

- **WHEN** two questions with identical stability are both graded `Good` at their due moment, and one has a higher difficulty than the other
- **THEN** the question with the higher difficulty ends with the lower stability

#### Scenario: Reviewing late earns a larger increase

- **WHEN** two questions with identical stability and difficulty are both graded `Good`, one at its due moment and one long after it
- **THEN** the question reviewed later ends with the higher stability

#### Scenario: Grade affects the size of the increase

- **WHEN** three questions in identical state are graded `Hard`, `Good` and `Easy` respectively
- **THEN** the resulting stabilities are ordered from lowest for `Hard` to highest for `Easy`

### Requirement: A forgotten question collapses in stability and records a lapse

When a user answers a question incorrectly, graded `Again`, the system SHALL sharply reduce that question's stability so it returns soon, SHALL never reduce it below the configured minimum, SHALL never increase it, and SHALL increment that question's lapse count.

#### Scenario: A long-stable question is forgotten

- **WHEN** a question with a stability of 22.5 days is graded `Again`
- **THEN** its stability drops to under 2 days, its lapse count increases by one, and it becomes due within that shortened interval

#### Scenario: A newly learned question is forgotten

- **WHEN** a question with a stability already at or near the configured minimum is graded `Again`
- **THEN** its stability is clamped at the configured minimum rather than falling below it

### Requirement: Difficulty responds to grades and drifts back toward the midpoint

The system SHALL hold a difficulty for each user and question, bounded between 1 and 10. A grade below `Good` SHALL raise difficulty and a grade above `Good` SHALL lower it. After every adjustment, difficulty SHALL be drawn slightly back toward the midpoint, so that a run of unusual grades does not pin a question permanently at an extreme.

#### Scenario: Repeated failures raise difficulty without exceeding the bound

- **WHEN** a question is graded `Again` many times in succession
- **THEN** its difficulty rises but never exceeds 10

#### Scenario: Repeated easy grades lower difficulty without breaching the bound

- **WHEN** a question is graded `Easy` many times in succession
- **THEN** its difficulty falls but never drops below 1

#### Scenario: Steady performance holds difficulty stable

- **WHEN** a question at midpoint difficulty is graded `Good`
- **THEN** its difficulty remains at the midpoint

#### Scenario: Mean reversion pulls an extreme difficulty back

- **WHEN** a question sitting at an extreme difficulty is graded `Good`
- **THEN** its difficulty moves measurably toward the midpoint rather than staying put

### Requirement: A first review starts from grade-dependent initial values

A question with no prior review state for a user SHALL be treated as new. Its first review SHALL establish an initial stability and an initial difficulty determined by the grade given, such that a better grade yields both a longer initial stability and a lower initial difficulty.

#### Scenario: First review graded Good

- **WHEN** a question with no prior review state is graded `Good`
- **THEN** it is assigned the configured initial stability and difficulty for that grade, and becomes due that many days later

#### Scenario: Initial values are ordered by grade

- **WHEN** four questions with no prior review state are graded `Again`, `Hard`, `Good` and `Easy` respectively
- **THEN** their initial stabilities increase in that order and their initial difficulties decrease in that order

### Requirement: Intervals are bounded

The system SHALL cap the interval it grants at a configured maximum, regardless of how high stability has grown, and SHALL never grant an interval shorter than the configured minimum stability.

#### Scenario: Stability exceeds the interval cap

- **WHEN** a question's stability grows beyond the configured maximum interval
- **THEN** the granted interval is the configured maximum, while the stored stability retains its true value

### Requirement: Scheduling is deterministic and takes time as an input

Scheduling SHALL be a pure function of the current review state, the grade given, and an explicitly supplied moment. It MUST NOT read the system clock, consult stored data, or introduce randomness. The same inputs SHALL always yield the same stability, difficulty and due moment.

#### Scenario: Identical inputs produce identical outputs

- **WHEN** the same review state, grade and moment are scheduled twice
- **THEN** both calls return exactly the same stability, difficulty and due moment

#### Scenario: Scheduling a review in the past

- **WHEN** a review is scheduled with a supplied moment earlier than the present
- **THEN** the result is computed from the supplied moment, with no reference to the present time

### Requirement: Every scheduling decision is expressible as a review record

Each scheduling decision SHALL yield the values needed to record it: the grade given, the stability and difficulty before the review, the stability and difficulty after it, the days elapsed since the previous review, and the interval granted. For a first review, the before-values SHALL be reported as absent rather than fabricated.

#### Scenario: A first review reports absent prior state

- **WHEN** a question with no prior review state is scheduled
- **THEN** the decision reports no prior stability, no prior difficulty and no elapsed days, while reporting the resulting stability, difficulty and granted interval

#### Scenario: A subsequent review reports the transition

- **WHEN** a question with an existing review state is scheduled
- **THEN** the decision reports both the prior and resulting stability and difficulty, together with the days elapsed and the interval granted
