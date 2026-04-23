# Implementation Plan: `overnight` boolean on subEvents

## Overview

Add an optional `overnight` boolean property to subEvents for events with `calendarType = single` or `calendarType = multiple`. The property is only meaningful for events of type "Kamp of vakantie" (term id `0.57.0.0.0`). When omitted or `false`, it is hidden from the read model.

---

## JSON Schema Status

The vendor package `publiq/udb3-json-schemas` already has `overnight` defined in:
- `event-subEvent-patch.json` ✅ (PATCH /events/{id}/subEvents)
- `event-subEvent-put.json` ✅ (used by PUT /events and POST /events import)
- `event-subEvent.json` ✅ (response shape)

Still missing:
- `event-calendar-put.json` ❌ — needs `overnight: { type: boolean }` added to the subEvent item schema

---

## Central Configuration

The term id `0.57.0.0.0` must be defined as a single named constant. The right home is `EventTypeResolver`, which already references this id in `isOnlyAvailableUntilStartDate()`:

```php
// src/Event/EventTypeResolver.php
public const KAMP_OF_VAKANTIE_TERM_ID = '0.57.0.0.0';
```

Update `isOnlyAvailableUntilStartDate()` to use `self::KAMP_OF_VAKANTIE_TERM_ID` instead of a raw string. Every other reference throughout the codebase (validation, reset logic, tests) must use this constant — never a raw string.

---

## PR 1 — Domain Model: `overnight` on `SubEvent` and `SubEventUpdate`

**Goal:** Add the `overnight` field to the core value objects and calendar classes. No HTTP or projection concerns yet.

### Files to change

**`src/Model/ValueObject/Calendar/SubEvent.php`**
- Add `private bool $overnight = false`
- Add `withOvernight(bool $overnight): self`
- Add `isOvernight(): bool`

**`src/Model/ValueObject/Calendar/SubEventUpdate.php`**
- Add `private ?bool $overnight = null`
- Add `withOvernight(?bool $overnight): self`
- Add `getOvernight(): ?bool`

**`src/Event/EventTypeResolver.php`**
- Add `public const KAMP_OF_VAKANTIE_TERM_ID = '0.57.0.0.0'`
- Replace the hardcoded `'0.57.0.0.0'` string in `isOnlyAvailableUntilStartDate()` with `self::KAMP_OF_VAKANTIE_TERM_ID`

**`src/Event/Event.php`** — update `updateSubEvents()`
- When merging a `SubEventUpdate` into a `SubEvent`, apply `overnight` if it is not `null` in the update
- When `overnight` is `true` in any subEvent or subEventUpdate, verify the event's current type is `EventTypeResolver::KAMP_OF_VAKANTIE_TERM_ID`; throw a domain exception otherwise
- In `updateType()` (or wherever the type change is applied in the aggregate): if the new type id is no longer `EventTypeResolver::KAMP_OF_VAKANTIE_TERM_ID`, reset `overnight` to `false` on all subEvents before recording the `CalendarUpdated` event

**`src/Offer/Offer.php`** — update `updateCalendar()`
- After receiving a new `Calendar`, if any subEvent has `overnight = true`, verify the event's current type is `EventTypeResolver::KAMP_OF_VAKANTIE_TERM_ID`; throw a domain exception otherwise

### Tests
- `tests/Model/ValueObject/Calendar/SubEventTest.php` — getter and fluent setter
- `tests/Model/ValueObject/Calendar/SubEventUpdateTest.php` — getter and fluent setter
- `tests/Event/EventTest.php` — overnight validation, overnight preserved across unrelated updates, reset when term changes away from `0.57.0.0.0`

---

## PR 2 — Write Path: Deserialization, JSON Schema, and Validation

**Goal:** Accept `overnight` in request bodies and wire it through to the domain. Includes the one remaining schema change.

### Files to change

**`vendor/publiq/udb3-json-schemas` (upstream package)**
- `event-calendar-put.json` — add `overnight: { type: boolean }` to the subEvent item schema (mirrors the definition already in `event-subEvent-patch.json`)

