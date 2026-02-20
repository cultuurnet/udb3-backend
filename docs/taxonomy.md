# Taxonomy

Terms are fetched from the UiTdatabank taxonomy API: `https://taxonomy.uitdatabank.be/terms`

## Term structure

Each term in the API response has the following shape:

```json
{
  "id": "term_id",
  "domain": "eventtype|theme|facility",
  "name": { 
    "nl": "term_label_in_dutch",
    "fr": "term_label_in_french",
    "de": "term_label_in_german",
    "en": "term_label_in_english"
  },
  "scope": ["events", "places"]
}
```

- **id** - unique id of the taxonomy term
- **domain** — one of `eventtype`, `theme`, or `facility`
- **name** - Human readable name of the term in several language.
- **scope** — which offer types the term applies to: `events`, `places`, or both

## Permissions

| Domain      | Who can set it |
|-------------|----------------|
| `eventtype` | Regular users  |
| `theme`     | Regular users  |
| `facility`  | Prea           |

## API client

`TaxonomyApiClient` (interface) exposes typed methods per domain/scope combination:

| Method                 | Domain      | Scope     |
|------------------------|-------------|-----------|
| `getEventTypes()`      | `eventtype` | `events`  |
| `getEventThemes()`     | `theme`     | `events`  |
| `getEventFacilities()` | `facility`  | `events`  |
| `getPlaceTypes()`      | `eventtype` | `places`  |
| `getPlaceFacilities()` | `facility`  | `places`  |
| `getNativeTerms()`     | —           | raw array |

`JsonTaxonomyApiClient` fetches all terms once on construction and filters in memory.
`CachedTaxonomyApiClient` wraps it with a 24-hour Symfony cache.

## Value objects

- `Category` — id, label, domain
- `CategoryID`, `CategoryLabel`, `CategoryDomain`
- `Categories` — typed collection of `Category`, deduplicates on construction

## Resolvers

Scope-specific resolvers implement `TypeResolverInterface`, `ThemeResolverInterface`, or
`OfferFacilityResolverInterface` and resolve a term ID string to a `Category`:

| Class                   | Domain      | Scope    |
|-------------------------|-------------|----------|
| `EventTypeResolver`     | `eventtype` | `events` |
| `EventThemeResolver`    | `theme`     | `events` |
| `EventFacilityResolver` | `facility`  | `events` |
| `PlaceTypeResolver`     | `eventtype` | `places` |
| `PlaceFacilityResolver` | `facility`  | `places` |

`CategoryResolverInterface` resolves a `CategoryID` across all domains for a given offer type:

- `EventCategoryResolver` — delegates to `EventTypeResolver`, `EventThemeResolver`, `EventFacilityResolver`
- `PlaceCategoryResolver` — delegates to `PlaceTypeResolver`, `PlaceFacilityResolver` (places have no themes)

## TermRepository

`TermRepository` wraps the raw `getNativeTerms()` array and provides `getById(string $id): Category`.
Used for legacy lookups where domain/scope context is not available.