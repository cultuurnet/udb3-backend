---
name: api-docs
description: Document a new or changed UiTdatabank endpoint or property in the publiq apidocs repo before the backend is built. Covers where the docs go, the branch, naming and wording, internal or public, lint, release notes and the draft PR. Use whenever asked to add, spec or change API docs, a JSON schema model, or an endpoint in entry.json.
user-invocable: true
---

# Documenting an API change

The docs come first. The backend is built against them, so the endpoint, its body and its responses are agreed in the apidocs PR before any code is written.

The repo is https://github.com/cultuurnet/apidocs, checked out next to this one (`../apidocs`). Read its `CLAUDE.md` and `REVIEW.md` before writing anything: they hold the schema, naming and REST conventions, and this skill does not repeat them.

## Branch

Name it `<TICKET>-<short-slug>`, the same as in the backend. When the change needs something that is only on an unmerged docs branch (a property, a model), branch off that one and open the PR against it.

Never reference a branch that is gone. Check `git branch -r` before naming one in a ticket or plan.

## Where things go

| What | Where |
|---|---|
| Endpoint | `projects/uitdatabank/reference/entry.json`, next to related endpoints |
| Request or response body | a model in `projects/uitdatabank/models/`, referenced with `$ref` |
| Guide or explanation | `projects/uitdatabank/docs/entry-api/` |
| Release note | `projects/release-notes/docs/uitdatabank`, under "Upcoming" |

Reuse existing models with `$ref` (for example `common-bookingAvailability-type.json`) instead of copying their properties.

`entry.json` round-trips cleanly through `json.dumps(d, indent=2, ensure_ascii=False) + '\n'`, so a small script is safer than hand editing a 20k line file. Keep the `tags` list sorted, lint fails otherwise.

## Designing the endpoint

- Follow the existing shape: a resource path plus a sub-resource, like `/events/{eventId}/booking-availability`. A partner or channel gets its own group, like `uitpas/` or `ticketing/`.
- No verbs in the path. No values in the path that the backend has to parse; put them in the body.
- Pick the method by what the URL names. PUT only when the URL identifies the resource being replaced. When the target is in the body, or one call touches several resources, use POST.
- Write success as `204 No Content` unless the caller needs something back, like the rest of the Entry API.
- List every response with what triggers it. Reuse `#/components/responses/Unauthorized`, `Forbidden` and `NotFound` where the generic text fits.

## Wording

Write for an integrator who has never seen our code. No internal table names, class names or ticket numbers in the docs.

Keep public docs neutral about partners. Do not name a partner, its accounts or its ids in descriptions or examples; use `example-provider:1234` style values. The partner specific details go in the ticket or a direct message.

## Internal or public

`x-internal: true` hides an endpoint from the published docs.

| Use internal | Keep public |
|---|---|
| Only our own frontends call it (`contributors`, `history`) | An external partner has to integrate with it |

A public endpoint that only some clients may call gets a warning note at the top of its description saying who can use it and that others get a 403.

Stoplight branch previews need a login, so external partners cannot read a branch link. Share the payload with them directly until the docs are on `main`.

## Before pushing

Run the lint and make sure it passes:

```
npx spectral lint projects/uitdatabank/reference/entry.json --fail-severity warn
```

For guide changes, also run `yarn docs:lint`.

Check `REVIEW.md` once more: every new endpoint or property needs a release note under "Upcoming".

## The pull request

Follow the `pr` skill for commits, title and body, with these additions:

- Base it on the branch it was stacked on, not on `main`.
- Add a `### Live preview` chapter above the `---` rule with the Stoplight link: `https://publiq.stoplight.io/docs/uitdatabank/branches/<branch>/...`
- Do not move the ticket to Code review: the backend still has to follow.

Once the PR is open, update the ticket so it points at the docs as the source of the contract, and drop any "blocked" note that no longer holds.
