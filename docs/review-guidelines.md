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
- Every change should be evaluated for whether it needs new or updated unit tests and/or feature (acceptance) tests

### Documentation
- Code documentation
- README updates for new features
- API documentation accuracy

### API Backwards Compatibility
The most important guideline when reviewing API-related changes (controllers, request/response payloads, JSON-LD projections, OpenAPI/Swagger docs) is that **we must not introduce breaking changes**. UiTdatabank is consumed by many external integrators, so any change visible on the API surface needs to stay backwards compatible.

Treat the following as breaking changes and flag them:
- Renaming or removing an API endpoint, or changing its HTTP method
- Renaming, removing or restructuring a field in a request or response body
- Changing the type of a field's value
- Changing values that integrators match on, such as:
    - UUID-based identifiers (e.g. `id`, `cdbid`, `organizerId`, `placeId`, `labelId`, `mediaObjectId`, `productionId`, `ownershipId`, `userId`).
    - Enum values (e.g. `workflowStatus`, `audienceType`, `calendarType`, `attendanceMode`, `bookingAvailability`, `status`, `eventStatus`, `availabilityType`, `role`, `permission`).
    - URL formats and `@id` / `@type` values in JSON-LD output.
- Tightening validation rules so payloads that used to be accepted are now rejected (new required fields, stricter regexes, smaller min/max bounds, new enum restrictions).
- Changing HTTP status codes or error response for existing scenarios.

**Reflex check:** if an internal/refactor change forces you to update the API feature tests (the `*.feature` files under `features/`), that is a strong signal the public API contract is shifting. Pause and verify whether the change is actually backwards compatible, if not, it needs to be reworked (e.g. additive change, new endpoint version, or a deprecation path) before it can be merged.
