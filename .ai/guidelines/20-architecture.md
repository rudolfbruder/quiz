# Architecture

## Domain-driven layout — this is the rule that outranks Laravel defaults

Application code lives under `app/Domains/<Domain>/`. Each domain owns everything it needs: models, services,
repositories, jobs, notifications, agents, data objects, enums, events, policies. A domain is a vertical slice, not
a layer. Code inside a domain folder is grouped by *what it belongs to*, then by *what it is*.

Two deliberate exceptions:

- **`app/Models/User.php` stays where it is.** Fortify, the auth guards, and framework conventions are bound to it.
- **Controllers stay in `app/Http/Controllers/<Domain>/`.** Standard Laravel HTTP layer, grouped by domain folder.

```
app/
  Domains/
    Source/                         uploads: storing, validating, extracting documents
      Models/            Source.php
      Data/              StoreSourceData.php  SourceData.php
      Actions/           StoreUploadedSource.php  DeleteSource.php
      Services/          SourceTextExtractor.php  PageCounter.php
      Repositories/      (only if it earns its place — see below)
      Jobs/              AnalyzeSource.php
      Notifications/
      Enums/             SourceStatus.php
      Policies/          SourcePolicy.php
      Events/            SourceUploaded.php
    Quiz/                           proposals, questions, generation
      Models/            Quiz.php  QuizProposal.php  Question.php  Option.php
      Data/              QuizData.php  GeneratedQuestionData.php  AcceptProposalData.php
      Actions/           AcceptQuizProposal.php  PersistGeneratedQuestions.php
      Agents/            ProposeQuizOutlines.php  GenerateQuizQuestions.php
      Jobs/              GenerateQuiz.php
      Enums/             QuestionType.php
      Policies/          QuizPolicy.php
    Attempt/                        serving daily quizzes, scoring, reminders
      Models/            Attempt.php  Response.php
      Data/              AttemptData.php  SubmitResponseData.php
      Actions/           SelectDailyQuestions.php  StartAttempt.php  ScoreAttempt.php
      Notifications/     DailyQuizAvailable.php
      Jobs/
    Topic/
      Models/            Topic.php
      Data/
      Actions/
  Models/
    User.php                        stays here — framework/Fortify bound
  Http/
    Controllers/<Domain>/           thin HTTP layer, grouped by domain
    Middleware/
  Actions/Fortify/                  Fortify-owned, do not repurpose
  Concerns/                         cross-domain traits only
  Providers/
database/
  migrations/                       all migrations, flat, standard Laravel
  factories/<Domain>/               namespaced Database\Factories\<Domain>
  seeders/
resources/js/
  pages/<domain>/                   Inertia pages mirror the domain names
  components/  layouts/  types/
routes/web.php, routes/settings.php
tests/Feature/<Domain>/  tests/Unit/<Domain>/
```

Namespaces follow the path: `App\Domains\Quiz\Models\Question`, `App\Domains\Attempt\Actions\ScoreAttempt`.
No composer change is needed — `App\` already maps to `app/`.

### Two traps this layout creates — handle them explicitly

Laravel's auto-discovery guesses `App\Models\X` and `App\Policies\XPolicy`. Domain models break both guesses, so
wire them with attributes instead of hoping:

```php
namespace App\Domains\Quiz\Models;

use App\Domains\Quiz\Policies\QuizPolicy;
use Database\Factories\Quiz\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(QuizFactory::class)]
#[UsePolicy(QuizPolicy::class)]
class Quiz extends Model
{
    //
}
```

Never rely on convention-based factory or policy resolution for a domain model. Missing `#[UseFactory]` surfaces as
a confusing "class not found `Database\Factories\Domains\Quiz\Models\QuizFactory`" at test time.

### Domain boundaries

- A domain may depend on another domain's **models, Data objects, and actions** — through their public API, not by
  reaching into its internals.
- Dependencies point one way. `Attempt` reads `Quiz`; `Quiz` never reads `Attempt`. If you need the reverse, raise
  a domain event instead of adding the back-reference.
- Cross-domain orchestration belongs in the controller or in an action of the domain that owns the outcome — never
  in a model.
- Shared code that genuinely belongs to no single domain goes in `app/Concerns/`. If two domains keep needing the
  same thing, that is a signal the boundary is wrong; fix the boundary rather than growing a shared bucket.
- No `app/Services/`, `app/Repositories/`, `app/Jobs/`, or `app/Notifications/` at the top level. Those live inside
  a domain.

## What each building block is for

- **Controller** — accepts a request `Data` object, calls one action or one job dispatch, returns an Inertia
  response or a redirect. Thin: if it needs a local variable to hold intermediate state, that logic belongs in an
  action. No business rules, no queries beyond a `find`.
- **Action** — one use case, one public entry point (`__invoke` or a single verb method), constructor-injected
  dependencies. The default place for domain behaviour. Receives Data objects or models, returns models or Data
  objects. Never touches `Request`, `session()`, or `redirect()`.
- **Service** — a cohesive collaborator wrapping one external or technical concern with several related methods
  (`SourceTextExtractor`, `PageCounter`). Use a service when the thing has state or a real API surface; use an
  action when it is one use case.
- **Repository** — **only if needed.** Justify it: the same non-trivial query is needed by two or more callers, or
  the data comes from somewhere that is not Eloquent. Otherwise query Eloquent directly from the action. A
  repository that only forwards `find`/`create`/`save` to a model is noise and should be deleted.
- **Job** — queued work. Thin: it resolves an action and calls it. Retries, backoff, timeouts, and status
  transitions live here; the domain logic does not.
- **Notification** — one class per message, queued, copy behind `__()`.
- **Model** — persistence, relations, casts, scopes. No HTTP awareness, no view formatting, no AI calls.
- **Data object** — the only shape that crosses the HTTP boundary, in either direction.
- **Agent** — the only way a model is prompted. See `.ai/35-ai-generation`.

## Request flow

```
Route  →  Controller  →  Action / Job dispatch  →  Service / Model
   ↑           ↓                    ↓
Wayfinder   Data object        Agent (queued only)
                ↓
          Inertia page (Vue)
```

## Rules

- **No fat controllers, no anaemic actions.** Business rules live in actions, services, and models.
- **No logic in Blade.** `resources/views/app.blade.php` is the only Blade file and stays a shell.
- **Single source of truth for validation:** the request Data object. Never re-validate in the controller or action.
- **Single source of truth for shape:** the response Data object. New field for the frontend? Add it there — do not
  sneak an extra key into `Inertia::render()`.
- Shared props (`auth.user`, `sidebarOpen`, flash) belong in `HandleInertiaRequests::share()` and nowhere else.
- **No model call in a request cycle.** Controllers dispatch jobs; jobs call agents. See `.ai/35-ai-generation`.
- Reach for a new abstraction on the second real caller. One caller = inline it.
