# Overnight stay

An occurrence of an Event can have an overnight stay. Only a camp or vacation can, so the flag is
meaningless on any other kind of event.

## Data model

An overnight stay belongs to an **occurrence**, not to the event: an event with several occurrences
can have one on some of them and not on others.

| Concept                | Where                                                                   |
|------------------------|-------------------------------------------------------------------------|
| Value object           | `src/Model/ValueObject/Calendar/SubEvent.php` — `getHasOvernightStay()`  |
| Update value object    | `src/Model/ValueObject/Calendar/SubEventUpdate.php`                      |
| Normalizer             | `src/Model/Serializer/ValueObject/Calendar/SubEventNormalizer.php`       |
| Event type rule        | `src/Event/EventTypeResolver.php` — `isOvernightStayAllowed()`           |

Only the **camp or vacation** event type (`0.57.0.0.0`, `EventTypeResolver::CAMP_OR_VACATION_TERM_ID`)
can have an overnight stay.

### JSON structure

`SubEventNormalizer` writes the property **only when it has a value**, so an occurrence has three
states, see https://jira.publiq.be/browse/III-7524:

| Request                     | Occurrence | Projection                  |
|-----------------------------|------------|-----------------------------|
| absent                      | `null`     | *(property absent)*         |
| `"hasOvernightStay": false` | `false`    | `"hasOvernightStay": false` |
| `"hasOvernightStay": true`  | `true`     | `"hasOvernightStay": true`  |

`null` means nobody ever filled it in, `false` means the occurrence really has none.

```json
"subEvent": [
  {"startDate": "...", "endDate": "...", "hasOvernightStay": true},
  {"startDate": "...", "endDate": "...", "hasOvernightStay": false},
  {"startDate": "...", "endDate": "..."}
]
```

A PATCH that omits the property keeps the current value. A PUT of the whole calendar clears it, the
same way it resets `status`, `bookingAvailability` and `childcare`.

On any other event type `true` is refused with a 400 and `false` is accepted but reset to `null`, so
those events never carry a value.

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

**Rule:** `src/EventExport/OvernightStayResolver.php` — `forEvent()`

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
