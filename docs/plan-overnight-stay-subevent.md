# Implementation Plan: `hasOvernightStay` boolean on subEvents

## Overview

Add an optional `hasOvernightStay` boolean property to subEvents for events with `calendarType = single` or `calendarType = multiple`. The property is only meaningful for events of type "Kamp of vakantie" (term id `0.57.0.0.0`). When omitted or `false`, it is hidden from the read model.

---

## JSON Schema Status

The vendor package `publiq/udb3-json-schemas` already has `hasOvernightStay` defined in:
- `event-subEvent-patch.json` ✅ (PATCH /events/{id}/subEvents)
- `event-subEvent-put.json` ✅ (used by PUT /events and POST /events import)
- `event-subEvent.json` ✅ (response shape)

Still missing:
- `event-calendar-put.json` ❌ — needs `hasOvernightStay: { type: boolean }` added to the subEvent item schema

---

## Central Configuration

The term id `0.57.0.0.0` must be defined as a single named constant. The right home is `EventTypeResolver`, which already references this id in `isOnlyAvailableUntilStartDate()`:

```php
// src/Event/EventTypeResolver.php
public const KAMP_OF_VAKANTIE_TERM_ID = '0.57.0.0.0';
```

Update `isOnlyAvailableUntilStartDate()` to use `self::KAMP_OF_VAKANTIE_TERM_ID` instead of a raw string. Every other reference throughout the codebase (validation, reset logic, tests) must use this constant — never a raw string.

---

## PR 1 — Domain Model: `hasOvernightStay` on `SubEvent` and `SubEventUpdate`

**Goal:** Add the `hasOvernightStay` field to the core value objects and calendar classes. No HTTP or projection concerns yet.

### Files to change

**`src/Model/ValueObject/Calendar/SubEvent.php`**
- Add `private bool $hasOvernightStay = false`
- Add `withHasOvernightStay(bool $hasOvernightStay): self`
- Add `hasOvernightStay(): bool`

**`src/Model/ValueObject/Calendar/SubEventUpdate.php`**
- Add `private ?bool $hasOvernightStay = null`
- Add `withHasOvernightStay(?bool $hasOvernightStay): self`
- Add `getHasOvernightStay(): ?bool`

**`src/Event/EventTypeResolver.php`**
- Add `public const KAMP_OF_VAKANTIE_TERM_ID = '0.57.0.0.0'`
- Replace the hardcoded `'0.57.0.0.0'` string in `isOnlyAvailableUntilStartDate()` with `self::KAMP_OF_VAKANTIE_TERM_ID`

**`src/Event/Event.php`** — update `updateSubEvents()`
- When merging a `SubEventUpdate` into a `SubEvent`, apply `hasOvernightStay` if it is not `null` in the update
- When `hasOvernightStay` is `true` in any subEvent or subEventUpdate, verify the event's current type is `EventTypeResolver::KAMP_OF_VAKANTIE_TERM_ID`; throw a domain exception otherwise
- In `updateType()` (or wherever the type change is applied in the aggregate): if the new type id is no longer `EventTypeResolver::KAMP_OF_VAKANTIE_TERM_ID`, reset `hasOvernightStay` to `false` on all subEvents before recording the `CalendarUpdated` event

**`src/Offer/Offer.php`** — update `updateCalendar()`
- After receiving a new `Calendar`, if any subEvent has `hasOvernightStay = true`, verify the event's current type is `EventTypeResolver::KAMP_OF_VAKANTIE_TERM_ID`; throw a domain exception otherwise

### Tests
- `tests/Model/ValueObject/Calendar/SubEventTest.php` — getter and fluent setter
- `tests/Model/ValueObject/Calendar/SubEventUpdateTest.php` — getter and fluent setter
- `tests/Event/EventTest.php` — overnight stay validation, overnight stay preserved across unrelated updates, reset when term changes away from `0.57.0.0.0`

---

## PR 2 — Write Path: Deserialization, JSON Schema, and Validation

**Goal:** Accept `hasOvernightStay` in request bodies and wire it through to the domain. Includes the one remaining schema change.

### Files to change

**`vendor/publiq/udb3-json-schemas` (upstream package)**
- `event-calendar-put.json` — add `hasOvernightStay: { type: boolean }` to the subEvent item schema (mirrors the definition already in `event-subEvent-patch.json`)

