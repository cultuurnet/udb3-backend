---
name: pr
description: Open a pull request the publiq way, with a ticket numbered branch and title, small readable commits, and a draft PR whose body uses Why, Changed, Added and Removed chapters and ends in the Jira link. Use whenever asked to create, open or draft a pull request.
user-invocable: true
---

# Opening a pull request

The ticket number is an argument, for example `/pr III-7493`. When none is given, take it from the current branch name. When the branch has none either, ask for it before doing anything else.

`III-7493` below is only an example. Always substitute the real ticket.

## Branch

Name it `<TICKET>-<short-slug>`, so `III-7493-php-8.2`. Never commit to `master`.

When the work sits on top of another branch that is not merged yet, branch off that one and open the PR against it. Keep each branch to one reviewable idea.

## Commits

Make them small and self contained so the reviewer can follow a history. One logical change per commit.

Order them by cause and effect. The commit that makes something necessary comes before the commit that reacts to it, so the reviewer never has to guess why a change appeared.

Subject lines are short and imperative. No ticket prefix, no trailing period. Add a body only when the diff does not already explain the reason, and keep it to plain sentences.

Once a branch is pushed and under review, add follow up commits instead of rewriting history. Amend only a commit that has not been pushed yet.

## Before pushing

When the repo has a `make ci` target, run it first and make sure it passes. Otherwise run the checks the repo does have. Report the outcome honestly, including anything still failing or skipped.

Then summarise what changed, including that outcome, and get approval. When the implement skill already got approval for this exact work, do not ask again.

## The pull request

Always create it as a draft:

```
gh pr create --draft --base <base> --title "..." --body "..."
```

The title starts with the ticket number, then a short summary. No colon, no brackets, no branch style slug.

```
III-7493 Upgrade to PHP 8.2
```

The body uses `###` chapters with one sentence bullets. Include only the chapters that have something in them and leave the others out completely, with no placeholder and no "nothing" bullet.

```markdown
### Why
- the reason this change exists

### Changed
- what behaves differently now

### Added
- what is new

### Removed
- what is gone

---
Ticket: https://jira.publiq.be/browse/III-7493
```

End with exactly that `---` rule followed by the `Ticket:` line.

## Ticket status

When the draft PR is opened, transition the ticket to Code review, but only when it is the ticket's last repository: a ticket that spans several repos stays In Progress until its final repo is under review. Closing happens with the release, not here.

## Writing

Let lines flow. Do not wrap at 80 characters, these bullets are read in a browser.

No dash connectors in prose, neither the em dash nor a spaced hyphen. Join clauses with a comma, with parentheses, or with a word such as so, because or and. Hyphens inside compound words stay fine.

Keep sentences short and plain. Describe what changed and why. Never write about deploying, rebasing, or how the PRs are stacked.