**`src/Model/Serializer/ValueObject/Calendar/CalendarDenormalizer.php`**
- In `denormalizeSubEvent()`: read `overnight` from the raw data; call `->withOvernight(true)` when present and `true`

**`src/Model/Serializer/ValueObject/Calendar/SubEventUpdatesDenormalizer.php`**
- Read `overnight` from each patch item; call `->withOvernight()` when the key is present (pass `false` explicitly to allow clearing)

### Validation summary

| Rule | Where enforced |
|------|---------------|
| `overnight` only for `calendarType` single/multiple | JSON schema (`event-calendar-put.json` and `event-subEvent-patch.json` include it; place schemas do not) |
| `overnight` only when term `0.57.0.0.0` is present | Domain (`Event::updateCalendar`, `Event::updateSubEvents`) |
| Auto-reset when term removed | Domain (`Event::updateType`) |
| `overnight` must be boolean | JSON schema (`type: boolean`) |

### New test cases — `UpdateCalendarRequestHandlerTest::validEventDataProvider()`

| Case key | What it covers |
|----------|----------------|
| `single_with_overnight_true` | `calendarType=single`, one subEvent with `overnight: true` → command includes `SubEvent::createAvailable(…)->withOvernight(true)` |
| `multiple_with_overnight_on_one_subevent` | `calendarType=multiple`, two subEvents, only the first has `overnight: true` |
| `single_overnight_false_omitted_from_command` | `overnight: false` in request → command carries `withOvernight(false)` (or default false, same shape) |

### New test cases — `UpdateCalendarRequestHandlerTest::invalidEventDataProvider()`

| Case key | What it covers |
|----------|----------------|
| `single_overnight_wrong_type_string` | `overnight: "yes"` → JSON schema error `/subEvent/0/overnight` "The data (string) must match the type: boolean" |
| `single_overnight_wrong_type_integer` | `overnight: 1` → JSON schema error `/subEvent/0/overnight` "The data (integer) must match the type: boolean" |

### New test cases — `UpdateSubEventsRequestHandlerTest::validDataProvider()`

| Case key | What it covers |
|----------|----------------|
| `one_subEvent_with_overnight_true` | `id: 0, overnight: true` → command: `(new SubEventUpdate(0))->withOvernight(true)` |
| `one_subEvent_with_overnight_false` | `id: 0, overnight: false` → command: `(new SubEventUpdate(0))->withOvernight(false)` |

### New test cases — `UpdateSubEventsRequestHandlerTest::invalidDataProvider()`

| Case key | What it covers |
|----------|----------------|
| `one_subEvent_overnight_wrong_type_string` | `overnight: "yes"` → schema error `/0/overnight` "The data (string) must match the type: boolean" |
| `one_subEvent_overnight_wrong_type_integer` | `overnight: 1` → schema error `/0/overnight` "The data (integer) must match the type: boolean" |

### Tests
- `tests/Model/Serializer/ValueObject/Calendar/CalendarDenormalizerTest.php`
- `tests/Model/Serializer/ValueObject/Calendar/SubEventUpdatesDenormalizerTest.php`

---

## PR 3 — Read Model: Projection, Normalization, and Feature Tests

**Goal:** Include `overnight: true` in JSON-LD output only when set; omit it when `false`. Add end-to-end feature tests.

### Files to change

**`src/Model/Serializer/ValueObject/Calendar/SubEventNormalizer.php`**
- After serializing existing fields: `if ($subEvent->isOvernight()) { $data['overnight'] = true; }`

**`src/Model/Serializer/ValueObject/Calendar/SubEventDenormalizer.php`** (used during import/replay)
- Read `overnight` from stored JSON and call `->withOvernight(true)` if present and `true`

### Tests
- `tests/Model/Serializer/ValueObject/Calendar/SubEventNormalizerTest.php`
  - `overnight: true` → included in output
  - `overnight: false` (default) → absent from output
- `tests/Model/Serializer/ValueObject/Calendar/SubEventDenormalizerTest.php`
  - stored JSON with `overnight: true` → denormalized with `isOvernight() === true`
  - stored JSON without `overnight` → denormalized with `isOvernight() === false`