**`src/Model/Serializer/ValueObject/Calendar/CalendarDenormalizer.php`**
- In `denormalizeSubEvent()`: read `hasOvernightStay` from the raw data; call `->withHasOvernightStay(true)` when present and `true`

**`src/Model/Serializer/ValueObject/Calendar/SubEventUpdatesDenormalizer.php`**
- Read `hasOvernightStay` from each patch item; call `->withHasOvernightStay()` when the key is present (pass `false` explicitly to allow clearing)

### Validation summary

| Rule | Where enforced |
|------|---------------|
| `hasOvernightStay` only for `calendarType` single/multiple | JSON schema (`event-calendar-put.json` and `event-subEvent-patch.json` include it; place schemas do not) |
| `hasOvernightStay` only when term `0.57.0.0.0` is present | Domain (`Event::updateCalendar`, `Event::updateSubEvents`) |
| Auto-reset when term removed | Domain (`Event::updateType`) |
| `hasOvernightStay` must be boolean | JSON schema (`type: boolean`) |

### New test cases — `UpdateCalendarRequestHandlerTest::validEventDataProvider()`

| Case key | What it covers |
|----------|----------------|
| `single_with_overnight_stay_true` | `calendarType=single`, one subEvent with `hasOvernightStay: true` → command includes `SubEvent::createAvailable(…)->withHasOvernightStay(true)` |
| `multiple_with_overnight_stay_on_one_subevent` | `calendarType=multiple`, two subEvents, only the first has `hasOvernightStay: true` |
| `single_overnight_stay_false_omitted_from_command` | `hasOvernightStay: false` in request → command carries `withHasOvernightStay(false)` (or default false, same shape) |

### New test cases — `UpdateCalendarRequestHandlerTest::invalidEventDataProvider()`

| Case key | What it covers |
|----------|----------------|
| `single_overnight_stay_wrong_type_string` | `hasOvernightStay: "yes"` → JSON schema error `/subEvent/0/hasOvernightStay` "The data (string) must match the type: boolean" |
| `single_overnight_stay_wrong_type_integer` | `hasOvernightStay: 1` → JSON schema error `/subEvent/0/hasOvernightStay` "The data (integer) must match the type: boolean" |

### New test cases — `UpdateSubEventsRequestHandlerTest::validDataProvider()`

| Case key | What it covers |
|----------|----------------|
| `one_subEvent_with_overnight_stay_true` | `id: 0, hasOvernightStay: true` → command: `(new SubEventUpdate(0))->withHasOvernightStay(true)` |
| `one_subEvent_with_overnight_stay_false` | `id: 0, hasOvernightStay: false` → command: `(new SubEventUpdate(0))->withHasOvernightStay(false)` |

### New test cases — `UpdateSubEventsRequestHandlerTest::invalidDataProvider()`

| Case key | What it covers |
|----------|----------------|
| `one_subEvent_overnight_stay_wrong_type_string` | `hasOvernightStay: "yes"` → schema error `/0/hasOvernightStay` "The data (string) must match the type: boolean" |
| `one_subEvent_overnight_stay_wrong_type_integer` | `hasOvernightStay: 1` → schema error `/0/hasOvernightStay` "The data (integer) must match the type: boolean" |

### Tests
- `tests/Model/Serializer/ValueObject/Calendar/CalendarDenormalizerTest.php`
- `tests/Model/Serializer/ValueObject/Calendar/SubEventUpdatesDenormalizerTest.php`

---

## PR 3 — Read Model: Projection, Normalization, and Feature Tests

**Goal:** Include `hasOvernightStay: true` in JSON-LD output only when set; omit it when `false`. Add end-to-end feature tests.

### Files to change

**`src/Model/Serializer/ValueObject/Calendar/SubEventNormalizer.php`**
- After serializing existing fields: `if ($subEvent->hasOvernightStay()) { $data['hasOvernightStay'] = true; }`

**`src/Model/Serializer/ValueObject/Calendar/SubEventDenormalizer.php`** (used during import/replay)
- Read `hasOvernightStay` from stored JSON and call `->withHasOvernightStay(true)` if present and `true`

