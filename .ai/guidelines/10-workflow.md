# Workflow

## Spec first: OpenSpec

Any change that adds or alters behaviour is an OpenSpec change before it is code.

1. `openspec-propose` — write the proposal, delta specs, and task list into `openspec/changes/<change-id>/`.
2. `openspec-apply-change` — implement the tasks, ticking them off as they land.
3. `openspec-archive-change` — archive once shipped and specs are synced.

Use `openspec-explore` when the requirement is still fuzzy — think there, not in the implementation.
Use `openspec-update-change` when the plan shifts mid-flight; never let code drift from an unrevised change.

Skip OpenSpec only for: typo fixes, formatting, dependency bumps, and non-behavioural refactors.
When in doubt, propose — a small proposal costs less than a wrong implementation.

**One change, one concern.** Do not fold an unrelated fix into an open change; propose a second one.

## Skills

Activate the matching skill *before* writing code in its domain, not after getting stuck:

| Working on | Skill |
|---|---|
| Any Laravel backend code | `laravel-best-practices` |
| Auth, login, 2FA, passkeys | `fortify-development` |
| Vue pages, forms, navigation | `inertia-vue-development` |
| Route helpers in TS | `wayfinder-development` |
| Utility classes / layout | `tailwindcss-development` |
| Any UI design or polish work | `impeccable` |
| Writing or reviewing tests | `testing-best-practices` |
| `Laravel\Ai\` usage | `ai-sdk-development` |
| Recording new house rules | `infer-conventions` |

## Order of work inside a change

1. Migration + model + factory.
2. Data objects (request and response) with their validation rules.
3. Route + controller + action.
4. Feature test — red, then green.
5. Vue page and components.
6. Quality gates (`.ai/95-quality-gates`).

Backend before frontend. A Vue page written against a prop shape that does not exist yet is rework.

## Recording decisions

When a decision is durable and non-obvious, record it with the Boost `record-rule` tool so it lands in `.ai/rules`
for the whole team. Do not store project rules in personal agent memory. Guidelines in `.ai/guidelines` are the
project-wide baseline; `.ai/rules` holds narrower, path-scoped rules discovered along the way.
