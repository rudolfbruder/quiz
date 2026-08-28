## Purpose

Defines the durable structure of the study domain — how uploaded material, AI-suggested quiz outlines, generated questions, user attempts and per-user review state relate to one another, and which guarantees the system upholds about that data regardless of how any feature is implemented on top of it.

## ADDED Requirements

### Requirement: Study material is organised into user-owned topics

The system SHALL group study material into topics, and every topic SHALL belong to exactly one user. A user MUST NOT hold two topics with the same identifying slug. Topic names are supplied by the user or derived from analysis of an uploaded source.

#### Scenario: Two users choose the same topic name

- **WHEN** two different users each create a topic named "Roman History"
- **THEN** both topics are stored, each owned by its own user, and neither collides with the other

#### Scenario: One user reuses an existing topic name

- **WHEN** a user attempts to create a second topic whose slug matches one they already own
- **THEN** the system rejects the duplicate rather than creating a second topic with the same slug

### Requirement: An uploaded source records its own processing lifecycle

Every uploaded source SHALL carry a status drawn from exactly this set: `pending`, `processing`, `ready`, `failed`, `rejected`. A source SHALL record the disk and path where its file is stored, its original filename, its media type and its size in bytes. A source's page count SHALL be optional, because it is not known at upload time and is determined later during analysis. When a source ends in `failed` or `rejected`, the system SHALL record a human-readable reason alongside the status.

#### Scenario: A freshly uploaded source

- **WHEN** a source is first persisted
- **THEN** its status is `pending`, its page count is empty, and no failure reason is set

#### Scenario: A source is rejected for exceeding the page limit

- **WHEN** analysis determines the document has more pages than the configured limit
- **THEN** the source's status becomes `rejected` and the recorded reason names the actual page count

#### Scenario: A source whose analysis failed

- **WHEN** analysis cannot complete
- **THEN** the source's status becomes `failed` and a reason is recorded that can be shown to the user

### Requirement: A source carries a set of ordered candidate proposals

An analysed source SHALL hold candidate quiz proposals, each occupying a distinct position within that source. Each proposal SHALL record a title, the angle it takes on the material, a summary, a suggested topic name, an estimated question count, the detected language of the source, and an outline of the sections it covers. A proposal SHALL record whether and when it was accepted.

#### Scenario: Proposals are distinguishable within a source

- **WHEN** several proposals are stored for the same source
- **THEN** each occupies a distinct position, and no two proposals for that source share a position

#### Scenario: A proposal is accepted

- **WHEN** a user accepts one of a source's proposals
- **THEN** that proposal records the moment of acceptance, and proposals that were not chosen remain stored and unaccepted

#### Scenario: A proposal carries the outline that will drive generation

- **WHEN** a proposal is stored
- **THEN** its outline is retrievable as an ordered list of sections, each with a title and a stated focus

### Requirement: An accepted proposal yields exactly one quiz

A quiz SHALL derive from exactly one accepted proposal, and a proposal SHALL NOT yield more than one quiz. Every quiz SHALL belong to both a topic and the source it was generated from, and SHALL carry a lifecycle status drawn from `pending`, `processing`, `ready`, `failed`, together with a failure reason when it ends in `failed`.

#### Scenario: A second quiz for the same proposal is refused

- **WHEN** a quiz already exists for a given proposal and another is attempted for that same proposal
- **THEN** the system refuses the second quiz

#### Scenario: A quiz is reachable from its owner without traversing its source

- **WHEN** the quizzes belonging to a user within a topic are requested
- **THEN** they can be retrieved directly, without first resolving each quiz's source

### Requirement: Questions belong to a quiz and carry ordered options with marked correctness

Every question SHALL belong to exactly one quiz and SHALL carry its own explanation and a stable ordering position. Every question SHALL have options, each carrying its own text, its own ordering position, and an explicit flag marking whether it is correct. Question type SHALL be recorded, with single-choice as the only type this capability defines.

#### Scenario: Question ordering is stable

- **WHEN** the questions of a quiz are retrieved more than once
- **THEN** they are returned in the same order each time

#### Scenario: Correctness is stored per option

- **WHEN** a question's options are retrieved
- **THEN** each option independently states whether it is correct, so a question is not restricted to a single correct option by its structure

### Requirement: An attempt records every question served, answered or not

An attempt SHALL record the user it was served to, the topic it covers, when it started, how many questions it contains, and when it completed. A daily attempt SHALL be uniquely identified by its user, topic and the date it was served for; a practice attempt SHALL reference the specific quiz it was launched from. Each question served within an attempt SHALL be recorded at the moment it is served, before any answer exists, so that a question served but never answered still counts as having been served. The same question SHALL NOT appear twice within one attempt.

#### Scenario: A user abandons a quiz without answering

- **WHEN** an attempt is served and the user leaves without answering any question
- **THEN** every served question is still recorded as served, with no selected option and no correctness verdict

#### Scenario: A question is answered

- **WHEN** a user selects an option for a served question
- **THEN** the existing served record gains the selected option, a correctness verdict, the time taken to answer, and the moment of answering

#### Scenario: A second daily attempt on the same day is refused

- **WHEN** a daily attempt already exists for a user, topic and date, and another is requested for that same combination
- **THEN** the system refuses it and the original attempt is returned unchanged

### Requirement: Per-user review state exists only once a question has been reviewed

The system SHALL hold at most one review state per user and question. A question with no review state for a given user SHALL be understood as never reviewed by that user; the system MUST NOT require a separate marker to express that. A review state SHALL record memory stability, difficulty, when the question was last reviewed, when it is next due, how many times it has been reviewed, and how many times it has been forgotten.

#### Scenario: A question never seen by a user

- **WHEN** a question exists but the user has never reviewed it
- **THEN** no review state exists for that pairing, and the question is treated as new

#### Scenario: The same question reviewed twice

- **WHEN** a user reviews a question they have reviewed before
- **THEN** their single existing review state is updated rather than a second one being created

#### Scenario: Questions due for a user within a topic

- **WHEN** the questions due for a user in a given topic at a given moment are requested
- **THEN** they can be retrieved by that user, topic and due date together, without traversing quizzes or sources

### Requirement: Every graded review is recorded in an append-only log

The system SHALL record one immutable log entry per graded review, capturing the grade given, the memory stability and difficulty both before and after the review, the days elapsed since the previous review, the interval granted, how long the user took to answer, and the moment of review. Log entries SHALL NOT be modified or deleted once written, and the log SHALL remain complete even if the review state derived from it is later recomputed.

#### Scenario: A first review has no prior state

- **WHEN** a user reviews a question for the first time
- **THEN** a log entry is written whose before-values are empty and whose after-values record the resulting stability and difficulty

#### Scenario: Review state is rebuilt from history

- **WHEN** the scheduling model changes and review states must be recomputed
- **THEN** the log alone contains enough information to replay every review in order

### Requirement: Deleting a user removes their study data

Deleting a user SHALL remove the topics, sources, proposals, quizzes, attempts, responses, review states and review logs belonging to that user, leaving no orphaned study records.

#### Scenario: A user account is deleted

- **WHEN** a user with uploaded sources, generated quizzes and review history is deleted
- **THEN** all study data belonging to that user is removed, and no record referencing the deleted user remains
