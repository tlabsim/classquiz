# ClassQuiz — Architecture & Design

## Overview

ClassQuiz is a Laravel-based quiz application for class tests. Teachers/admins create and assign quizzes; students take them via a public link without creating an account. Access is controlled by a per-session email/access-code flow.

---

## Tech Stack

| Layer      | Technology                        |
|------------|-----------------------------------|
| Backend    | Laravel 12 (PHP 8.3+)             |
| Frontend   | Laravel Blade + Tailwind CSS (light mode) |
| Database   | MySQL 8+                          |
| Email      | Laravel Mail (SMTP / Mailgun)     |
| Auth       | Laravel Breeze (admin/teacher only) |

---

## Application Roles

| Role    | Capabilities |
|---------|-------------|
| Admin   | All teacher capabilities + import/export JSON, manage all users/quizzes |
| Teacher | Create/edit own quizzes, assign quizzes, view reports, override grades   |
| Taker   | No account — identified by email + optional name/class ID               |

---

## Database Schema

### `users` — Admin / Teacher accounts
```
id              BIGINT PK
name            VARCHAR(255)
email           VARCHAR(255) UNIQUE
password        VARCHAR(255) HASHED
role            ENUM('admin','teacher')
created_at
updated_at
```

### `quizzes` — Quiz definitions
```
id              BIGINT PK
creator_id      FK → users.id
title           VARCHAR(255)
description     TEXT NULLABLE
created_at
updated_at
```

### `questions` — Questions within a quiz
```
id              BIGINT PK
quiz_id         FK → quizzes.id
type            ENUM('tf','mcq_single','mcq_multi')   -- expand later: 'matching','short_answer'
text            TEXT
points          DECIMAL(6,2) DEFAULT 1
sort_order      INT DEFAULT 0
settings        JSON NOT NULL DEFAULT '{}'  -- see Extensible Settings Pattern; type-specific keys
created_at
updated_at
```

**Default `settings` payload (mcq / tf):**
```json
{
  "randomize_choices": false
}
```
> `randomize_choices` lives in `settings` rather than a dedicated column because it only applies to `mcq_single`, `mcq_multi`, and `tf`. Future types like `matching` and `short_answer` simply omit the key. The `Question` model's `SETTINGS_DEFAULTS` defines which keys are valid per type.

### `choices` — Answer options for a question
```
id              BIGINT PK
question_id     FK → questions.id
text            VARCHAR(1000)
is_correct      BOOLEAN DEFAULT false
sort_order      INT DEFAULT 0
```
> T/F questions use two Choice rows: "True" (is_correct varies) and "False".

### `quiz_assignments` — A quiz made publicly available
```
id              BIGINT PK
quiz_id         FK → quizzes.id
public_token    VARCHAR(64) UNIQUE   -- URL slug, rotatable
is_active       BOOLEAN DEFAULT true
title           VARCHAR(255) NULLABLE  -- overrides quiz.title when set
instructions    TEXT NULLABLE          -- shown to taker before starting
registration_start  DATETIME NULLABLE
registration_end    DATETIME NULLABLE
availability_start  DATETIME NULLABLE
availability_end    DATETIME NULLABLE
duration_minutes    INT NULLABLE     -- NULL = unlimited
settings        JSON NOT NULL DEFAULT '{}'  -- see Extensible Settings Pattern
created_at
updated_at
```

**Default `settings` payload:**
```json
{
  "require_name":         false,
  "require_class_id":     false,
  "randomize_questions":  false,
  "show_result":          true,
  "partial_points":       false
}
```
Adding future settings (e.g., `allow_backtrack`, `show_correct_answers`, `max_attempts`) requires **no migration** — just define the new key and its default in the model. The `QuizAssignment` model exposes a `setting(string $key, mixed $default = null): mixed` helper and casts the column to `array`.

> **`title` vs `quiz.title`:** `title` on the assignment lets teachers reuse one quiz under different names (e.g., "Midterm A" vs "Midterm B") without duplicating it. Resolved display title: `$assignment->title ?? $assignment->quiz->title`.

