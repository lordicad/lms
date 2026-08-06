# LMS MOE Improvement Implementation Brief

## Purpose

Implement the teacher, student, and administrator improvements in this document in the existing LMS MOE Laravel application.

This is an implementation task, not only a visual mock-up. Update the database, models, validation, controllers/services, Blade views, routes, translations, responsive styles, exports, and automated tests wherever required. Preserve the existing Laravel 12 + Blade + Tailwind + Alpine.js architecture and the design system described in `DESIGN-BRIEF.md`.

## Instructions for Claude

1. Read `DESIGN-BRIEF.md`, `HANDOVER.md`, the relevant routes, controllers, models, migrations, Blade views, components, and existing tests before editing.
2. Inspect the current implementation and reuse existing services/components where practical. Do not replace real data with demo or hard-coded values.
3. Preserve Bahasa Melayu and English support. Add every new user-facing string to both locales; do not hard-code one language in a Blade view.
4. Preserve light/dark themes, WCAG AA contrast, keyboard access, visible focus states, reduced-motion support, responsive behavior, and 44px minimum touch targets.
5. Use eager loading and aggregate database queries. Avoid N+1 queries, especially on dashboards, rankings, and paginated lists.
6. Preserve authorization boundaries:
   - Students only access student functions and their own personal data.
   - Teachers only manage/view their own content and quiz results.
   - Admin analytics are read-only except for already-authorized user-management actions.
7. Add migrations rather than editing historical migrations that may already have run.
8. Do not silently remove data. If a route or page is retired, preserve backward compatibility with an authenticated redirect where specified.
9. Run formatting and the relevant test suite after implementation. Add feature tests for every new filter, pagination rule, relationship, dashboard aggregate, and export endpoint.

---

## 1. Shared filter behavior

### 1.1 Required order

Every page or form containing both Year (`Tahun`) and Subject (`Subjek`) dropdowns must display and process them in this order:

1. **Year**
2. **Subject**
3. **Chapter**, when the page also has a Chapter selector

The intended dependency is:

```text
Year -> Subject -> Chapter
```

This is a UI and selection-flow change. Do not alter the existing content relationships in the database.

### 1.2 Dependent dropdown rules

- Disable Subject until a Year is selected, unless the page supports an explicit `All years` view. In an all-years view, Subject may contain all subjects.
- After a Year is selected, show only subjects available for that Year.
- After a Subject is selected, show only chapters belonging to the selected Year + Subject combination.
- If Year changes and the current Subject is invalid for the new Year, clear Subject and Chapter.
- If Subject changes, clear an invalid Chapter.
- Preserve valid filter values in the query string after submitting, sorting, or changing pagination pages.
- Validate all combinations on the server. Client-side filtering is a convenience, not the source of truth.
- Provide a working non-JavaScript form submission fallback.
- Use a shared Blade/Alpine component or shared helper instead of duplicating dependency logic across pages.

### 1.3 Scope

Audit all teacher, student, and admin views and forms that contain Year and Subject dropdowns, including create/edit forms and content filters. Apply the same order and dependency consistently.

### Acceptance criteria

- There is no page where Subject appears before Year when both controls are present.
- A user cannot submit a Subject that is not available for the chosen Year.
- Existing query-string filters and pagination links continue to work.

---

## 2. Teacher portal (`Cikgu`)

### 2.1 Quiz attempt/student list

On the per-quiz statistics page, paginate the completed student-attempt list at **10 attempts per page**.

- Use Laravel pagination, not client-side hiding of a fully loaded collection.
- Sort newest completed attempt first unless the current UI already offers a selected sort.
- Show a continuous row number across pages:

```php
$attempts->firstItem() + $loop->index
```

Expected numbering: page 1 is 1-10, page 2 is 11-20, and so on.

- Preserve any active filters in pagination links.
- Summary calculations such as completed count, average score, pass/fail totals, and per-question correctness must use **all matching attempts**, not only the current page.
- If multiple completed attempts by one student are intentionally allowed, each attempt remains a separate numbered row. Do not silently deduplicate.

### 2.2 Chapters page becomes read-only navigation

Change the teacher Chapters page so it no longer manages chapter records.

Remove from this page:

- Add Chapter form/section
- Edit button/action
- Delete button/action

Add a **View (`Lihat`)** button for every chapter.

The chapter detail view must show all content uploaded by the signed-in teacher for the selected chapter, grouped into:

- Videos
- Materials
- Quizzes

For each group:

