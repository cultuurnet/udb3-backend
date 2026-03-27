# Plan: Add `openingHoursAdjusted` to Event Calendar

## Context

**Events only.** Events with `calendarType = periodic` or `permanent` can define temporary custom opening hours that override the default schedule for a given date range. Each entry has a `startDate`, `endDate`, optional translatable `description`, and an `openingHours` array reusing the existing `OpeningHour` value object.

This is closely modelled on `openingHoursClosedDays` (see `docs/plan/opening-hours-closed-days.md`). The key difference is that each entry carries its own `openingHours` schedule instead of simply marking days as closed.

**Precedence rule:** `openingHoursClosedDays` always takes precedence over `openingHoursAdjusted` (consumer-side behaviour; no special backend enforcement needed beyond normal storage and serialization).

---

## Affected Endpoints

- `PUT /events/{eventId}/calendar` (events only)
- `GET /events/{eventId}` (read model)
- `POST /events/{eventId}/copies` (uses same `CalendarDenormalizer`)

---

## Step 1 — Value Objects

Reuse existing value objects as much as possible.

| File | Purpose |
|---|---|
| `src/Model/ValueObject/Calendar/AdjustedOpeningHoursDescription.php` | String VO (non-empty, max 1000 chars). Follow `ClosedDayDescription` pattern (traits: `IsString`, `IsNotEmpty`, `HasMaxLength`). |
| `src/Model/ValueObject/Calendar/TranslatedAdjustedOpeningHoursDescription.php` | Translatable wrapper. Extend `TranslatedValueObject`, same pattern as `TranslatedClosedDayDescription`. |
| `src/Model/ValueObject/Calendar/AdjustedOpeningHours.php` | Single adjusted entry: `startDate`, `endDate`, `OpeningHours`, optional `TranslatedAdjustedOpeningHoursDescription`. Validates `startDate <= endDate` in constructor (same guard as `ClosedDay`). |
| `src/Model/ValueObject/Calendar/AdjustedOpeningHoursCollection.php` | Sorted collection of `AdjustedOpeningHours`, sorted by `startDate`. Follow `ClosedDays` constructor sort pattern. |
| `src/Model/ValueObject/Calendar/CalendarWithAdjustedOpeningHours.php` | Interface with `getAdjustedOpeningHours(): AdjustedOpeningHoursCollection` and `withAdjustedOpeningHours(AdjustedOpeningHoursCollection): static`. Follow `CalendarWithClosedDays` pattern. |

**Reuse:** `OpeningHours` / `OpeningHour` from `src/Model/ValueObject/Calendar/OpeningHours/` are reused as-is inside `AdjustedOpeningHours`.

---

## Step 2 — Domain Model Changes

- `src/Model/ValueObject/Calendar/PeriodicCalendar.php`
  - Add `implements CalendarWithAdjustedOpeningHours`
  - Add private `AdjustedOpeningHoursCollection $adjustedOpeningHours` (default: empty collection)
  - Add `getAdjustedOpeningHours()` / `withAdjustedOpeningHours()` following `withClosedDays()` pattern

- `src/Model/ValueObject/Calendar/PermanentCalendar.php`
  - Same as above.

---

## Step 3 — Serialization

All new serializers/denormalizers follow the exact same patterns as their `ClosedDay*` counterparts.

### New files

| File | Purpose |
|---|---|
| `src/Model/Serializer/ValueObject/Calendar/AdjustedOpeningHoursNormalizer.php` | Normalizes one `AdjustedOpeningHours` to `{startDate, endDate, description?, openingHours[]}`. Reuses `OpeningHourNormalizer` for the hours array. |
| `src/Model/Serializer/ValueObject/Calendar/AdjustedOpeningHoursDenormalizer.php` | Denormalizes array → `AdjustedOpeningHoursCollection`. Reuses `OpeningHourDenormalizer` and `TranslatedAdjustedOpeningHoursDescriptionDenormalizer`. |
| `src/Model/Serializer/ValueObject/Calendar/TranslatedAdjustedOpeningHoursDescriptionDenormalizer.php` | Extends `TranslatedValueObjectDenormalizer`. Converts `{nl: "...", fr: "..."}` → `TranslatedAdjustedOpeningHoursDescription`. |

### Modified files