### `quiz_sessions` — One taker's attempt
```
id              ULID/UUID PK         -- non-sequential, safe in URLs
assignment_id   FK → quiz_assignments.id
email           VARCHAR(255)
name            VARCHAR(255) NULLABLE
class_id        VARCHAR(100) NULLABLE
access_code     VARCHAR(255)         -- bcrypt hash of 6-digit code
access_code_expires_at  DATETIME
resume_token    VARCHAR(255) NULLABLE -- bcrypt hash; generated on first quiz page load
status          ENUM('pending','active','in_progress','submitted','graded')
question_order  JSON NULLABLE        -- stored when quiz starts if randomize_questions=true
started_at      DATETIME NULLABLE
last_activity_at DATETIME NULLABLE   -- updated on every answer save; used for timeout detection
submitted_at    DATETIME NULLABLE
score           DECIMAL(8,2) NULLABLE
max_score       DECIMAL(8,2) NULLABLE
created_at
updated_at
```

> **Resume strategy:** answers are upserted to the `answers` table incrementally (auto-save). On resume, the server reloads all existing `Answer` rows for the session and pre-populates the quiz UI. The timer uses `started_at` to recompute remaining time server-side on every load, so pausing the clock is not needed.

### `answers` — Taker's response per question
```
id              BIGINT PK
session_id      FK → quiz_sessions.id
question_id     FK → questions.id
selected_choice_ids  JSON NULLABLE   -- array of choice IDs for mcq/tf
is_correct      BOOLEAN NULLABLE     -- null until graded
points_awarded  DECIMAL(6,2) NULLABLE
is_manually_graded  BOOLEAN DEFAULT false
graded_by       FK → users.id NULLABLE
graded_at       DATETIME NULLABLE
saved_at        DATETIME NULLABLE    -- timestamp of last auto-save for this answer
```

> Rows are **upserted** (insert or update on `session_id + question_id`) on every auto-save, so the table always reflects the latest draft. `is_correct` and `points_awarded` remain `null` until GradingService runs at final submission.

---

## Directory / Module Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── QuizController.php        -- CRUD for quizzes
│   │   │   ├── QuestionController.php    -- CRUD for questions/choices
│   │   │   ├── AssignmentController.php  -- assign quiz, rotate token
│   │   │   ├── ReportController.php      -- live + post-quiz reports, grade override
│   │   │   └── ImportExportController.php
│   │   └── Public/
│   │       ├── QuizLandingController.php  -- show quiz info, email registration form
│   │       ├── AccessCodeController.php   -- verify code, create session
│   │       └── QuizTakeController.php     -- serve questions, submit answers
│   ├── Middleware/
│   │   ├── EnsureQuizIsAvailable.php
│   │   ├── EnsureSessionOwnership.php  -- checks encrypted cookie or resume token against DB
│   │   └── EnsureSessionNotExpired.php -- enforces duration_minutes using started_at
│   └── Requests/
│       ├── StoreQuizRequest.php
│       ├── StoreQuestionRequest.php
│       ├── RegisterForQuizRequest.php
│       └── SubmitAnswersRequest.php
├── Models/
│   ├── User.php
│   ├── Quiz.php
│   ├── Question.php
│   ├── Choice.php
│   ├── QuizAssignment.php
│   ├── QuizSession.php
│   └── Answer.php
├── Services/
│   ├── GradingService.php          -- auto-grade, partial points logic
│   ├── AccessCodeService.php       -- generate, hash, verify codes; resume token lifecycle
│   ├── QuizExportService.php       -- Quiz → JSON
│   ├── QuizImportService.php       -- JSON → Quiz (with validation)
│   ├── TokenService.php            -- generate/rotate public tokens
│   └── SessionResumeService.php    -- resume token generation, email resume link, restore state
├── Mail/
│   └── AccessCodeMail.php
└── Policies/
    ├── QuizPolicy.php
    └── QuizAssignmentPolicy.php

resources/views/
├── admin/
│   ├── dashboard.blade.php
│   ├── quizzes/  (index, create, edit, show)
│   ├── questions/ (create, edit — modal or inline)
│   ├── assignments/ (index, create, edit)
│   └── reports/ (live, summary, session-detail)
└── public/
    ├── landing.blade.php      -- register with email
    ├── verify.blade.php       -- enter access code
    ├── quiz.blade.php         -- take quiz (SPA-lite with Alpine.js or simple Livewire)
    └── result.blade.php       -- show score + answers