### Tests
- `tests/Model/Serializer/ValueObject/Calendar/SubEventNormalizerTest.php`
  - `hasOvernightStay: true` → included in output
  - `hasOvernightStay: false` (default) → absent from output
- `tests/Model/Serializer/ValueObject/Calendar/SubEventDenormalizerTest.php`
  - stored JSON with `hasOvernightStay: true` → denormalized with `hasOvernightStay() === true`
  - stored JSON without `hasOvernightStay` → denormalized with `hasOvernightStay() === false`
- `tests/Event/ReadModel/JSONLD/EventLDProjectorTest.php` — projection round-trip

### Feature tests — new file `features/event/sub-event-overnight-stay.feature`

Each scenario follows the pattern established in `sub-event-childcare-time.feature`. Fixture JSON files go in `features/data/events/sub-event-overnight-stay/`.

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
      "hasOvernightStay": true
    }
  ]
}
```

**Scenarios to write:**

1. **Create `single` event with `hasOvernightStay: true` — visible in GET**
   - POST with `event-kamp-single.json` (term `0.57.0.0.0`)
   - GET → `subEvent/0/hasOvernightStay` should be `true`

2. **Create `multiple` event with `hasOvernightStay: true` on one subEvent**
   - POST with multiple calendarType, two subEvents, first has `hasOvernightStay: true`
   - GET → `subEvent/0/hasOvernightStay` is `true`, `subEvent/1` has no `hasOvernightStay`

3. **`hasOvernightStay: false` is omitted from GET response**
   - POST with `hasOvernightStay: false` explicitly set
   - GET → JSON response should not have `subEvent/0/hasOvernightStay`

4. **`hasOvernightStay` omitted entirely is also absent from GET response**
   - POST without `hasOvernightStay`
   - GET → JSON response should not have `subEvent/0/hasOvernightStay`

5. **Update `hasOvernightStay` via PUT /calendar**
   - Create event without `hasOvernightStay`
   - PUT `/calendar` with `hasOvernightStay: true`
   - GET → `subEvent/0/hasOvernightStay` is `true`

6. **Update `hasOvernightStay` via PATCH /subEvents**
   - Create event with `hasOvernightStay: true`
   - PATCH `/subEvents` with `id: 0, hasOvernightStay: false`
   - GET → JSON response should not have `subEvent/0/hasOvernightStay`

7. **`hasOvernightStay` is preserved when omitted from PATCH**
   - Create event with `hasOvernightStay: true`
   - PATCH `/subEvents` with `id: 0, status: {type: Available}` (no `hasOvernightStay` key)
   - GET → `subEvent/0/hasOvernightStay` still `true`

8. **`hasOvernightStay` is reset when term `0.57.0.0.0` is removed via PUT /type**
   - Create event with `hasOvernightStay: true`
   - PUT `/type/{termId}` replacing with a different type (e.g. `0.50.4.0.0`)
   - GET → JSON response should not have `subEvent/0/hasOvernightStay`

9. **Invalid: `hasOvernightStay: "yes"` rejected by schema on PUT /calendar**
   - PUT `/calendar` with `hasOvernightStay: "yes"` on a subEvent
   - Response 400, `schemaErrors/0/jsonPointer` = `/subEvent/0/hasOvernightStay`

10. **Invalid: `hasOvernightStay: 1` rejected by schema on PATCH /subEvents**
    - PATCH `/subEvents` with `hasOvernightStay: 1`
    - Response 400, `schemaErrors/0/jsonPointer` = `/0/hasOvernightStay`

---

## PR Breakdown Summary

| PR | Scope | Key changes |
|----|-------|-------------|
| **PR 1** | Domain model | `SubEvent`, `SubEventUpdate`, `EventTypeResolver` constant, domain validation + reset in `Event.php` and `Offer.php` |
| **PR 2** | Write path | `event-calendar-put.json` schema, `CalendarDenormalizer`, `SubEventUpdatesDenormalizer`, new test cases in both handler tests |
| **PR 3** | Read model + feature tests | `SubEventNormalizer`, `SubEventDenormalizer` (replay/import), unit tests, `sub-event-overnight-stay.feature` with fixture JSON |

Each PR is independently reviewable. PR 2 depends on PR 1; PR 3 depends on both.