- Show useful metadata already available, such as title, publish/type status, and created/uploaded date.
- Provide a safe preview/open action using the existing teacher-accessible content routes.
- Show a clear empty state when that content type has no items.
- Do not include content owned by other teachers.
- Enforce ownership/authorization on the server; hiding a button is not sufficient.

Routing expectations:

- Add a teacher chapter show route, for example `GET /cikgu/bab/{chapter}`.
- The Chapters index should expose only `index` and `show` functionality in the teacher UI.
- Remove or disable teacher-facing create/store/edit/update/destroy routes if they are no longer required elsewhere. Before deleting route behavior, search for all callers and update them safely.

Important: chapter selection must remain available in Video, Material, and Quiz creation/editing. Removing chapter management from this page must not break content creation.

### 2.3 Quiz list: add Chapter filter

Add a Chapter dropdown beside the Subject dropdown on the teacher Quizzes page. The complete filter order is:

1. Year
2. Subject
3. Chapter

Rules:

- Chapter options depend on the selected Year + Subject.
- The selected Chapter filters the quiz query by `chapter_id`.
- Invalid combinations return a validation response or an empty safe result; never expose unrelated data.
- Reset/Clear removes all three filters.
- Preserve filters while paginating.

### 2.4 Replace Talent page with Teacher Home analytics

The current standalone Teacher Talent (`Bakat`) page must no longer appear as a separate destination.

- Merge its useful analytics into Teacher Home (`Papan Pemuka`).
- Remove the Talent/Bakat item from teacher navigation.
- Redirect the old authenticated `cikgu.bakat` URL to `cikgu.dashboard` so bookmarks do not break.
- Do not discard the existing `TalentService` or its transparent score/sub-scores if they are still useful. Integrate them into Home without inventing values.

Teacher Home must contain the following real-data sections.

#### A. Interactive content-performance pie/doughnut chart

Provide one accessible interactive chart area with four selectable metrics:

1. Most viewed videos
2. Most favourited videos
3. Most downloaded materials
4. Most attempted quizzes

Behavior:

- Use tabs, segmented buttons, or a select control to switch the metric without a full page reload.
- Display the top 5 items for the selected metric; combine the remaining non-zero items into `Others` when applicable.
- Each segment and its legend/tooltip must show content title, raw count, and percentage of the displayed total.
- Clicking a segment or legend item should open the corresponding teacher-owned content detail/statistics destination when one exists.
- Provide an accessible HTML list/table containing the same values. The chart cannot be the only way to read the data.
- Show a truthful empty state rather than a chart when every value is zero.

#### B. Weekly upload activity trend

Show the teacher's upload frequency for the latest 7 calendar days, including today.

- Count Videos + Materials + Quizzes created per day.
- Prefer a line or bar chart because this is a time series.
- Allow the user to inspect each day and see the date and count, with separate content-type counts if the chosen chart supports them.
- Include zero-activity days so the time axis is continuous.
- Use the application timezone.

#### C. Student quiz pass/fail pie chart

Show totals for completed attempts across all quizzes owned by the signed-in teacher:

- Passed
- Failed

Use the application's existing quiz pass rule (`QuizAttempt::passed()` or equivalent) so this chart agrees with result pages and reports.

- Tooltip/legend must show count and percentage.
- The center/summary should show total completed attempts.
- Handle zero attempts with an empty state and do not divide by zero.
- Include an accessible text/table equivalent.

#### D. Existing Home content

Keep useful existing Teacher Home information, but reorganize it to avoid duplicate metrics. The new analytics should feel like the main Home experience, not an embedded copy of a separate page.

### 2.5 Teacher profile fields

The Teacher Profile must support:

