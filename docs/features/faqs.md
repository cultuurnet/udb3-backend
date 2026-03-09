# Faqs

FAQs are an optional list of question/answer pairs in various languages on Events. Each FAQ item has an internal `id` that is not exposed in the projection.

## Data model

Each FAQ item has:
- `id` — UUID, auto-generated on creation but not exposed with the API and only stored inside the event store
- One or more language translations, each with a `question` (max 255 chars) and an `answer` (max 5000 chars)

### JSON structure

```json
[
  {
    "nl": {
      "question": "Hoe geraak ik er?",
      "answer": "Met de bus."
    },
    "en": {
      "question": "How do I get there?",
      "answer": "By bus."
    }
  }
]
```

## API endpoint

| Method | Endpoint                  | Handler                |
|--------|---------------------------|------------------------|
| PUT    | `/events/{eventId}/faqs/` | `FaqsRequestHandler`   |

The endpoint **replaces** the entire FAQ list. Returns `204 No Content` on success.

### Creating FAQ items

```json
[{"nl": {"question": "Hoe geraak ik er?", "answer": "Met de bus."}}]
```

### Updating existing FAQ items

```json
[{"nl": {"question": "Hoe geraak ik er?", "answer": "Met de trein."}}]
```

### Removing all FAQ items

Send an empty array.

```json
[]
```

## Validation

| Constraint                               | HTTP status | Error                                                    |
|------------------------------------------|-------------|----------------------------------------------------------|
| Missing `question` or `answer`           | 400         | `schemaErrors` with the missing field                    |
| No valid language key on item            | 400         | `schemaErrors` listing missing required languages        |
| More than 30 items                       | 400         | `Array should have at most 30 items, {n} found`          |

## Value objects

All value objects live in `src/Model/ValueObject/Faq/`.

| Class           | Description                                          |
|-----------------|------------------------------------------------------|
| `Faq`           | A single FAQ in one language (`id`, `question`, `answer`) |
| `TranslatedFaq` | A `Faq` with all its language translations           |
| `Faqs`          | Ordered collection of `TranslatedFaq` items          |
| `Question`      | Non-empty string, max 255 chars                      |
| `Answer`        | Non-empty string, max 5000 chars                     |

## Aggregate

`src/Event/Event.php` — `updateFaqs(Faqs $faqs): void`

Emits `FaqsUpdated` unless the new list is identical to the current one (`sameAs()` check).

## Command / handler

| Class              | Location                                              |
|--------------------|-------------------------------------------------------|
| `UpdateFaqs`       | `src/Event/Commands/UpdateFaqs.php`                   |
| `UpdateFaqsHandler`| `src/Event/CommandHandlers/UpdateFaqsHandler.php`     |

## Domain event

`src/Event/Events/FaqsUpdated.php`

Serialized using `FaqsNormalizer` / deserialized using `FaqsDenormalizer`. Stored format per item:

```json
{"nl": {"question": "...", "answer": "..."}}
```

## Read model

**Projector:** `src/Event/ReadModel/JSONLD/EventLDProjector.php` — `applyFaqsUpdated()`

- Sets `faqs` on the JSON-LD document when the list is non-empty
- Removes `faqs` entirely when the list is empty
