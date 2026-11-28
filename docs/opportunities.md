# Atlas Fit — opportunity review

An investigation of where Atlas Fit stands today, what would make it succeed or fail, and a prioritised
list of work to pick from (issue #59). Each opportunity has an ID (for example **T1**) so it can be
turned into its own issue and pull request.

Effort: **S** = under a day, **M** = a few days, **L** = a week or more.
Value: ★ to ★★★ (how much it moves the project towards its aim or away from a failure).

---

## 1. What Atlas Fit is for

Atlas Fit is a personal strength-training and nutrition companion. A user follows a shared
programme (a weekly split of muscle-group sessions with a three-week rotation of sets × reps at a
percentage of their one-rep max), logs the sets they lift, logs the food they eat against macro
targets worked out from their body weight and goal (bulking, maintaining or cutting), and tracks
their body weight towards a goal. A small API lets other tools (such as Life OS) read today's
workout and macros.

**The promise to the user:** "Open the app, know exactly what to do today (lift and eat), record it
in seconds, and see that you are getting stronger and closer to your goal weight."

### Success criteria

| # | Criterion | How we would know |
|---|-----------|-------------------|
| S1 | The user knows what to lift today, and how heavy | Dashboard shows today's exercises *with target weights* |
| S2 | Logging a session or a meal takes seconds, on a phone, at the gym | Few taps per set; works one-handed on a small screen |
| S3 | The user can see progress | Strength and weight trends visible; PBs and achievements |
| S4 | Targets stay right as the user changes | Macros and loads follow the user's latest weight and lifts |
| S5 | The user keeps coming back | Weekly active users, streaks, sessions logged per week |
| S6 | New users reach a useful state quickly | Sign-up to first logged workout and meal in one sitting |
| S7 | Users trust the product with their data | Honest marketing, data export/delete, secure API |

### What would make it fail

| # | Failure risk | Where it shows today |
|---|--------------|----------------------|
| F1 | **Targets without numbers.** The programme says "5 × 5 at 80% of 1RM" but the app never knows the user's 1RM, so it cannot say "lift 185 lbs". The core loop stops at "work it out yourself". | `Workout.weight_1rm` and `Rotation.weight_percent` are shown as percentages only (dashboard, schedule) |
| F2 | **Logging friction.** Logging means choosing an exercise from the full list, then sets/reps/weight, one exercise at a time, with no link to today's plan and no "same as last time". | `pages/⚡workouts.blade.php` |
| F3 | **Stale, one-size targets.** Macros come from the profile weight, which is never updated by weigh-ins, and the formula ignores sex, height, age and activity. | `MacroCalculator`, `Settings/Profile.php`; `BodyWeight` never touches `users.body_weight_lbs` |
| F4 | **No visible strength progress.** Only the last 10 sets are listed; PBs are the max weight ever lifted, regardless of reps. | Workouts page, dashboard `personalBests()` |
| F5 | **Trust damage from the landing page.** It claims 50,000 members, "4.9/5 from 2,400+ reviews", paid plans, plateau detection, micronutrients and wearables. None of these exist. This is a credibility risk and, for a UK/EU audience, a consumer-protection risk. | `resources/views/welcome.blade.php` |
| F6 | **Shared, unmoderated food catalogue.** Any user's "add food" appears for every user, with no owner, no edit and no delete; mistakes and junk spread to everyone. | `nutrition` `addMealItem()`; `meal_items` has no `user_id` |
| F7 | **Days that are wrong for the user.** The app runs on UTC, so in British Summer Time food logged after midnight lands on the previous day, and "today's workout" flips an hour late. The rotation week comes from the ISO week number `% 3`, which jumps at year end (week 52/53 → week 1) and is the same for everyone regardless of when they started. | `config/app.php` timezone, `whereDate(..., Carbon::today())`, `format('W') % 3` in dashboard, schedule and API |
| F8 | **Imperial-only.** Weights are lbs only; the target audience is UK/Europe, where most gyms and scales use kg. | All forms and displays |
| F9 | **API secrets in URLs.** The token is part of the URL path, stored in plain text and compared in SQL; responses include the user's email. | `routes/api.php`, `Api/Macros.php`, `Api/UpcomingWorkouts.php` |
| F10 | **No reason to return** once the novelty wears off: no reminders, streaks or weekly summary. | — |

---

## 2. Personas

The core audience is self-coached lifters following a structured programme. Slightly wider: people
whose main goal is weight change, and people who also run or do cardio.

| Persona | Goal | What success looks like | Where they fall off today |
|---------|------|-------------------------|---------------------------|
| **Sam, the returning lifter** (30s, knows the big lifts, trains 4×/week) | Get back to old numbers | Opens the app between sets, sees today's weight for each lift, taps "done" | Has to calculate % of 1RM by hand (F1); logs each set by searching a long list (F2); can't see a squat trend (F4) |
| **Priya, the beginner** (20s, first gym membership) | Learn a routine, build confidence | Clear plan, form cues, small wins | Doesn't know her 1RM at all (F1); "(Ben.) Squat" naming is jargon; no exercise guidance; achievements start at 225 lb bench, far out of reach (F4) |
| **Dan, the cutter** (40s, wants to lose 15 kg) | Lose weight without losing strength | Calorie target that adjusts as he loses weight; weekly trend | Macro target never moves as he weighs in (F3); thinks in kg and stone (F8); logging late dinners after midnight in summer goes to the wrong day (F7) |
| **Alex, the hybrid athlete** (runs + lifts) | Balance running and lifting | One place for both; fuel for training days | Runs are invisible (Strava idea in `docs/todo.md`); macros are the same on rest and run days |
| **Morgan, the owner/coach** (admin, maybe trains a few friends) | Run the programme, see who is engaged | Edit the programme safely; see usage | Admin page edits the programme for everyone (fine) but there is no way to give someone a different programme; usage stats exist only through the daily stats API |
| **A sceptical visitor** (found the landing page) | Decide whether to sign up | Honest pitch, quick start | Inflated claims (F5) and paid tiers that don't exist; after sign-up, empty dashboard until profile is filled (S6) |

---

## 3. What is already working

Worth keeping and building on:

- A clean, consistent Livewire + Flux UI with a reusable component kit (`x-ui.stat`, `x-ui.ring`, `x-ui.empty-state`, …), dark mode and celebration feedback.
- Per-user schedule customisation (choose each day's session, switch exercises off) via `UserSchedule`, shared by dashboard, schedule and API.
- Weight tracking with goals, projections and multi-range charts; protection against accidental weigh-ins.
- Nutrition traffic-light catalogue showing whether a food fits today's remaining macros.
- Two-factor auth, password rules in production, sign-up notifications and a daily stats endpoint.
- A healthy test suite: 141 Pest tests passing.

---

## 4. Opportunities

### A. Make the training loop complete (core value: S1, S2, S3, S4)

**T1 — Personal training maxes and target weights** · Value ★★★ · Effort M
Store each user's 1RM (or training max) per benchmark/exercise and show the actual weight to lift
everywhere a percentage is shown today: "Bench press — 5 × 5 @ 80% → **185 lbs**", rounded to
the nearest plate increment. Let users enter a known 1RM, or estimate it from a recent set
(Epley/Brzycki: weight × reps), and update it automatically when a logged set implies a higher
estimate. Fixes F1 for Sam and Priya; unlocks T2, T4 and P1.

**T2 — Log today's session from the plan** · Value ★★★ · Effort M
From the dashboard, "Start today's session" opens a checklist of today's exercises pre-filled with
the rotation's sets × reps and the T1 target weight. Each exercise is one tap to mark done, or
adjust then done. Adds "repeat last time" for free-form logging. Fixes F2; the biggest driver of
daily use.

**T3 — Per-set logging, notes and RPE** · Value ★★ · Effort M
Allow logging individual sets (e.g. 5, 5, 4 reps) rather than one sets × reps × weight line,
with an optional note and effort rating (RPE). Gives accurate history and better 1RM estimates.
Needs a migration (a `completed_sets` table or JSON column) and care to keep existing records.

**T4 — Progressive overload suggestions** · Value ★★ · Effort S–M (after T1)
After a session where all reps were hit, suggest the next increment (+5 lb lower body, +2.5 lb
upper); after two misses, suggest a deload. The landing page already promises this; this makes
it true.

**T5 — Rest timer** · Value ★ · Effort S
An Alpine-only countdown after marking a set done (90s/2m/3m presets, vibration on phones).
Small, delightful, keeps the app open during the session.

**T6 — Friendlier exercise library** · Value ★★ · Effort M
Rename or display "(Ben.) Squat" as "Squat · benchmark lift"; add short how-to text or a link
and the muscle group to each exercise. Lets users who switch exercises off (#51) choose an
alternative. Helps Priya most.

**T7 — Start-of-programme anchor for the rotation** · Value ★★ · Effort S
Replace `ISO week % 3` with "weeks since the user's programme start date" (default: sign-up
week), stored on the user and resettable. Fixes the year-end jump (F7) and lets a new user start
on week 1. Also move the rotation calculation into one place (it is duplicated in the dashboard,
schedule and API).

### B. Show progress (S3, S5)

**P1 — Progress page with strength charts** · Value ★★★ · Effort M (already in `docs/todo.md`)
A "Progress" page with a Flux chart per benchmark lift (estimated 1RM over time, from T1's
formula), weekly training volume, and sessions per week. Reuse the range buttons and chart
helpers from the weight page.

**P2 — Full workout history** · Value ★★ · Effort S
Paginated history with filters by exercise and date, beyond the last 10; edit as well as delete
an entry (weigh-ins already got edit in #48/#49).

**P3 — Achievements gallery with progress** · Value ★★ · Effort S–M (in `docs/todo.md`)
Show every achievement with its image and "your best: 185 / 225 lb (82%)". Add beginner-friendly
tiers (bodyweight bench, 1 plate squat), consistency achievements (first workout, 10 sessions,
4-week streak) and weight-goal milestones, so Priya and Dan earn something in week one.

**P4 — PBs that respect reps** · Value ★ · Effort S
Show "best estimated 1RM" and best at common rep counts (1/3/5), rather than heaviest weight
regardless of reps, and celebrate a new PB at the moment it is logged.

### C. Nutrition that stays right (S4, F3, F6)

**N1 — Weigh-ins update the macro targets** · Value ★★★ · Effort S
Use the latest weigh-in (or a 7-day average) as the body weight for macros, falling back to the
profile value. Today, a user who loses 10 lb keeps eating to their starting weight. Smallest
change with the biggest correctness payoff for Dan.

**N2 — Better target formula** · Value ★★ · Effort M
Optionally collect sex, height, age and activity level and use Mifflin-St Jeor TDEE with a
goal adjustment (e.g. −20% cut, +10% bulk) and protein per kg. Keep the current simple formula
as the default for users who skip the extra fields. Show where the numbers come from.

**N3 — Personal vs shared foods** · Value ★★ · Effort M
Add `user_id` to `meal_items`: foods a user adds are theirs (editable, deletable); the seeded
items stay shared; an admin can promote a personal item to shared. Fixes F6.

**N4 — Faster food logging** · Value ★★ · Effort M
Recent and favourite foods at the top, search, grams-based portions (not just 1–10 servings),
saved meals ("my usual breakfast"), and logging for yesterday. Optional later: barcode lookup via
Open Food Facts.

**N5 — "What should I eat next?"** · Value ★★ · Effort S (in `docs/todo.md`)
The traffic-light scoring already exists in `catalogData()`; surface the top 3 best-fitting foods
on the dashboard and nutrition page.

**N6 — Training-day vs rest-day targets** · Value ★ · Effort S
Slightly higher carbs on training days, lower on rest days, using `UserSchedule`.

### D. First run and retention (S5, S6, F10)

**R1 — Onboarding wizard** · Value ★★★ · Effort M
After sign-up: units (kg/lb), body weight, goal, known lifts (or "I'm new — help me find
them"), training days, and timezone. Lands on a dashboard that is already personalised instead of
"Set your profile goals". Feeds T1, T7, N1, U1, U2.

**R2 — Streaks and weekly summary** · Value ★★ · Effort M
A training streak on the dashboard (scheduled sessions completed, rest days don't break it) and an
optional weekly email: sessions done, PBs, weight change, macro adherence. Uses the existing mail
setup and scheduler.

**R3 — Reminders** · Value ★ · Effort M
Opt-in reminder email (or web push, after U3) on training days if nothing has been logged by a
chosen time, and a weigh-in reminder.

**R4 — Fix rest-day text on dashboard** · Value ★ · Effort S (in `docs/todo.md`)
`docs/todo.md` reports the rest-day message not showing; the current code has it, so confirm on a
rest day (likely the UTC day boundary, F7) and close or fix.

### E. Phone and locale fit (S2, F7, F8)

**U1 — kg / lb preference** · Value ★★★ · Effort M
A per-user unit setting; store canonically (keep lbs columns) and convert on display and input,
with plate buttons in kg (1.25/2.5/5/10/20) when metric. Essential for the UK/Europe audience.

**U2 — Per-user timezone** · Value ★★ · Effort S–M
Store a timezone (default Europe/London) and use it for "today" in nutrition, dashboard,
schedule, API and weight charts. Fixes F7's midnight bug.

**U3 — Installable app (PWA)** · Value ★★ · Effort S–M
A web manifest and icons so the app can be added to the home screen and opens full-screen at the
gym; optionally a service worker that caches the shell. Big perceived quality gain for little code.

**U4 — Mobile pass on the logging flows** · Value ★ · Effort S
Check workout and food logging at 360px: large tap targets, numeric keyboards
(`inputmode="decimal"`), sticky submit button.

### F. Trust, safety and data (S7, F5, F9)

**S1 — Make the landing page honest** · Value ★★★ · Effort S
Remove or reword invented figures (50,000 members, 4.9/5 from 2,400+ reviews), the pricing tiers
and team plans, and features that don't exist (plateau detection, micronutrients, wearables). Point
the copy at what the app genuinely does well. Cheapest fix for the largest risk. *(Owner decision:
which claims are aspirational and should become features instead.)*

**S2 — Secure the integration API** · Value ★★ · Effort M
Move to header-based auth (Sanctum personal access tokens are already installed), store tokens
hashed, compare in constant time, rate-limit the per-user endpoints, and stop returning email.
Keep the current URLs working for a deprecation period so Life OS doesn't break.

**S3 — Data export and account deletion clarity** · Value ★★ · Effort S
"Download my data" (CSV/JSON of workouts, food log, weigh-ins) in settings; the landing page
claims GDPR compliance, and an export is part of that. Check that account deletion removes all
related rows (foreign keys / cascade).

**S4 — Health guardrails** · Value ★ · Effort S
Warn on extreme goals (e.g. targets under ~1,200 kcal, losing more than ~1% body weight a week),
and keep the "not a medical device" note visible in-app, not just on the landing page.

### G. Integrations and growth

**I1 — Strava runs on a cardio page** · Value ★★ · Effort L (in `docs/todo.md`)
OAuth-connect Strava, import runs (distance, time, pace), list them on a "Cardio" page, and count
them in streaks and weekly summaries. Serves Alex; needs a Strava API app and secrets.

**I2 — Write API for Life OS** · Value ★ · Effort M (after S2)
Endpoints to log a weigh-in or a workout from another tool, so Life OS can push data as well as
read it.

**I3 — Multiple programmes** · Value ★★ · Effort L
Let the admin create more than one programme (e.g. beginner full-body 3×/week, the current split,
a 5-day split) and let a user pick one in onboarding. Widens the audience beyond one style of
training.

### H. Engineering health (supports everything above)

**E1 — Shared services for repeated logic** · Value ★★ · Effort S
Macro goals are computed in three places (dashboard, nutrition, API), "today's consumed totals"
in three, and the rotation week in three. Extract them (e.g. `MacroTargets::for($user)`,
`Rotation::forDate()`), so N1, N2, U2 and T7 each change one place.

**E2 — Indexes for growth** · Value ★ · Effort S
Check indexes on `(user_id, created_at)` for `completed_workouts`, `consumeds` and
`body_weights`, which every page filters on.

**E3 — Tests for achievements and PBs** · Value ★ · Effort S
Cover the achievement and personal-best calculations before P3/P4 change them.

---

## 5. Suggested order

**Now — cheap, high value, removes risk**

1. **S1** Honest landing page (S)
2. **N1** Weigh-ins drive macro targets (S)
3. **E1** Shared services for macros/rotation/today (S) — makes the next items one-place changes
4. **U2** Per-user timezone + **T7** rotation anchor (S–M, can share a PR)

**Next — complete the core loop (the reason to use the app)**

5. **T1** Personal training maxes and target weights (M)
6. **T2** Log today's session from the plan (M)
7. **U1** kg / lb preference (M)
8. **R1** Onboarding wizard (M) — once T1/U1/U2 exist there is something to onboard into

**Then — make progress visible and habit-forming**

9. **P1** Progress page with strength charts (M)
10. **P3** Achievements gallery with beginner tiers (S–M)
11. **N3** Personal vs shared foods (M) and **N4** faster food logging (M)
12. **R2** Streaks and weekly summary (M)
13. **U3** Installable PWA (S–M)

**Later — widen the audience and harden**

14. **S2** Secure API (M), **S3** data export (S)
15. **N2** Better macro formula (M), **T3** per-set logging (M), **T4** overload suggestions
16. **I1** Strava, **I3** multiple programmes, **I2** write API

Smaller items (T5, T6, P2, P4, N5, N6, R3, R4, U4, S4, E2, E3) can be picked up alongside
related work or as quick wins.

---

## 6. Issues raised

Each group of opportunities has its own issue, written so an agent can pick it up and open a PR.
Listed in the suggested order; dependencies are noted in each issue.

| Order | Issue | Opportunities | Depends on / easier after |
|-------|-------|---------------|---------------------------|
| 1 | #61 Honest landing page | S1 | — |
| 2 | #62 Shared services, indexes, PB/achievement tests | E1, E2, E3 | — |
| 3 | #63 Macro targets follow weigh-ins | N1 | #62 (optional) |
| 4 | #64 Per-user timezone and programme start date | U2, T7, R4 | #62 (optional) |
| 5 | #65 Training maxes, target weights, e1RM PBs | T1, P4 | — |
| 6 | #66 "Start today's session" checklist, rest timer, mobile pass | T2, T5, U4 | #65 (optional) |
| 7 | #67 kg / lb preference | U1 | — |
| 8 | #68 Onboarding wizard | R1 | #64, #65, #67 |
| 9 | #69 Progress page and full workout history | P1, P2 | #65 (optional) |
| 10 | #70 Achievements gallery and new achievement types | P3 | — |
| 11 | #71 Personal foods, faster logging, "what to eat next" | N3, N4, N5 | — |
| 12 | #73 Streaks, weekly summary, reminders | R2, R3 | #64 (optional) |
| 13 | #74 Installable PWA | U3 | — |
| 14 | #75 Secure API and write endpoints | S2, I2 | — |
| 15 | #76 Data export and complete account deletion | S3 | — |
| 16 | #72 Smarter macro targets and guardrails | N2, N6, S4 | #62, #63 |
| 17 | #77 Per-set logging and overload suggestions | T3, T4 | #65 |
| 18 | #78 Friendlier exercise library and swaps | T6 | — |
| 19 | #79 Strava runs on a Cardio page | I1 | owner: Strava API app |
| 20 | #80 Multiple programmes | I3 | #64 |
