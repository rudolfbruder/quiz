## Purpose

Governs the chrome every signed-in page is drawn inside: how the product names and marks itself, what
the persistent navigation offers, and the rule that the shell keeps a person inside the product rather
than handing them off to somewhere else.

## ADDED Requirements

### Requirement: The product presents its own identity, not its framework's

Every signed-in surface SHALL present the product's own name and its own mark. The shell MUST NOT
present the name, wordmark or logo of a framework, starter kit or other tool the product was built
with.

#### Scenario: A person looks at the persistent navigation

- **WHEN** a signed-in person views any page
- **THEN** the shell shows the product's name and its own mark

#### Scenario: A person reads the browser tab or window title

- **WHEN** any page of the product is open
- **THEN** the title identifies the page and the product, and names no framework or starter kit

#### Scenario: A person bookmarks or shares a page

- **WHEN** a page is saved or its title is copied elsewhere
- **THEN** what is carried away identifies this product

### Requirement: The shell does not send a person out of the product

The persistent navigation SHALL offer only destinations inside the product. It MUST NOT present links
to source repositories, framework documentation, or any other external destination.

#### Scenario: A person scans the navigation for somewhere to go

- **WHEN** a signed-in person reads every entry the shell offers
- **THEN** every one of them leads to a surface of this product

#### Scenario: A person follows a navigation entry

- **WHEN** any entry in the shell is activated
- **THEN** the person remains inside the product and no new browsing context is opened

### Requirement: Every destination the shell offers is one the product serves

Each navigation entry SHALL lead to a surface the signed-in person can actually reach. The shell MUST
NOT advertise a destination that does not exist, refuses them, or is not yet built.

#### Scenario: A signed-in person follows each navigation entry in turn

- **WHEN** every entry the shell presents is followed
- **THEN** each one renders a surface belonging to that person

#### Scenario: A capability has not been built yet

- **WHEN** a part of the product has no surface a person can reach
- **THEN** the shell offers no entry for it