```

---

## URL / Route Structure

### Authenticated (Admin/Teacher) — prefix `/admin`
```
GET  /admin                          → Dashboard
GET  /admin/quizzes                  → Quiz list
GET  /admin/quizzes/create           → Create form
POST /admin/quizzes                  → Store
GET  /admin/quizzes/{quiz}/edit      → Edit form
PUT  /admin/quizzes/{quiz}           → Update
DEL  /admin/quizzes/{quiz}           → Delete

POST /admin/quizzes/{quiz}/questions         → Add question
PUT  /admin/quizzes/{quiz}/questions/{q}     → Update question
DEL  /admin/quizzes/{quiz}/questions/{q}     → Delete question

GET  /admin/quizzes/{quiz}/assign            → Assignment form
POST /admin/quizzes/{quiz}/assign            → Create assignment
PUT  /admin/assignments/{assignment}         → Update settings
POST /admin/assignments/{assignment}/rotate  → Rotate public token
DEL  /admin/assignments/{assignment}         → Deactivate

GET  /admin/assignments/{assignment}/report  → Live / post report
GET  /admin/assignments/{assignment}/report/{session}  → Per-taker detail
POST /admin/assignments/{assignment}/report/{session}/grade → Override grade

POST /admin/quizzes/{quiz}/export    → Download JSON
POST /admin/quizzes/import           → Upload JSON
```

### Public — no auth
```
GET  /q/{token}                          → Landing (register form)
POST /q/{token}                          → Submit email; send access code
GET  /q/{token}/verify                   → Access code form
POST /q/{token}/verify                   → Validate code; set cookie; redirect to take
GET  /q/{token}/take/{session_id}        → Quiz UI (guarded by cookie)
POST /q/{token}/take/{session_id}/save   → Auto-save answer draft (AJAX, returns 200)
POST /q/{token}/take/{session_id}        → Final submit
GET  /q/{token}/result/{session_id}      → Result page
GET  /q/{token}/resume                   → Resume form (enter email)
POST /q/{token}/resume                   → Look up in_progress session; email resume link
GET  /q/{token}/resume/{session_id}      → Verify resume token from link; restore cookie
```

---

## Key Workflows

### 1. Teacher Creates a Quiz
```
Teacher → /admin/quizzes/create
  → fills title, description
  → adds questions (type, text, points, choices)
  → per-question: set points, randomize_choices
  → saves → quiz stored; not yet available publicly
```

### 2. Admin Assigns a Quiz
```
Admin → /admin/quizzes/{quiz}/assign
  → sets registration window, availability window, duration
  → sets require_name, require_class_id, randomize_questions, show_result, partial_points
  → saves → QuizAssignment created, unique public_token generated
  → admin copies the public link: /q/{token}
```

### 3. Taker Takes a Quiz
```
Taker → /q/{token}
  [EnsureQuizIsAvailable: is_active=true, within registration window]
  → enters email (+ name/class_id if required by settings)
  → AccessCodeService.generate() → 6-digit code; bcrypt hash stored in quiz_sessions
  → AccessCodeMail dispatched with plain code
  → redirect to /q/{token}/verify

Taker → /q/{token}/verify
  → enters code
  → AccessCodeService.verify() → bcrypt_check, expiry check
  → session status → 'active'
  → session_id stored in encrypted Laravel cookie (HttpOnly, SameSite=Strict)
  → redirect to /q/{token}/take/{session_id}

Taker → /q/{token}/take/{session_id}
  [EnsureSessionOwnership: cookie session_id matches URL + DB record]
  [EnsureSessionNotExpired: checks started_at + duration_minutes]
  → first load: status → 'in_progress'; started_at set; resume_token generated
    resume_token (plain) included in page JS for localStorage + emailed to taker
  → questions served in stored question_order (or fresh shuffle on first load)
  → taker answers each question → AJAX POST to /save → answer upserted; last_activity_at updated
  → on final submit → GradingService.grade(session) → answers scored
  → session status → 'submitted'
  → if settings.show_result: redirect to /q/{token}/result/{session_id}
