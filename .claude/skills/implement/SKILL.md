---
name: implement
description: Implement a Jira ticket in this repo, from ticket to a reviewed local branch. Reads the ticket and its context, works on an III numbered branch, keeps commits small, and hands off to the pr skill once the work is reviewed. Use whenever asked to implement, start or pick up a ticket.
user-invocable: true
---

# Implementing a ticket

The ticket number is an argument, for example `/implement III-7541`. When none is given, take it from the current branch name. When neither has one, ask for it before doing anything else.

## Reading the ticket

Pull the ticket from Jira with its description and comments. When it belongs to an epic, read the epic and the Confluence page it links.

Summarise what will be built before writing any code. When the ticket leaves a choice open, ask before choosing. When the ticket is blocked, say so and stop.

## Branch

Create it from `master` and name it `<TICKET>-<short-slug>`, so `III-7541-subevent-reservation-link`. One ticket per branch. Never branch off a branch that carries unrelated work.

## Before writing code

Find the code the ticket touches and read it first: the command, the handler, the projector, the schema validation. Also read the tests that already cover that area.

Reproduce the ticket in a test before fixing anything, so the test fails first.

## While implementing

Follow the existing patterns in the file and package. Do not introduce a new style.

One logical change per commit, ordered by cause and effect, subject lines short and imperative with no ticket prefix. A first commit may set up the failing test, a second one makes it pass.

Let the commit body flow as plain sentences without hard wrapping at 80 characters. The viewer reflows; wrapped text only looks ragged.

Do not commit while implementing. The work stays uncommitted in the working tree until it has been reviewed and the commits are asked for. Then split it into the commits described above.

## Tests

Every change ships with tests. Unit tests live under `tests/`, mirroring the path of the code they cover in `src/`. API behavior gets Behat scenarios under `features/`, one `.feature` file per area, written from the ticket's acceptance criteria and following `docs/features/guidelines.md` (use the helper steps that create a place or event in one step).

Run everything through `make`, which runs inside Docker, never through `vendor/bin` directly: `make test` for the unit tests, `make feature-filter path=features/...` for a single feature file, `make ci` for the full check.

## Related repos

A ticket here usually needs matching changes in sibling repos. Check them per ticket and say which ones are needed:

- `apidocs`: the JSON schema and endpoint documentation change land there first. `udb3-json-schemas` is split from apidocs automatically on push, so never edit it directly. While working, point `composer.json` at the `dev-<branch>` of `udb3-json-schemas`, and switch it back before merging.
- `udb3-search-service`: search projections when event properties change what is searchable

When a sibling repo is needed but not available locally, say so instead of silently skipping it.

New schemas never get `additionalProperties: false`. Unknown properties in a request are ignored (robustness principle), and only a predictable mistake gets an explicit rejection.

## Ticket status

When work starts, the ticket moves with it: transition it to In Progress. When the work pauses because a question or input is needed, use Feedback needed. When it stops without finishing, use Stop progress. Nothing is closed here.

## Review before pushing

The work stays on the local branch for review. Nothing is pushed at this stage.

When the implementation looks done, run `make ci` and report the outcome honestly, including anything still failing or skipped.

Then summarise what changed and in which files, and ask for approval before anything is pushed. Approval of the summary is approval to push and open the PR, and the pr skill takes over from there.