| File | Change |
|---|---|
| `src/Model/Serializer/ValueObject/Calendar/CalendarDenormalizer.php` | In `periodic` and `permanent` cases: if `openingHoursAdjusted` is set, call `(new AdjustedOpeningHoursDenormalizer())->denormalize(...)` and `$calendar->withAdjustedOpeningHours(...)`. Follow the `openingHoursClosedDays` block. |
| `src/Model/Serializer/ValueObject/Calendar/CalendarNormalizer.php` | After the `openingHoursClosedDays` block: if `$calendar instanceof CalendarWithAdjustedOpeningHours` and not empty, emit `openingHoursAdjusted` array using `AdjustedOpeningHoursNormalizer`. |
| `src/Model/Serializer/ValueObject/Calendar/CalendarSerializer.php` | In `serialize()`: serialize `openingHoursAdjusted` key (same guard as `openingHoursClosedDays`). In `deserialize()`: for `periodic` and `permanent`, if `openingHoursAdjusted` key present, denormalize and call `withAdjustedOpeningHours()`. |

---

## Step 4 — Validation

### New file

`src/Http/Offer/AdjustedOpeningHoursValidator.php`

Validates the `openingHoursAdjusted` array. Returns `SchemaError[]`.

Rules:
1. `startDate <= endDate` per entry — delegate to `AdjustedOpeningHours` constructor via try/catch (same as `ClosedDaysValidator`).
2. For `periodic` calendars: each entry's `startDate`/`endDate` must fall within the calendar's `startDate`/`endDate`.
3. No overlap between entries: sort by `startDate`, then check that each `startDate >= previous endDate`.

```php
// Overlap detection (after sorting):
for ($i = 1; $i < count($entries); $i++) {
    if ($entries[$i]->startDate <= $entries[$i-1]->endDate) {
        // emit SchemaError for /openingHoursAdjusted/{i}/startDate
    }
}
```

### Modified files

| File | Change |
|---|---|
| `src/Http/Offer/UpdateCalendarValidatingRequestBodyParser.php` | In `periodic` and `permanent` cases: add `(new AdjustedOpeningHoursValidator())->validate($data)` after `ClosedDaysValidator`. |
| `src/Http/Offer/CalendarValidatingRequestBodyParser.php` | Same — if this parser also handles periodic/permanent, add the same validator. |

---

## Step 5 — JSON Schema (vendor)

The JSON schema lives in `vendor/publiq/udb3-json-schemas/`. These files are managed externally and must be updated in the `udb3-json-schemas` package before being pulled into this repo via composer.