- Full name
- Username
- Password change
- Phone number
- Position
- School
- Subjects taught (one teacher can teach multiple subjects)
- Homeroom teacher class (dropdown containing classes in the selected teacher's school)

Implementation notes:

- Keep password handling secure: never display the saved password. Use current password, new password, and confirmation fields in the existing separate password-update flow.
- Model Subjects as a proper many-to-many relationship (for example a teacher-subject pivot table), not a comma-separated string.
- Model School and Class as relations if equivalent tables already exist. Inspect the project before adding new structures. A class must belong to a school and should have an associated Year where the domain requires it.
- `homeroom_class_id` is nullable because not every teacher is a homeroom teacher.
- Only list active/valid classes from the teacher's selected school.
- Clear or reject the homeroom selection when School changes and the chosen class is no longer valid.
- Position should use an existing controlled list if one exists; otherwise use a validated text field and document the decision.
- Phone should allow international/Malaysian formatting while storing a normalized value where practical.
- Subject selection must be an accessible multi-select control with visible selected values.
- Update admin teacher create/edit forms and seed/factory data as necessary so the same schema remains coherent.

### 2.6 Content totals

Add a clearly visible total to each teacher content index:

- Video page: total videos uploaded by the signed-in teacher
- Material page: total materials uploaded by the signed-in teacher
- Quiz page: total quizzes created by the signed-in teacher

Rules:

- The primary total means all records owned by that teacher.
- If filters are active, also show the filtered result count with an unambiguous label; do not replace the all-time ownership total with an unexplained filtered number.
- Counts must come from the database and remain correct when the current table page contains fewer records.

---

## 3. Student portal (`Murid`)

### 3.1 Offline Saves filters and teacher labels

On Student Offline Saves (`Simpanan Offline`), add dependent filters in this order:

1. Year
2. Subject

Behavior:

- Year determines the available Subjects.
- By default, use the student's active/browsing Year already managed by the application, unless a valid Year is supplied in the query string.
- Apply both filters consistently to Videos and Materials.
- Clear Filters restores the appropriate default view.
- Preserve filter values in query strings and validate them server-side.

Add the uploading teacher's name to every displayed Video and Material, using a clear label such as:

```text
Teacher: [Full Name]
```

- Eager-load the teacher relation.
- The teacher label is informational and must not expose private teacher contact/profile fields.
- Preserve current offline/download rules: uploaded videos may download, YouTube videos remain online-only, and material download behavior remains unchanged.

### 3.2 Leaderboard: Top 100

Change the student leaderboard from Top 10 to **Top 100** within the existing scope (the student's Year and optional Subject filter).

- Show ranks 1 through 100 when enough eligible students exist.
- Keep the signed-in student's pinned row when they fall outside the Top 100.
- If pagination is used for usability, ranking numbers must remain continuous and the system must still represent the Top 100, not only the first page.
- Do not expose students from another Year.
- Keep deterministic tie handling consistent with `LeaderboardService`.

### 3.3 Student profile fields and layout

The Student Profile must support:

- Full name
- Username
- Password change
- Email
- School
- Class
- Year
- Homeroom teacher (dropdown containing teachers applicable to the student's school/class)
- Guardian name
- Guardian phone number
- Guardian email

Implementation notes:

- Keep password updates in the secure current/new/confirmation flow. Never reveal the stored password.
- Email may remain nullable only if that matches the existing product rule; if supplied, validate format and uniqueness according to current authentication requirements.
- School, Class, Year, and Homeroom Teacher selections must be relational and mutually consistent.
- Filter Class options by School and Year.
- Filter Homeroom Teacher options to appropriate active teachers in the selected School; prefer the teacher assigned to the selected Class when that relationship is available.
- Reject tampered IDs on the server.
- Add nullable guardian fields via a new migration; validate phone/email formats and sensible maximum lengths.
- Update admin student create/edit views, factories, seeders, API resources/endpoints, and mobile contracts if these profile fields are shared with them.

#### Search bar alignment

Align the Student Profile search bar with the main content container and the other page content at all responsive breakpoints.

- Reuse the same max-width, horizontal padding, and grid boundary used by the profile content.
- Avoid one-off magic offsets.
- Verify desktop, tablet, and mobile layouts in both languages.

---

## 4. Admin portal (`Admin`)

### 4.1 Dashboard layout order

Move the four existing summary cards to the bottom of the Admin Home page:

- Total students
- Total teachers
- Total videos
- Total quizzes

They must remain visible, responsive, and based on all-time database totals.

Recommended page order:

1. Dashboard heading, reporting period, and export actions
2. Top contributors
3. Top-performing content
4. Interactive platform activity
5. Last week's registrations
6. Existing actionable oversight/pending section, if retained
7. Four all-time summary cards at the bottom

### 4.2 Top 3 teacher contributors and full ranking

Show the Top 3 teacher contributors on Admin Home and provide a clickable action to view the complete contributor ranking.

Use a transparent contribution metric:

```text
Contribution total = number of Videos + Materials + Quizzes created by the teacher
```

Ranking rules:

- Rank descending by contribution total.
- Tie-break by Videos, then Materials, then Quizzes, then teacher full name, then teacher ID for deterministic output.
- Display teacher name, school when available, total contribution, and the three content-type counts.
- Do not use the Talent score as the contribution metric unless the UI explicitly labels a separate Talent ranking.
- Add a dedicated paginated full-ranking page or reuse an appropriate existing admin page with a clearly named Contributors view.
- The Home `View all contributors` action must be keyboard accessible and link to that complete ranking.

### 4.3 Top-performing content with teacher attribution

On Admin Home, show:

- Most viewed Video + its teacher name + view count
- Most downloaded Material + its teacher name + download count
- Most attempted Quiz + its teacher name + completed-attempt count

Rules:

- Use real aggregate values.
- Completed quiz attempts are the attempt metric.
- Handle ties deterministically (newest metric count tie may then use title/ID; document the chosen stable order in code/tests).
- Each item must link to a safe admin read-only preview/detail or its relevant ranked list.
- Teacher names may link to the contributor ranking/detail view.
- Admin previews must not increment student-facing view/download/attempt statistics.
- Show a meaningful empty state when no content exists.

### 4.4 Interactive Platform Activity

Upgrade Platform Activity from static bars into an interactive chart with a selectable reporting period:

- Last 7 days (default)
- Last 30 days
- Last 12 months

Track at least:

- Video views
- Completed quiz attempts
- Passed quiz attempts
- Content uploads (Videos + Materials + Quizzes)

Behavior:

- Display a time series using real grouped aggregates.
- Allow series to be toggled through an accessible legend/control.
- Tooltips show period/date and raw counts.
- Include zero-value periods.
- Include an accessible tabular/text alternative.
- Use application timezone boundaries consistently.
- Use a lightweight chart library already present in the project where possible. If adding one, avoid external CDN dependencies and document/build it through the existing asset pipeline.

### 4.5 Registrations: last week, not merely recent

Replace `Recent registrations` with **Registrations in the last 7 days**.

- Include only Teacher and Student accounts created from the start of the rolling 7-day window through now.
- Sort newest first.
- Make the period explicit in the heading/subtitle.
- Do not fill the section with older users just to reach a fixed row count.
- Paginate or provide a `View all` path if the list can be long.
- Show a correct empty state when there were no registrations in the period.

### 4.6 Generate PDF and Word dashboard reports

Add two Admin Home actions:

- Generate PDF
- Generate Word (`.docx`)

Both exports must generate a server-side report of the data shown on Admin Home for the selected Platform Activity period.

Required report content:

- Report title
- Generated timestamp and application timezone
- Selected reporting period/date range
- Top 3 contributors and the metric definition
- Top-performing Video, Material, and Quiz with teacher attribution
- Platform Activity data
- Last-7-days registrations
- Existing pending/oversight items if they remain on the dashboard
- The four all-time summary totals

Export behavior:

- Use dedicated authenticated admin-only GET endpoints with explicit format routes/names.
- Validate the requested reporting period against an allow-list.
- Use appropriate response content type and download filename.
- PDF should render charts in a print-safe way or provide equivalent labeled tables; do not depend on browser JavaScript during server-side generation.
- DOCX should contain semantic headings and readable tables. Do not rename HTML or text files with a `.docx` extension.
- Escape user-provided data.
- Keep report generation read-only: it must not increment analytics counters.
- If a new package is necessary, use a maintained Composer package compatible with the project, commit lock-file changes, and add tests for authorization, response type, and core report content.

---

## 5. Data and domain changes

Inspect the existing schema first. Add only the structures not already represented. The final model should support the following concepts without storing relational data as comma-separated strings:

- Teacher phone number
- Teacher position
- Teacher school
- Teacher-to-many-Subjects relation
- Optional teacher homeroom Class
- Student school
- Student Class
- Student Year (existing `grade_id` may already satisfy this)
- Student homeroom Teacher where needed by the agreed domain model
- Student guardian name, phone, and email

Prefer normalized structures such as:

- `schools`
- `school_classes` (avoid reserved/ambiguous table naming)
- `subject_teacher` pivot
- Nullable foreign keys on `users` where appropriate

Before creating a separate student `homeroom_teacher_id`, determine whether the teacher is already unambiguously derived from the selected Class's homeroom assignment. Avoid two conflicting sources of truth. If derived, display it as a filtered/read-only resolved choice; if the product requires a selectable override, enforce consistency with the Class and School.

Use foreign keys, indexes, safe nullable migrations for existing records, model relationships, factories, seeders, and validation messages. Define sensible `nullOnDelete`/`restrictOnDelete` behavior so deleting or deactivating an account does not corrupt student/profile data.

---

## 6. Dashboard/chart implementation standards

- All metrics must be calculated on the server from persisted data.
- Every chart must have a non-canvas accessible equivalent (list or table).
- Charts must resize without horizontal page overflow.
- Use semantic colors and patterns/labels; do not rely on color alone.
- Tooltips must work with keyboard focus as well as pointer hover where supported.
- Respect `prefers-reduced-motion`.
- Empty and single-item datasets must render cleanly.
- Use a single shared chart integration/component instead of page-specific global scripts.
- Format dates and labels in the active locale.

---

## 7. Performance and security

- Paginate database queries; do not load all attempts, users, or ranking rows merely to display one page.
- Use `withCount`, grouped aggregates, joins/subqueries, and eager loading rather than loops that issue queries.
- Add indexes needed for new foreign keys and common dashboard date filters.
- Cache expensive admin aggregates only if needed, with a short duration and a clear invalidation/staleness policy.
- Authorize every new route and resource.
- Validate dependent IDs together (Year/Subject/Chapter, School/Class/Teacher).
- Continue hashing passwords through Laravel's hashing/cast facilities.
- Never include passwords, guardian contact data, student private data, or unrelated teacher contact data in analytics/export output.

---

## 8. Minimum automated test coverage

Add or update tests for:

### Shared filters

- Year appears/behaves before Subject.
- Subject results are limited to the selected Year.
- Chapter results are limited to Year + Subject.
- Tampered combinations are rejected.

### Teacher

- Quiz attempts paginate at 10 and page 2 starts numbering at 11.
- Quiz summary metrics still use all completed attempts.
- Chapter index has no add/edit/delete controls.
- Chapter View returns only that teacher's Video/Material/Quiz content.
- Quiz Chapter filter returns only matching quizzes.
- Old Talent route redirects to Teacher Home and navigation no longer links to it.
- Teacher dashboard metric values, seven-day zero filling, and pass/fail totals are correct.
- Teacher profile multi-subject and school/class constraints work.
- Content index totals reflect all owned records and filtered counts are labeled separately.

### Student

- Offline Year/Subject filters apply to Videos and Materials.
- Offline content displays the correct teacher label without private information.
- Leaderboard is capped at 100 and remains scoped to the student's Year.
- Student outside the Top 100 receives a pinned own row.
- Profile rejects mismatched School/Class/Year/Homeroom Teacher combinations.
- Guardian fields save and validate correctly.

### Admin

- Contributor ranking uses the documented formula and deterministic tie-breaks.
- Top content uses views/downloads/completed attempts and includes teacher attribution.
- Platform Activity returns correct 7-day, 30-day, and 12-month buckets including zero periods.
- Registrations include only accounts created in the last 7 days.
- PDF and DOCX routes reject non-admin users.
- Exports have correct MIME types, filenames, selected period, and core report content.
- Export/preview actions do not increment analytics counters.

---

## 9. Manual QA checklist

Test in both Bahasa Melayu and English, light and dark themes, and at mobile/tablet/desktop widths.

- [ ] All Year/Subject dropdowns follow `Year -> Subject` order.
- [ ] All Chapter dropdowns follow the selected Year + Subject.
- [ ] Teacher attempt pagination shows 10 rows and continuous numbering.
- [ ] Teacher Chapters page is read-only and View shows all three owned content groups.
- [ ] Teacher Quiz filter includes Chapter.
- [ ] Teacher Home contains interactive content performance, upload trend, and quiz pass/fail analytics.
- [ ] There is no standalone Teacher Talent navigation item; its old URL redirects.
- [ ] Teacher profile supports all requested fields and multiple Subjects.
- [ ] Video, Material, and Quiz indexes display accurate totals.
- [ ] Student Offline Saves filters work and every item displays its teacher.
- [ ] Student leaderboard shows Top 100 within the correct Year.
- [ ] Student profile fields save correctly and search aligns with the content container.
- [ ] Admin summary cards appear at the bottom.
- [ ] Admin Home shows Top 3 contributors and a working full-ranking link.
- [ ] Admin top content includes teacher attribution and safe read-only links.
- [ ] Platform Activity period switching and series toggles work.
- [ ] Registrations are restricted to the last 7 days.
- [ ] PDF and DOCX reports download, open successfully, and match dashboard data.
- [ ] Charts have readable accessible alternatives and truthful empty states.
- [ ] No new N+1 queries, authorization leaks, console errors, or broken mobile layouts.

---

## 10. Definition of done

The work is complete only when:

1. All requirements above are implemented with real persisted data.
2. Relevant migrations, translations, desktop/mobile-responsive views, API/mobile contracts, and tests are updated.
3. Legacy links affected by the Teacher Talent merge redirect safely.
4. PDF and DOCX outputs are valid files and accurately reflect the dashboard.
5. The complete automated test suite and formatter pass.
6. A short implementation summary lists schema changes, new/changed routes, packages added, tests run, and any explicitly documented assumptions.
