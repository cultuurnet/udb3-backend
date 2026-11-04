# Claude Code

Shared skills and an output style for working on this repo with Claude Code. They live in `.claude/`, personal settings and plans in that folder stay out of git.

## Skills

Start a skill with its slash command, the argument is optional.

| Command | What it does |
|---|---|
| `/implement III-1234` | Reads the ticket, its epic and Confluence page, works on a ticket numbered branch and leaves the work uncommitted for review |
| `/review 1234` | Reviews a PR, a branch or the local work: functional (ticket, side effects, consistency, tests) and technical (design, guidelines, naming), with issues and nits apart |
| `/pr III-1234` | Splits the work into small commits and opens a draft PR with the Why, Changed, Added and Removed chapters |
| `/api-docs III-1234` | Documents a new or changed endpoint or property in the apidocs repo |
| `/bulk-merge udb3-search-service` | Merges the open PRs one by one, oldest first: base branch merged in, CI green, approve, merge |
| `/dependency-review 2433` | Reviews a Dependabot PR (the bump, usage in `src/`, test coverage, risk) and posts one summary comment. Also runs automatically on new Dependabot PRs through the `claude-dependency-review` workflow |

They need:

- the GitHub CLI, logged in (`gh auth login`)
- a Jira and Confluence connection in Claude Code, see [Connect to Jira & Confluence publiq](https://confluence.publiq.be/spaces/UPW/pages/217580782/Connect+to+Jira+Confluence+publiq)
- for `/api-docs`: the apidocs repo checked out next to this one

## Output style

`To the point` keeps answers short and structured: tables, trees and numbered steps instead of long prose.

Turn it on with `/output-style` and pick `To the point`. Claude Code stores the choice in `.claude/settings.local.json`, so it only applies to you. Switch back the same way.