- `tests/Event/ReadModel/JSONLD/EventLDProjectorTest.php` — projection round-trip

### Feature tests — new file `features/event/sub-event-overnight.feature`

Each scenario follows the pattern established in `sub-event-childcare-time.feature`. Fixture JSON files go in `features/data/events/sub-event-overnight/`.

**Fixture: `event-kamp-single.json`**
```json
{
  "mainLanguage": "nl",
  "name": {"nl": "Zomerkamp"},
  "terms": [{"id": "0.57.0.0.0", "label": "Kamp of vakantie", "domain": "eventtype"}],
  "location": {"@id": "%{placeUrl}"},
  "calendarType": "single",
  "startDate": "2026-07-01T09:00:00+02:00",
  "endDate": "2026-07-05T17:00:00+02:00",
  "subEvent": [
    {
      "startDate": "2026-07-01T09:00:00+02:00",
      "endDate": "2026-07-05T17:00:00+02:00",
      "overnight": true
    }
  ]
}
```

**Scenarios to write:**

1. **Create `single` event with `overnight: true` — visible in GET**
   - POST with `event-kamp-single.json` (term `0.57.0.0.0`)
   - GET → `subEvent/0/overnight` should be `true`

2. **Create `multiple` event with `overnight: true` on one subEvent**
   - POST with multiple calendarType, two subEvents, first has `overnight: true`
   - GET → `subEvent/0/overnight` is `true`, `subEvent/1` has no `overnight`

3. **`overnight: false` is omitted from GET response**
   - POST with `overnight: false` explicitly set
   - GET → JSON response should not have `subEvent/0/overnight`

4. **`overnight` omitted entirely is also absent from GET response**
   - POST without `overnight`
   - GET → JSON response should not have `subEvent/0/overnight`

5. **Update `overnight` via PUT /calendar**
   - Create event without `overnight`
   - PUT `/calendar` with `overnight: true`
   - GET → `subEvent/0/overnight` is `true`

6. **Update `overnight` via PATCH /subEvents**
   - Create event with `overnight: true`
   - PATCH `/subEvents` with `id: 0, overnight: false`
   - GET → JSON response should not have `subEvent/0/overnight`

7. **`overnight` is preserved when omitted from PATCH**
   - Create event with `overnight: true`
   - PATCH `/subEvents` with `id: 0, status: {type: Available}` (no `overnight` key)
   - GET → `subEvent/0/overnight` still `true`

8. **`overnight` is reset when term `0.57.0.0.0` is removed via PUT /type**
   - Create event with `overnight: true`
   - PUT `/type/{termId}` replacing with a different type (e.g. `0.50.4.0.0`)
   - GET → JSON response should not have `subEvent/0/overnight`

9. **Invalid: `overnight: "yes"` rejected by schema on PUT /calendar**
   - PUT `/calendar` with `overnight: "yes"` on a subEvent
   - Response 400, `schemaErrors/0/jsonPointer` = `/subEvent/0/overnight`

10. **Invalid: `overnight: 1` rejected by schema on PATCH /subEvents**
    - PATCH `/subEvents` with `overnight: 1`
    - Response 400, `schemaErrors/0/jsonPointer` = `/0/overnight`

---

## PR Breakdown Summary

| PR | Scope | Key changes |
|----|-------|-------------|
| **PR 1** | Domain model | `SubEvent`, `SubEventUpdate`, `EventTypeResolver` constant, domain validation + reset in `Event.php` and `Offer.php` |
| **PR 2** | Write path | `event-calendar-put.json` schema, `CalendarDenormalizer`, `SubEventUpdatesDenormalizer`, new test cases in both handler tests |
| **PR 3** | Read model + feature tests | `SubEventNormalizer`, `SubEventDenormalizer` (replay/import), unit tests, `sub-event-overnight.feature` with fixture JSON |

Each PR is independently reviewable. PR 2 depends on PR 1; PR 3 depends on both.
