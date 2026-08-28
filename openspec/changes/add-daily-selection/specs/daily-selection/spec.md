## Purpose

Decides which questions a learner is asked on a given day within a given topic, so that material they
are about to forget comes back before it is lost, material they hold firmly stays out of the way, and
new material is introduced at a rate they can sustain.

## ADDED Requirements

### Requirement: The daily set is drawn from questions the scheduler judges due

The system SHALL select a learner's daily questions for a topic by their scheduled due moment. A
question whose review state is due at or before the moment of selection SHALL be eligible; a question
due later SHALL NOT be served, however long ago it was last seen. The number of questions served SHALL
be the configured daily count.

#### Scenario: A question that is not yet due is not served

- **WHEN** a learner's daily quiz is selected and one of their questions is scheduled to come due in
  four days
- **THEN** that question is not included, even if the topic has fewer questions due than the daily
  count

#### Scenario: A question that has come due is served

- **WHEN** a question's due moment has passed and the learner requests their daily quiz
- **THEN** that question is eligible for inclusion

### Requirement: The most overdue questions lead

When more questions are due than the daily count, the system SHALL serve those whose due moment passed
longest ago, in that order. The system MUST NOT increase the size of the daily set to accommodate a
backlog.

#### Scenario: More questions are due than fit in a day

- **WHEN** forty questions are due and the daily count is five
- **THEN** the five whose due moment passed longest ago are served, and the remaining thirty-five stay
  due for a later day

#### Scenario: A backlog does not grow the daily set

- **WHEN** a learner has not answered for two weeks and a large number of questions are overdue
- **THEN** the daily set is still exactly the configured daily count

### Requirement: Never-seen questions fill the remaining slots, up to a daily cap

A question with no review state for a learner SHALL be treated as new. When the due questions do not
fill the daily set, the system SHALL fill the remaining slots with new questions, up to a configured
maximum number of new questions per day. Where more new questions are available than the cap allows,
the system SHALL introduce them in a stable order so that introduction does not restart or reshuffle
between days.

#### Scenario: A quiet day introduces new material

- **WHEN** one question is due, the daily count is five and the new-question cap is three
- **THEN** the learner is served that one due question and three new ones, for a set of four

#### Scenario: A busy day introduces nothing

- **WHEN** five or more questions are due and the daily count is five
- **THEN** no new questions are introduced that day, and the daily set is made up entirely of due
  questions

#### Scenario: The cap bounds introduction on an empty queue

- **WHEN** nothing is due, the daily count is five and the new-question cap is three
- **THEN** exactly three new questions are served, not five

### Requirement: Selection is stable for a learner, topic and day

The set of questions served to a learner for a topic on a given day SHALL be the same every time it is
requested that day. Reloading, navigating away and returning, or requesting the quiz from another
device MUST NOT change which questions are in the set or the order they appear in.

#### Scenario: The page is reloaded mid-quiz

- **WHEN** a learner answers two of five questions and reloads the page
- **THEN** the same five questions are present in the same order, with the two answers preserved

#### Scenario: Two requests on the same day

- **WHEN** the daily quiz for a learner and topic is requested twice on the same day
- **THEN** both requests yield the identical set, in the identical order

### Requirement: A short or empty day is explained, never silently blank

When fewer questions are available than the daily count, the system SHALL serve what it has rather
than nothing. When no question is due and no new question remains, the system SHALL state why the day
is empty and when the next question falls due. The system MUST NOT present an empty quiz without an
explanation.

#### Scenario: Everything is held and nothing is due

- **WHEN** every question in a topic has been reviewed and the earliest due moment is three days away
- **THEN** the learner is told there is nothing to answer today and when the next question is due,
  rather than being shown an empty quiz

#### Scenario: Fewer questions remain than the daily count

- **WHEN** two questions are due, no new questions remain, and the daily count is five
- **THEN** those two are served and the learner is told the set is short because the rest are not yet
  due

### Requirement: A backlog is never presented as a debt

The system SHALL report how many questions are waiting for the learner today. It MUST NOT present a
running count of overdue questions as a headline figure, a badge, or a measure the learner is invited
to reduce.

#### Scenario: A learner returns after a long absence

- **WHEN** a learner who has been away for a fortnight opens their topic and forty questions are
  overdue
- **THEN** they are told how many questions today holds, and are not shown an overdue total

### Requirement: Selection is scoped to one topic and one learner

Selection SHALL consider only questions belonging to the requested topic, and only the requesting
learner's own review history. A question's standing for one learner MUST NOT affect its standing for
another.

#### Scenario: Two learners share a topic

- **WHEN** two learners study the same topic and one has answered a question many times while the
  other has never seen it
- **THEN** each learner's daily set reflects only their own history with that question
