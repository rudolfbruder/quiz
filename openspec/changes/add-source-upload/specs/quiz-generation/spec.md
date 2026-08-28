## Purpose

Covers turning one stored document into a set of answerable questions: what the model is asked to
produce, what makes its answer acceptable, how a topic and quiz come into existence from it, and what
the person is told while it happens and when it does not work.

## ADDED Requirements

### Requirement: Generation runs in the background, never in front of the person

No model call SHALL occur while a person waits on a request. Generation SHALL be started as background
work after the upload response has been returned.

#### Scenario: A document is uploaded

- **WHEN** a person uploads a document
- **THEN** the response returns immediately and generation begins separately

### Requirement: Questions are generated from the document itself

The system SHALL provide the model with the uploaded document. Every generated question SHALL derive
from that document's content. The system MUST NOT generate questions from a document that failed
upload validation.

#### Scenario: Questions come from the person's own material

- **WHEN** a quiz is generated from an uploaded document
- **THEN** its questions are about that document's content, and the person can see which document
  produced them

### Requirement: Generated output is accepted only in a declared structure

The system SHALL require the model's answer in a declared structure and SHALL NOT interpret free text.
The structure SHALL carry a topic name, a quiz title, the document's page count, and the questions,
each with its explanation and its answer options marked for correctness.

#### Scenario: The model answers in the required structure

- **WHEN** generation completes successfully
- **THEN** the topic name, quiz title, page count, questions, options and explanations are all read
  from the declared structure

### Requirement: Structurally valid output is still checked before it is stored

Before storing anything, the system SHALL verify that the returned question set is usable: at least one
question, every question carrying at least two options, exactly one option marked correct per question,
no two options on a question sharing the same text, and every question carrying an explanation. Output
failing any of these SHALL be rejected as a failure with a reason, and nothing partial SHALL be stored.

#### Scenario: A question comes back with no correct option

- **WHEN** the model returns a question whose options are all marked incorrect
- **THEN** the document is marked failed with a reason, and no question from that run is stored

#### Scenario: A question comes back with two correct options

- **WHEN** the model returns a question with more than one option marked correct
- **THEN** the document is marked failed with a reason, and no question from that run is stored

#### Scenario: An empty question set

- **WHEN** the model returns no questions
- **THEN** the document is marked failed with a reason rather than producing an empty quiz

### Requirement: A document over the page limit is rejected after analysis names its size

The page count is not known when a document is accepted. When analysis reports a page count above the
configured limit, the system SHALL reject the document, SHALL record a reason naming the actual page
count, and SHALL NOT store any question from that run. The system MUST NOT silently use part of an
oversized document.

#### Scenario: A document turns out to be too long

- **WHEN** analysis reports that a document has more pages than the configured limit
- **THEN** the document is rejected, the reason names its real page count and the limit, and no
  questions are stored

#### Scenario: A document within the limit

- **WHEN** analysis reports a page count at or below the limit
- **THEN** the count is recorded against the document and generation proceeds

### Requirement: A successful run produces a topic and a quiz in one step

A successful generation SHALL result in a quiz belonging to the person, attached to the document it
came from and to a topic. Where the person already has a topic matching the one the model named, the
quiz SHALL join it rather than creating a duplicate. The stored result SHALL be all-or-nothing: a run
that cannot be completed leaves no partial quiz behind.

#### Scenario: A person's first document

- **WHEN** generation succeeds for a person with no topics
- **THEN** a topic is created for them, and a quiz with its questions and options is attached to it and
  to the document

#### Scenario: A second document on the same subject

- **WHEN** generation names a topic the person already has
- **THEN** the new quiz joins the existing topic rather than creating a second one with the same
  identity

#### Scenario: Storing fails partway

- **WHEN** an error occurs while storing a generated question set
- **THEN** no part of that quiz remains, and the document is marked failed with a reason

### Requirement: A failed generation is recoverable, and says so

Where generation fails, the system SHALL record a reason the person can read and SHALL leave the
document in a state from which it can be tried again. A failure MUST NOT present as an empty or
unchanged screen.

#### Scenario: The provider is unavailable

- **WHEN** the model cannot be reached during generation
- **THEN** the document is marked failed with a reason, and the person is offered a way to try again

#### Scenario: Generation is retried

- **WHEN** a person retries a document that previously failed
- **THEN** a new run begins and the previous failure reason is cleared

### Requirement: Generation happens once per document, not once per view

A document's questions SHALL be generated once and stored. Displaying a quiz, a question or a
dashboard MUST NOT trigger a model call.

#### Scenario: The dashboard is opened repeatedly

- **WHEN** a person opens the dashboard many times after a quiz is ready
- **THEN** no further model call occurs and the stored questions are shown
