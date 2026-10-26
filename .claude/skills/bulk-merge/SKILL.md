---
name: bulk-merge
description: Bulk merge the open pull requests of a repo, one by one and oldest first. Each PR gets the base branch merged in, a green CI run, an approval and a merge before the next one starts. Use whenever asked to merge, land or clean up several open PRs at once.
user-invocable: true
---

# Bulk merging pull requests

The argument is the repo, as a name (`udb3-search-service`) or its pulls URL. Without one, use the current repo. Pass it as `-R cultuurnet/<repo>` to every `gh` command below.

PR numbers after the repo limit the run to those PRs.

## Pick

List them oldest first:

```
gh pr list --state open --json number,title,author,createdAt,isDraft,mergeable,reviewDecision,baseRefName
```

Skip drafts, PRs with conflicts and PRs with unanswered review comments. Show the list with a short verdict per PR and get approval before merging anything.

## One at a time

Each merge changes the base branch, so the next PR has to be tested against it. Start a PR only when the previous one is merged.

1. `gh pr update-branch <n>` merges the base branch into the PR.
2. `gh pr checks <n> --watch` waits for CI.
3. `gh pr review <n> --approve`, unless you are the author.
4. `gh pr merge <n> --merge`.

Stop at the first failing check or refused merge and report it. Never merge with checks failing or pending.

A PR built on another PR's branch moves to the base branch once that PR is merged. Before updating it, check that `baseRefName` is the base branch and that the diff only holds its own commits.

## Report

A table with each merged PR and its merge commit, then the skipped PRs with the reason.
