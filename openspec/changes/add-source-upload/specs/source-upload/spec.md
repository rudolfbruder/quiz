## Purpose

Governs what a person may hand the application, what it refuses and how it says so, where an accepted
document is kept, and the states a document moves through from the moment it is accepted until it has
either produced a quiz or failed trying.

## ADDED Requirements

### Requirement: Only a signed-in person may upload, and a document belongs to them alone

Uploading SHALL require an authenticated session. An uploaded document SHALL belong to the person who
uploaded it, and SHALL be visible and deletable only by them.

#### Scenario: An anonymous visitor attempts to upload

- **WHEN** an upload is submitted without an authenticated session
- **THEN** it is refused and nothing is stored

#### Scenario: Someone requests another person's document

- **WHEN** a signed-in person attempts to view or delete a document uploaded by someone else
- **THEN** the request is refused and the document is unchanged

### Requirement: Only supported document types are accepted

The system SHALL accept PDF, plain text and Markdown documents. Any other type SHALL be refused at
upload with a message naming what is accepted. The system MUST NOT accept a document it cannot read
and then fail later.

#### Scenario: A supported document is uploaded

- **WHEN** a person uploads a PDF
- **THEN** it is accepted and stored

#### Scenario: An unsupported document is uploaded

- **WHEN** a person uploads a file that is not one of the accepted types
- **THEN** the upload is refused with a validation message naming the accepted types, and nothing is
  stored

### Requirement: A document above the size limit is refused at upload

The system SHALL refuse a document larger than the configured maximum upload size, before storing it,
with a message stating the limit.

#### Scenario: An oversize document

- **WHEN** a person uploads a document larger than the configured limit
- **THEN** the upload is refused with a message stating the limit, and nothing is stored

### Requirement: An accepted upload responds immediately and records where it was kept

An accepted document SHALL be stored on a filesystem disk and recorded with the disk, the path, the
original filename, the media type and the size in bytes. The response SHALL be returned without
waiting for any analysis or model call. A newly accepted document SHALL have no page count and no
failure reason.

#### Scenario: A document is accepted

- **WHEN** a person uploads a supported document within the size limit
- **THEN** it is stored, recorded as pending with its disk, path, original name, media type and size,
  and the person is returned to the dashboard without waiting

#### Scenario: A freshly accepted document has no analysis yet

- **WHEN** a document has just been accepted
- **THEN** its page count is empty and no failure reason is recorded

### Requirement: A document carries a visible lifecycle

A document SHALL be in exactly one of these states at any time: awaiting analysis, being analysed,
ready, failed, or rejected. The person SHALL be able to see which state their document is in without
taking any action, and the display SHALL keep up as the state changes.

#### Scenario: A person watches a document being processed

- **WHEN** a person uploads a document and remains on the dashboard
- **THEN** the document's state advances from awaiting analysis to being analysed and then to ready or
  a failure, without the person reloading the page by hand

#### Scenario: A failed document explains itself

- **WHEN** a document ends in failed or rejected
- **THEN** a reason is shown that states what went wrong in terms the person can act on

### Requirement: A document is analysed once at a time

A document already being analysed SHALL NOT be analysed again concurrently. A request to process a
document already in progress SHALL be refused rather than starting a second run.

#### Scenario: Processing is requested twice

- **WHEN** a document is already being analysed and processing is requested again for it
- **THEN** the second request is refused and only the original run continues

### Requirement: A person can see their documents and the topics they produced

A person SHALL be able to see, in one place, every document they have uploaded together with its state
and the quiz it produced, and the topics those documents have created. A topic SHALL show how much
material it now holds. Neither listing SHALL include anything belonging to another person.

#### Scenario: A person reviews what they have uploaded

- **WHEN** a person opens the sources panel after uploading two documents
- **THEN** both are listed with their original filename, state, and — where generation succeeded — the
  quiz they produced and how many questions it holds

#### Scenario: A person reviews their topics

- **WHEN** a person opens the sources panel and their documents have produced topics
- **THEN** each topic is listed with the amount of material it holds

#### Scenario: The panel is empty

- **WHEN** a person who has uploaded nothing opens the sources panel
- **THEN** they are told there is nothing yet and how to begin, rather than being shown empty tables

### Requirement: Deleting a document removes it and what it produced

Deleting a document SHALL remove its stored file and every record derived from it. A document being
analysed SHALL NOT be deleted while the run is in progress.

#### Scenario: A person deletes a source

- **WHEN** a person deletes a document that produced a quiz
- **THEN** the stored file, the document record, and the quiz and questions generated from it are all
  removed

#### Scenario: A person deletes a source that failed

- **WHEN** a person deletes a document that failed analysis
- **THEN** the stored file and the record are removed and no orphaned data remains
