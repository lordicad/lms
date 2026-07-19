# For Icadhensem05 — two patches for the admin Kandungan section

Copy the message below. The two `.patch` files sit next to this one; both apply
cleanly to `origin/main` as of `803390d`.

This file and the patches are untracked scratch — delete them once merged.

---

Hi — two patches for the admin Kandungan section, both based on current `main`
(`803390d`). Verified applying cleanly, and running locally against the demo seed.

**0001 — Admin video list: preview in a dialog instead of leaving the page**

The View action sent an admin to the student watch page, which drops them out of
the list they're auditing. It now opens the video in a modal instead.

Not `<x-player>`: that counts a view and saves watch progress. Right for a
student, wrong for an audit — an admin previewing shouldn't move a teacher's
numbers or their Bakat engagement score.

The dialog is an `x-if`, not an `x-show`. Worth knowing: `x-show` reproducibly
failed to re-hide the overlay on close, even though the same `lesson` expression
drove the `x-if` nested inside it correctly. The result was an invisible
full-screen overlay swallowing every click, with the YouTube iframe still playing
audio underneath. `x-if` removes the element, so playback genuinely stops.

**0002 — Add admin Kandungan > Bahan oversight page**

Lists every teacher's material behind the same Subjek/Tahun filter as the video
list, with totals (all / PDF / DOCX / PPTX) that follow the filter. Bahan is no
longer greyed out in the menu.

Preview opens in a dialog. PDFs and images render in place; Word/PowerPoint/Excel
can't be shown by a browser, so it says so and offers the download rather than
faking it or shipping the file to a third-party viewer. Download is also its own
row action.

Two things in here you may want to look at specifically:

1. `DownloadController` no longer counts a download when an admin opens a file.
   The list displays `download_count`, so without it the audit inflates the number
   it's auditing. Mirrors `WatchController::markViewed`, which already skips
   non-students.
2. The Subjek/Tahun filter is now one shared helper, since both lists narrow on
   the same chapter relations.

**To apply**

    git am 0001-*.patch 0002-*.patch
    npm run build && git add public/build && git commit -m "build assets"
    git push

No migrations. Assets are deliberately not in the patches — please rebuild your
side, see below.

**Two things worth raising**

*`.deploy.env` isn't gitignored.* `.env*` only matches names starting `.env`, so
`.deploy.env` slips through — and this repo is public. A single `git add -A` would
publish the live FTP password. 0002 adds `/.deploy.env` to `.gitignore`. The same
gap covers anything similar (`autodeploy.md` holds the deploy token).

*The asset build isn't reproducible.* `tailwind.config.js` scans
`storage/framework/views`, so the CSS bundle depends on which pages happen to be
compiled on the builder's machine. Same source gave me 57.8 KB / 72.1 KB / 72.3 KB
depending on view-cache state — and a "clean" build silently dropped classes that
production was using. That's why I left `public/build` out of the patches. Dropping
that glob would fix it; the source blades are already scanned, so it adds nothing.

**One ask**

Could you add `ShadatulSofia` as a collaborator? I'm building on this regularly and
right now every change needs you to press the button — three features today, each
waiting on a merge. Happy to work on branches/PRs rather than pushing to `main`.

Thanks!
