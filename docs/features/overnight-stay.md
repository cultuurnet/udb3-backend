# Overnight stay

An occurrence of an Event can have an overnight stay. Only a camp or vacation can, so the flag is
meaningless on any other kind of event.

## Data model

An overnight stay belongs to an **occurrence**, not to the event: an event with several occurrences
can have one on some of them and not on others.

| Concept                | Where                                                                   |
|------------------------|-------------------------------------------------------------------------|
| Value object           | `src/Model/ValueObject/Calendar/SubEvent.php` — `hasOvernightStay()`     |
| Update value object    | `src/Model/ValueObject/Calendar/SubEventUpdate.php`                      |
| Normalizer             | `src/Model/Serializer/ValueObject/Calendar/SubEventNormalizer.php`       |
| Event type rule        | `src/Event/EventTypeResolver.php` — `isOvernightStayAllowed()`           |

Only the **camp or vacation** event type (`0.57.0.0.0`, `EventTypeResolver::CAMP_OR_VACATION_TERM_ID`)
can have an overnight stay.

### JSON structure

`SubEventNormalizer` writes the property **only when it is true**. An occurrence without an
overnight stay carries no `hasOvernightStay` at all:

```json
"subEvent": [
  {"startDate": "...", "endDate": "...", "hasOvernightStay": true},
  {"startDate": "...", "endDate": "..."}
]
```

This is the reason the export has three states rather than two: an absent flag says nothing about
whether the event could ever have had one.

## API endpoints

| Method | Endpoint                          | Handler                          |
|--------|-----------------------------------|----------------------------------|
| PATCH  | `/events/{eventId}/sub-events/`   | `UpdateSubEventsRequestHandler`  |
| PUT    | `/events/{offerId}/calendar/`     | `UpdateCalendarRequestHandler`   |

Schemas: `event-subEvent.json`, `event-subEvent-patch.json`, `event-subEvent-put.json` in
`publiq/udb3-json-schemas`.

## Export

An overnight stay is exportable by adding `hasOvernightStay` to the `include` list of an event
export.

| Method | Endpoint                | Format | Output                    |
|--------|-------------------------|--------|---------------------------|
| POST   | `/events/export/ooxml/` | Excel  | A `met overnachting` column |
| POST   | `/events/export/json/`  | JSON   | A `hasOvernightStay` flag |

The PDF export does not include it.

```json
{"email": "export@publiq.be", "query": "...", "include": ["name", "hasOvernightStay"]}
```

**Rule:** `src/EventExport/OvernightStay.php` — `forEvent()`

The occurrences are summarised into a single answer for the whole event, which has **three** states.
The third one exists because an event type that can never have an overnight stay must not be
reported as simply not having one.

| Event                                             | Excel           | JSON                          |
|---------------------------------------------------|-----------------|-------------------------------|
| Camp or vacation, at least one occurrence with one | `ja`            | `"hasOvernightStay": true`    |
| Camp or vacation, no occurrence with one           | `nee`           | `"hasOvernightStay": false`   |
| Any other event type                               | *(empty cell)*  | *(property absent)*           |

An integrator reading the JSON export should therefore treat an absent `hasOvernightStay` as "not
applicable to this event type", not as `false`, and not as an older export that predates the
property.
