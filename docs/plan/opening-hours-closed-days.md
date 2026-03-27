# Plan: Add `openingHoursClosedDays` to Event Calendar

## Context

**Events only.** Events with `calendarType = periodic` or `permanent` have opening hours. There is currently no way to mark specific date ranges as closed (e.g., public holidays) while keeping the default opening hours intact. The JSON schema already defines `openingHoursClosedDays` in `event-calendar-put.json` and `event-openingHoursClosedDays.json`, but the PHP backend does not yet parse, store, validate, or return this field.

**Note:** Although the calendar domain model is shared between events and places, places do not have a schema definition for `openingHoursClosedDays` and will reject any such input per their schema validation. This feature is event-specific only.

## Affected Endpoints

- `PUT /events/{eventId}/calendar` (events only)
- `GET /events/{eventId}` (read model)
- `POST /events/{eventId}/copies` (uses same CalendarDenormalizer)

---

## Step 1 — Value Objects

| File | Purpose |
|---|---|
| `src/Model/ValueObject/Calendar/ClosedDayDescription.php` | String VO (non-empty, max 1000 chars). Follow `StatusReason` pattern. |
| `src/Model/ValueObject/Calendar/TranslatedClosedDayDescription.php` | Translatable wrapper. Follow `TranslatedStatusReason` pattern. |
| `src/Model/ValueObject/Calendar/ClosedDay.php` | Single closed date range with optional translated description. |
| `src/Model/ValueObject/Calendar/ClosedDays.php` | Sorted collection of `ClosedDay`, sorted by `startDate`. |
| `src/Model/ValueObject/Calendar/CalendarWithClosedDays.php` | Interface implemented by `PeriodicCalendar` and `PermanentCalendar`. |

## Step 2 — Domain Model Changes

- `src/Model/ValueObject/Calendar/PeriodicCalendar.php` — implement `CalendarWithClosedDays`, add `withClosedDays()` / `getClosedDays()`.
- `src/Model/ValueObject/Calendar/PermanentCalendar.php` — same.

## Step 3 — Serialization

| File | Change |
|---|---|
| `src/Model/Serializer/ValueObject/Calendar/ClosedDayNormalizer.php` | New. Emits `{startDate, endDate, description?}` with `Y-m-d` dates. |
| `src/Model/Serializer/ValueObject/Calendar/TranslatedClosedDayDescriptionDenormalizer.php` | New. Extends `TranslatedValueObjectDenormalizer`. |
| `src/Model/Serializer/ValueObject/Calendar/CalendarNormalizer.php` | Emit `openingHoursClosedDays` for `CalendarWithClosedDays`. |
| `src/Model/Serializer/ValueObject/Calendar/CalendarDenormalizer.php` | Parse `openingHoursClosedDays` for periodic/permanent. |
| `src/Model/Serializer/ValueObject/Calendar/CalendarSerializer.php` | Serialize/deserialize `openingHoursClosedDays` for event store. |

## Step 4 — Validation

| File | Change |
|---|---|
| `src/Http/Offer/ClosedDaysValidator.php` | New. Validates `startDate <= endDate` per entry; for periodic also validates entries fall within calendar date range. |
| `src/Http/Offer/UpdateCalendarValidatingRequestBodyParser.php` | Add `ClosedDaysValidator` to periodic and permanent branches. |
| `src/Http/Offer/CalendarValidatingRequestBodyParser.php` | Same. |

## Step 5 — Read-Model Purge

- `src/Offer/ReadModel/JSONLD/OfferUpdate.php` — add `openingHoursClosedDays` to the `unset()` list.

## Validation Rules (from spec)

- All exception dates must fall within the main `startDate`/`endDate` (periodic only; permanent has no bounds).
- `startDate` must be ≤ `endDate` per entry.
- Description is optional; max 1000 chars per language.
- `openingHoursClosedDays` takes precedence over `openingHoursAdjusted` (behavior note for consumers).
- GET responses return entries sorted by `startDate`.

## Events vs Places

**Events Only:** The `openingHoursClosedDays` field is defined only in the event schema (`event-calendar-put.json` and `event-openingHoursClosedDays.json`). Places do not have a corresponding schema definition and will reject this field as an additional property.

**Shared Domain Model:** Although the Calendar domain model classes (`PeriodicCalendar`, `PermanentCalendar`, `CalendarWithClosedDays` interface) are shared between events and places, the feature is accessible only to events via the API layer. The serializers and validators are also shared, but schema validation at the HTTP boundary ensures places cannot submit this field.

## Reusable Patterns

- `StatusReason` / `TranslatedStatusReason` / `TranslatedStatusReasonDenormalizer`
- `SubEvents` constructor sort → `ClosedDays` sort
- `DateRangeValidator` → `ClosedDaysValidator`