```

### 3a. Taker Resumes an Interrupted Quiz
```
Taker → /q/{token}/resume
  → enters email
  → SessionResumeService.lookup(email, assignment)
      → finds quiz_session with status='in_progress' for that email
      → generates a short-lived signed URL: /q/{token}/resume/{session_id}?rt={plain_token}
      → sends ResumeQuizMail to taker's email
      → page shows: "If you have an active session, a resume link was sent to your email."

Taker clicks link → /q/{token}/resume/{session_id}?rt={plain_token}
  → SessionResumeService.verify(session, plain_token)
      → bcrypt_check against resume_token in DB
      → if valid: set encrypted cookie, redirect to /q/{token}/take/{session_id}
      → quiz UI reloads with previously saved answers pre-filled
      → timer resumes from remaining = duration_minutes - elapsed(now - started_at)
```

### 4. Teacher Views Reports
```
Teacher → /admin/assignments/{id}/report
  → live view: count of active sessions, submitted, pending
  → post view: per-taker score table, average, distribution
  → drill into a session → see each answer; override grade button
  → after override → GradingService.recalculate(session)
```

### 5. JSON Import / Export
```
Export:
  Admin clicks Export on a quiz
  QuizExportService builds JSON (see format below)
  Returns JSON download

Import:
  Admin uploads JSON file
  QuizImportService validates schema (version, questions[], choices[])
  Creates new Quiz + Questions + Choices
  Invalid files return user-friendly validation errors
```

---

## JSON Quiz Format

```json
{
  "version": "1.0",
  "title": "Sample Quiz",
  "description": "Optional description",
  "questions": [
    {
      "type": "mcq_single",
      "text": "Which of the following is a prime number?",
      "points": 2,
      "randomize_choices": true,
      "choices": [
        { "text": "4",  "is_correct": false },
        { "text": "7",  "is_correct": true  },
        { "text": "9",  "is_correct": false },
        { "text": "12", "is_correct": false }
      ]
    },
    {
      "type": "mcq_multi",
      "text": "Select all even numbers.",
      "points": 3,
      "randomize_choices": false,
      "choices": [
        { "text": "2",  "is_correct": true  },
        { "text": "3",  "is_correct": false },
        { "text": "4",  "is_correct": true  },
        { "text": "5",  "is_correct": false }
      ]
    },
    {
      "type": "tf",
      "text": "The earth is flat.",
      "points": 1,
      "correct_answer": false
    }
  ]
}
```
> Assignment-level settings (windows, duration, etc.) are **not** stored in the export — they are set each time an admin assigns the quiz.

---

## Grading Logic (`GradingService`)

| Question Type | Rule |
|---------------|------|
| `tf`          | Full points if correct; 0 otherwise |
| `mcq_single`  | Full points if correct choice selected; 0 otherwise |
| `mcq_multi` (partial OFF) | Full points only if all correct choices and no wrong choices selected |
| `mcq_multi` (partial ON)  | `points × (correct_selected - wrong_selected) / total_correct`, min 0 |

---

## Access Code & Resume Token Flow

**Access code**
- 6-digit numeric string (e.g., `482931`)
- Stored as `bcrypt` hash in `quiz_sessions.access_code`
- Expiry: 15 minutes from generation (configurable via `QUIZ_ACCESS_CODE_TTL_MINUTES` in `.env`)
- Re-send: taker can re-register; a new `QuizSession` row is created (old `pending` sessions for the same email + assignment are soft-deleted or replaced)

**Resume token**
- Generated once when the session first transitions to `in_progress`
- Stored as `bcrypt` hash in `quiz_sessions.resume_token`
- Plain token delivered two ways: (1) injected into the page for `localStorage` storage in the browser, (2) emailed via `ResumeQuizMail`
- Resume links expire when the quiz availability window closes or duration runs out
- Resume link is a signed Laravel URL (using `URL::signedRoute`) to prevent forgery

**Session ownership (middleware)**
- Primary: `session_id` in encrypted HttpOnly cookie; `EnsureSessionOwnership` validates cookie matches URL param and DB row
- Fallback (resume): resume token in signed URL query string; once verified, cookie is re-issued

---

## Security Considerations

- All admin routes behind Laravel `auth` middleware + role check (`admin`/`teacher`)
- Public quiz routes rate-limited: registration 5/min per IP; code verify 10/min per IP; auto-save 60/min per session
- Access codes and resume tokens hashed at rest (`bcrypt`); plain values only in email
- `session_id` is a ULID (non-sequential) — safe in URLs, prevents enumeration
- `public_token` is a 32-char `Str::random()` string; rotatable per assignment
- Resume links are Laravel signed URLs (HMAC-verified); expire with quiz availability
- CSRF protection on all non-AJAX forms (Laravel default); auto-save uses CSRF header
- File uploads (JSON import): MIME type check (`application/json`), max 1 MB, schema validation before any DB writes
- `settings` JSON column: read through a typed accessor — never passed raw to queries

---

## Extensible Settings Pattern

Both `quiz_assignments.settings` and `questions.settings` use a `JSON` column to avoid migrations for new configuration keys.

**Model convention (`QuizAssignment`):**
```php
protected $casts = ['settings' => 'array'];

