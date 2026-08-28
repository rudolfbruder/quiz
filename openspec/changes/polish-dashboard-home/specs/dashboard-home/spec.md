## Purpose

Governs the surface a signed-in person lands on: what it tells them about the material they have given
the product, what it tells them when they have given it none, and the standard any figure shown here
must meet before it is allowed on the page.

## ADDED Requirements

### Requirement: A person sees only their own material

The landing surface SHALL present only documents, topics, questions and figures belonging to the signed-in
person. It MUST NOT present another person's material, and MUST NOT include another person's material in
any count it displays.

#### Scenario: Two people have uploaded documents

- **WHEN** a signed-in person lands on the home surface
- **THEN** only their own documents are listed
- **AND** every figure shown counts only their own material

#### Scenario: An anonymous visitor requests the landing surface

- **WHEN** the home surface is requested without an authenticated session
- **THEN** it is not rendered and nothing about anyone's material is disclosed

### Requirement: The landing surface shows the person's most recent documents

The home surface SHALL list the person's most recently uploaded documents, most recent first, and SHALL
show for each one its name, the state it has reached, and what it has produced. The list SHALL be
bounded so the surface stays a summary, and SHALL offer a way to reach the full listing.

#### Scenario: A person has uploaded more documents than the surface lists

- **WHEN** a person with many documents lands on the home surface
- **THEN** only the most recent ones are listed, newest first
- **AND** a way to reach the complete listing is offered

#### Scenario: A document has produced a quiz

- **WHEN** a listed document has been analysed successfully
- **THEN** the quiz it produced and how many questions it holds are shown alongside it

#### Scenario: A document has not produced a quiz

- **WHEN** a listed document has not yet been analysed, or its analysis did not succeed
- **THEN** its state is shown and no quiz or question count is claimed for it

### Requirement: Every figure the surface presents is one the system can answer for

A figure SHALL be shown only when it is derived from material the system holds. The surface MUST NOT
present a figure for a capability that has not been built, MUST NOT present a placeholder, sample or
illustrative value, and MUST NOT show a zero that is caused by a missing feature rather than by an
absence of the person's material.

#### Scenario: A capability that would supply a figure does not exist yet

- **WHEN** the product cannot yet answer what a figure would report
- **THEN** that figure is absent from the surface entirely
- **AND** no zero, dash or placeholder stands in for it

#### Scenario: A person has material but a particular count is genuinely zero

- **WHEN** a figure the system can answer for evaluates to zero for this person
- **THEN** zero is shown, because it is the true answer

### Requirement: A person with no material is given the next step

When the signed-in person has uploaded nothing, the home surface SHALL tell them what to do next and
SHALL offer the way to do it. It MUST NOT present an empty list or a row of zeroes as the whole
response.

#### Scenario: A person who has just signed up lands on the home surface

- **WHEN** a person with no documents lands on the home surface
- **THEN** they are told that uploading a document is the next step
- **AND** they are offered the way to reach it

#### Scenario: A person deletes their last document

- **WHEN** the person's final document is removed and they return to the home surface
- **THEN** the same next step is presented

### Requirement: A document still being analysed is identifiable and leads to where it is followed

A document that has not settled SHALL be shown in that state, distinguishable from one that is ready
and from one that failed, by more than colour alone. The surface SHALL offer the way to the listing
where its progress is followed.

#### Scenario: A document is still being analysed

- **WHEN** a person lands on the home surface while one of their documents is being analysed
- **THEN** that document is shown as unsettled, in words as well as in any colour used
- **AND** the way to the listing where its progress is followed is offered

#### Scenario: A document's analysis has failed

- **WHEN** a listed document did not survive analysis
- **THEN** it is shown as failed, distinguishably from one still in progress
