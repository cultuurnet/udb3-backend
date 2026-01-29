# PR Review Guidelines

Review pull requests and provide inline feedback using the GitHub review system.

Focus on actionable improvements only. Skip praise and positive feedback.

Reference relevant files in the `docs/` folder for project standards.

## Review Process

1. Start a review using `mcp__github__create_pending_pull_request_review`
2. Get diff information using `mcp__github__get_pull_request_diff`
   - Paginate large diffs using `page` and `per_page` parameters
3. Add inline comments using `mcp__github__add_comment_to_pending_review`
4. Submit review using `mcp__github__submit_pending_pull_request_review` with event type "COMMENT"

## Suggestion Blocks

When suggesting code changes, format them as GitHub suggestion blocks:

- Single-line: ` ```suggestion `
- Multi-line: ` ```suggestion:-0+1 ` (adjust numbers for lines to remove/add)

Only provide suggestion blocks for concrete, actionable fixes.

## Focus Areas

### Code Quality
- Clean code principles and best practices
- Proper error handling and edge cases
- Code readability and maintainability

### Security
- Potential security vulnerabilities
- Input sanitization
- Authentication/authorization logic

### Performance
- Potential performance bottlenecks
- Database query efficiency
- Memory leaks or resource issues

### Testing
- Adequate test coverage
- Test quality and edge cases
- Missing test scenarios

### Documentation
- Code documentation
- README updates for new features
- API documentation accuracy