New schema file needed: `event-openingHoursAdjusted.json`

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "array",
  "items": {
    "type": "object",
    "required": ["startDate", "endDate", "openingHours"],
    "properties": {
      "startDate": { "type": "string", "format": "date" },
      "endDate":   { "type": "string", "format": "date" },
      "description": {
        "type": "object",
        "properties": {
          "nl": { "type": "string", "maxLength": 1000 },
          "fr": { "type": "string", "maxLength": 1000 },
          "de": { "type": "string", "maxLength": 1000 },
          "en": { "type": "string", "maxLength": 1000 }
        },
        "additionalProperties": false
      },
      "openingHours": { "$ref": "opening-hours.json" }
    },
    "additionalProperties": false
  }
}
```

`event-calendar-put.json` must be updated to reference `event-openingHoursAdjusted.json` for `periodic` and `permanent` calendar type branches — same as `openingHoursClosedDays`.

Places (`place-calendar-put.json`) must **not** include this field.

---

## Step 6 — Read-Model Purge

- `src/Offer/ReadModel/JSONLD/OfferUpdate.php` — add `openingHoursAdjusted` to the `unset()` list so stale data is cleared when a calendar is updated.

---

## Step 7 — Tests

### Unit: `AdjustedOpeningHoursValidatorTest.php`

Follow `ClosedDaysValidatorTest` pattern. Cover:

| Case | Expected |
|---|---|
| Valid periodic entries within calendar range | No errors |
| Valid permanent entries (any dates) | No errors |
| `startDate > endDate` | Error on `endDate` |
| Entries outside periodic range (start before) | Error on `startDate` |
| Entries outside periodic range (end after) | Error on `endDate` |
| Overlapping entries | Error on `startDate` of second entry |

### Unit: `UpdateCalendarRequestHandlerTest.php` — data provider additions

Add invalid cases as `@dataProvider` entries to the existing `invalidEventDataProvider` (or a new `invalidAdjustedOpeningHoursDataProvider`):

| Case | JSON | Expected error path |
|---|---|---|
| Missing `startDate` | entry without `startDate` | `/openingHoursAdjusted/0/startDate` |
| Missing `endDate` | entry without `endDate` | `/openingHoursAdjusted/0/endDate` |
| Missing `openingHours` | entry without `openingHours` | `/openingHoursAdjusted/0/openingHours` |
| `startDate > endDate` | start after end | `/openingHoursAdjusted/0/endDate` |
| Entry before periodic start | startDate before calendar start | `/openingHoursAdjusted/0/startDate` |
| Entry after periodic end | endDate after calendar end | `/openingHoursAdjusted/0/endDate` |
| Overlapping entries | two entries with overlapping ranges | `/openingHoursAdjusted/1/startDate` |
| Description too long (>1000 chars) | description.nl with 1001 chars | `/openingHoursAdjusted/0/description/nl` |
| Invalid time format | `opens: "25:00"` | `/openingHoursAdjusted/0/openingHours/0/opens` |

### Feature test: `features/place/opening-hours-adjusted.feature`

Mirror `opening-hours-closed-days.feature`. Verify that **places ignore** `openingHoursAdjusted` (field not returned in GET).

### Feature test: `features/event/opening-hours-adjusted.feature`

Cover the happy path for events:

1. Create a periodic event with `openingHoursAdjusted` → GET returns field sorted by `startDate`.
2. Create a permanent event with `openingHoursAdjusted` → GET returns field.
3. Update calendar (PUT) with `openingHoursAdjusted` → GET returns updated field.
4. Update calendar without `openingHoursAdjusted` → GET does not return the field (field is cleared).
5. `openingHoursAdjusted` with optional `description` including translations → GET returns all language translations.
6. `openingHoursAdjusted` with childcare on `openingHours` entries (reuse existing childcare DTOs) → GET returns childcare times.

---

## Validation Rules Summary

| Rule | Enforcement layer |
|---|---|
| `startDate <= endDate` per entry | `AdjustedOpeningHours` constructor + `AdjustedOpeningHoursValidator` |
| Entries within periodic `startDate`/`endDate` | `AdjustedOpeningHoursValidator` |
| No overlap between entries | `AdjustedOpeningHoursValidator` |
| `openingHours` required per entry | JSON schema |
| `description` optional, max 1000 chars per language | JSON schema + `AdjustedOpeningHoursDescription` VO |
| `openingHoursClosedDays` takes precedence | Consumer behaviour only; no enforcement needed |
| GET response sorted by `startDate` | `AdjustedOpeningHoursCollection` constructor sort |

---

## Reusable Patterns / Code References

| Pattern | Source |
|---|---|
| Description VO | `ClosedDayDescription` → `AdjustedOpeningHoursDescription` |
| Translated description VO | `TranslatedClosedDayDescription` → `TranslatedAdjustedOpeningHoursDescription` |
| Collection with sort | `ClosedDays` → `AdjustedOpeningHoursCollection` |
| Normalizer | `ClosedDayNormalizer` → `AdjustedOpeningHoursNormalizer` (add `openingHours` field) |
| Denormalizer | `ClosedDaysDenormalizer` → `AdjustedOpeningHoursDenormalizer` (add `OpeningHourDenormalizer` loop) |
| HTTP validator | `ClosedDaysValidator` → `AdjustedOpeningHoursValidator` (add overlap check) |
| CalendarDenormalizer hooks | `openingHoursClosedDays` block → `openingHoursAdjusted` block |
| CalendarNormalizer hooks | `CalendarWithClosedDays` block → `CalendarWithAdjustedOpeningHours` block |
| CalendarSerializer hooks | `openingHoursClosedDays` in serialize/deserialize → same for `openingHoursAdjusted` |
| Feature test (places ignore) | `features/place/opening-hours-closed-days.feature` |

---

## Events vs Places

**Events only:** `openingHoursAdjusted` will be defined only in `event-calendar-put.json`. Places will reject it as an additional property via their schema.

**Shared domain model:** `PeriodicCalendar` and `PermanentCalendar` implement `CalendarWithAdjustedOpeningHours`, but the API layer only exposes the field to events via schema validation.