// Defined defaults act as the schema contract
public const SETTINGS_DEFAULTS = [
    'require_name'        => false,
    'require_class_id'    => false,
    'randomize_questions' => false,
    'show_result'         => true,
    'partial_points'      => false,
];

public function setting(string $key, mixed $default = null): mixed
{
    return $this->settings[$key] ?? self::SETTINGS_DEFAULTS[$key] ?? $default;
}

public function displayTitle(): string
{
    return $this->title ?? $this->quiz->title;
}
```

**Model convention (`Question`):**
```php
protected $casts = ['settings' => 'array'];

// Keys present depend on question type; mcq/tf include randomize_choices
public const SETTINGS_DEFAULTS = [
    'randomize_choices' => false,
];

public function setting(string $key, mixed $default = null): mixed
{
    return $this->settings[$key] ?? self::SETTINGS_DEFAULTS[$key] ?? $default;
}
```

**Adding a new setting** (e.g., `allow_backtrack`):
1. Add to `SETTINGS_DEFAULTS` with its default value
2. Add the field to the assignment form Blade view
3. Add validation in `StoreAssignmentRequest`
4. Use `$assignment->setting('allow_backtrack')` in the quiz UI

No database migration required.

---

## Future Expansion Notes

- **Matching questions**: new `type='matching'` with a `matching_pairs` JSON column on `questions`
- **Short answer**: new `type='short_answer'`; auto-grade via exact/fuzzy match; manual override workflow already in place
- **New assignment settings** (e.g., `max_attempts`, `allow_backtrack`, `show_correct_answers`): add to `SETTINGS_DEFAULTS` — no migration needed
- **New per-question settings** (e.g., `time_limit_seconds`, `explanation_text`): add to `Question::SETTINGS_DEFAULTS` — same pattern
- `randomize_choices` is already in `questions.settings`; `matching` and `short_answer` types simply never write or read that key
- **Dark mode**: Tailwind `dark:` classes can be added without structural changes
- **Notifications**: Laravel Notification channels (Slack, SMS) can wrap existing `AccessCodeMail` / `ResumeQuizMail` pattern
- **Multi-language**: Laravel `lang/` files; no hard-coded strings in Blade

---

## Implementation Order (Suggested Milestones)

1. **Auth scaffold** — Laravel Breeze, user roles (`admin`/`teacher`), basic admin layout
2. **Quiz & Question CRUD** — models, migrations (with `settings JSON` columns), admin controllers + Blade forms
3. **Assignment management** — token generation, `SETTINGS_DEFAULTS` pattern, public link
4. **Public taker flow** — landing, email registration, access code mail, quiz UI, auto-save (AJAX), submit
5. **Session resumption** — resume token, `ResumeQuizMail`, `SessionResumeService`, resume routes
6. **Grading** — GradingService, auto-grade on submit, result page
7. **Reports** — live dashboard, post-quiz summary, per-session detail, grade override
8. **Import / Export** — JSON format, validation, import service
9. **Polish** — rate limiting, session expiry enforcement, security hardening, UI refinement
