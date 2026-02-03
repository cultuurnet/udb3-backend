# PR Review Guidelines

Review pull requests and provide inline feedback using the GitHub review system.

Focus on actionable improvements only. Skip praise and positive feedback.

Reference relevant files in the `docs/` folder for project standards.

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
