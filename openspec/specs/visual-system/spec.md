# visual-system Specification

## Purpose
Defines the product's appearance as a contract rather than a preference: one dark theme, one brand
colour, a measured contrast floor, and the guarantee that nothing is distinguishable by colour alone.

## Requirements

### Requirement: The product renders in a single dark theme

The product SHALL render in one dark theme on every surface and SHALL NOT offer a light variant or any
control that appears to switch themes.

#### Scenario: A person whose system prefers light opens the product

- **WHEN** the operating system requests a light appearance
- **THEN** the product renders in its dark theme unchanged

#### Scenario: A person looks for an appearance setting

- **WHEN** a person opens their settings
- **THEN** no appearance or theme control is presented

### Requirement: Text meets a measured contrast floor

Any text a person must read SHALL render at a contrast ratio of at least 4.5:1 against the surface
behind it, on the page ground and on a raised surface alike.

#### Scenario: Secondary text renders on a card

- **WHEN** helper text, a label or a timestamp renders inside a card
- **THEN** it meets the floor against the card's own ground, not merely against the page

#### Scenario: A new colour is introduced

- **WHEN** a colour carrying text is added to the system
- **THEN** its ratio is measured against every ground it appears on before it is used

### Requirement: One brand colour marks one action

The brand green SHALL indicate the primary action or the active state, and SHALL NOT be used
decoratively. A surface SHALL present at most one primary action.

#### Scenario: A screen offers several actions

- **WHEN** a screen presents a primary action alongside secondary ones
- **THEN** only the primary action carries the brand fill
- **AND** the secondary actions are presented without it

### Requirement: Colour never carries meaning alone

Every state the product expresses through colour SHALL also be expressed by an icon, a word, or both.

#### Scenario: A field fails validation

- **WHEN** a validation error is shown
- **THEN** the error is accompanied by a message in words
- **AND** the message is identifiable as an error without relying on its colour

#### Scenario: An operation succeeds

- **WHEN** a success or status message is shown
- **THEN** it is distinguishable from an error by its wording and its icon, not only by its hue

### Requirement: Focus is always visible

Every interactive element SHALL present a visible focus indicator when focused by keyboard, and that
indicator SHALL NOT be a colour change alone.

#### Scenario: A person tabs through a form

- **WHEN** focus moves to any control
- **THEN** a focus ring is drawn around it
- **AND** the ring is visible against both the page ground and a card ground

### Requirement: Reduced motion is honoured

Anything that animates SHALL render a complete, settled state when the person prefers reduced motion,
and the product SHALL NOT autoplay animation.

#### Scenario: A person prefers reduced motion

- **WHEN** the operating system requests reduced motion
- **THEN** every surface renders complete and still, with no animation and no transition delay
