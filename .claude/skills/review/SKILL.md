---
name: review
description: Review a pull request, a branch or local uncommitted work the publiq way, functional first (does it do what the ticket asks, without side effects, tested well) and technical second (design, guidelines, naming). Reports real issues and nits separately, short and without repetition. Use whenever asked to review a PR, a branch or the current changes.
user-invocable: true
---

# Reviewing a pull request

The target is an argument: a PR number or URL, or a branch. Without one, review the local work: the current branch against its base, including uncommitted and untracked files. That way it also works before anything is pushed.

## Gather

- The changes: `gh pr view <n>` and `gh pr diff <n>` for a PR, otherwise `git diff <base>` plus `git status` for new files.
- The ticket from the title or branch name, with its comments. When it has an epic, read the epic and the Confluence page it links. These are the requirements.
- The API docs, only when the change touches the API: the apidocs PR with the same ticket key (`gh pr list -R cultuurnet/apidocs --state all --search <ticket>`), otherwise the current docs of that endpoint. The schema, examples and status codes there are the contract the code has to match. No apidocs PR for an internal change is fine; for an API change it is worth a question.
- The code around each change, not only the diff lines.

## Functional

1. **Requirements.** Every point of the ticket is implemented, nothing more, and the behaviour matches the API docs. List what is missing or added without being asked.
2. **Side effects.** What else runs through the changed code: other endpoints, projectors, listeners, replays, places next to events, existing stored data.
3. **Consistency.** It behaves like comparable features (the same validation, errors, PATCH semantics, naming in the API). Look the comparable feature up instead of assuming.
4. **Tests.** Each behaviour change is covered, and the tests are good: edge cases, the unhappy path, fixtures that match reality, assertions on the result instead of on the mocks.

API changes also go through the backwards compatibility rules in `docs/review-guidelines.md`.

## Technical

1. **Design.** The abstraction fits the problem and the existing structure: the right layer, no needless indirection, no duplication of something that exists.
2. **Guidelines.** `docs/coding-guidelines.md` is applied. Mention a rule only when it is broken.
3. **Naming.** Correct, consistent with the surrounding code and the domain language (ticket, Confluence), and clear without a comment.

## Verify before reporting

Check every finding against the code. Drop what you cannot point to in a file and line. A guess goes under Questions, not under Issues.

## Report

```
III-1234 Title  https://github.com/cultuurnet/<repo>/pull/<n>
<one line verdict: ready, ready after the issues, or needs rework>

Issues
1. `path/File.php:42` What is wrong and what it causes. Fix: what to do.

Nits
- `path/File.php:10` Short remark.

Questions
- Something the ticket leaves open.
```

- **Issue:** must change before merge. Wrong or missing behaviour, a side effect, a breaking API change, an untested behaviour change, a design choice that will cost later.
- **Nit:** no behaviour impact. Naming, readability, a small guideline slip.
- Each finding once, in the section where it fits best. No praise, no summary of the PR, no repeating the ticket. Leave out an empty section.
- Link tickets and PRs with their full URL.

Do not post the review on GitHub unless asked. When asked, write the comments in the voice of the person who asked: short, plain and casual.
