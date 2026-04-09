# Schools API Import - Analysis

## Executive Summary

We want to import all Flemish schools from the government's Instelling API into UDB3 as places, and keep them in sync over time. The data team handles the entire import flow: fetching schools, preprocessing, duplicate detection via BigQuery, and calling the UDB3 API to create/update places. UDB3's role is to expose its existing Place API.

The data team is the right owner of this import because they have the existing infrastructure and expertise for duplicate detection (BigQuery matching, Google geolocation API, cluster analysis) and the data knowledge needed to clean up and normalize school data before it enters UDB3.

### Terminology

The government API uses the term "instelling" (institution), which covers schools but also other structures like CLBs, internaten, and scholengemeenschappen. In this document we use **"school"** when talking about what we import and manage. We use **"instelling"** only when referring to the API's field names (e.g. `instelling_naam_volledig`) or endpoints (e.g. `GET /instelling`).

---

## 1. Architecture: Two Separate Systems

Both systems are internal to publiq, but they operate independently with a clear boundary:

**Data team (Python/BigQuery)**:
- Fetches schools from the Instelling API
- Preprocesses and transforms the data
- Runs duplicate/cluster analysis against existing UDB3 places
- Calls the UDB3 API over HTTP to create and update places
- Tracks which schools have been imported (see chapter 7)

**UDB3**:
- Exposes the `POST /places/` and `PUT /places/{placeId}/` API endpoints
- Handles duplicate prevention at creation time (see chapter 3)
- Stores places as event-sourced aggregates
- Keeps track of which places are imported schools (see chapter 7 for options)

---

## 2. Cluster Processing

UDB3 has an existing system for managing duplicate places:

1. **External detection**: The data team analyzes places in BigQuery, using name matching and the Google geolocation API. When locations match, places are grouped into a cluster.
2. **Intermediate table**: Cluster data is uploaded to the `duplicate_places_import` table (columns: `cluster_id`, `place_uuid`).
3. **Import sync**: The command `place:duplicate-places:import` syncs the intermediate table to `duplicate_places`.
4. **Canonical selection**: The command `place:process-duplicates` determines which place in each cluster is the "canonical" (primary) one, using this priority:
   - A place with a configured "canonical label" (highest priority)
   - The place with the most events
   - The oldest place (fallback)
5. **Event relocation**: All events at duplicate (non-canonical) places are automatically moved to the canonical place.

This is a **manual, multi-step workflow**. The data team exports UDB3 places, processes them in BigQuery, and uploads results to the intermediate table. Without those manual steps, the cluster commands have nothing to process.

---

## 3. Duplicate Prevention at Runtime

Separately from the cluster process, UDB3 has **built-in duplicate prevention at creation time**. When a new place is created via `POST /places/`, UDB3 checks for duplicates using a `global_address_identifier` (computed from name + street + postal code + locality + country). If a match is found, creation is rejected with `409 Conflict` and the response includes a `duplicatePlaceUri` pointing to the existing place.

Name mismatches (e.g. "TSM" vs "Technische Scholen Mechelen") or slight address differences will bypass this check. This is why the data team's pre-import duplicate analysis (chapter 9, step 2) is critical - they have a daily copy of all UDB3 places in BigQuery and can do fuzzy matching with geolocation before any `POST` is made. The runtime check is a last safety net, not the primary deduplication mechanism.

---

## 4. UDB3 Place API

Full API documentation: https://publiq.stoplight.io/docs/uitdatabank/entry-api/reference/operations/create-a-place

### `POST /places/`

Creates a new place. Returns `201 Created`, or `409 Conflict` if a duplicate was detected (see chapter 3).

### `PUT /places/{placeId}/`

Updates an existing place. The aggregate internally compares old and new values - only actual changes result in stored events and projection updates. This means the data team can safely PUT the full payload every time without worrying about no-op updates.

### Required fields

```json
{
  "mainLanguage": "nl",
  "name": {
    "nl": "Technische Scholen Mechelen"
  },
  "terms": [
    {
      "id": "rJRFUqmd6EiqTD4c7HS90w"
    }
  ],
  "address": {
    "nl": {
      "addressCountry": "BE",
      "addressLocality": "Mechelen",
      "postalCode": "2800",
      "streetAddress": "Jef Denynplein 2"
    }
  },
  "calendarType": "permanent"
}
```

- **Term ID `rJRFUqmd6EiqTD4c7HS90w`** = "School of onderwijscentrum" (the place type for schools)
- **`calendarType: "permanent"`** is appropriate for schools

### Optional fields useful for schools

| Field | Type | Description |
|---|---|---|
| `contactPoint.phone` | `string[]` | Phone number(s) |
| `contactPoint.email` | `string[]` | Email address(es) |
| `contactPoint.url` | `string[]` | Website URL(s) |
| `labels` | `string[]` | Visible labels (e.g. education level, net) |
| `hiddenLabels` | `string[]` | Hidden labels for internal use (e.g. `instelling-109892`) |
| `description` | `object` | Language-keyed descriptions, e.g. `{"nl": "..."}` |
| `bookingInfo` | `object` | Booking details with phone, email, url |
| `status` | `object` | `{"type": "Available"}` or `{"type": "Unavailable", "reason": {"nl": "..."}}` |
| `workflowStatus` | `string` | `"DRAFT"`, `"READY_FOR_VALIDATION"`, `"APPROVED"`, `"DELETED"` |
| `openingHours` | `array` | Array of `{dayOfWeek, opens, closes}` objects |

### Response

```json
{
  "id": "b19d4090-db47-4520-ac1a-880684357ec9",
  "placeId": "b19d4090-db47-4520-ac1a-880684357ec9",
  "url": "https://io.uitdatabank.dev/places/b19d4090-db47-4520-ac1a-880684357ec9",
  "commandId": "00000000-0000-0000-0000-000000000000"
}
```

---

## 5. The Instelling API

**Base URL**: `https://onderwijs.api.vlaanderen.be/instellingsgegevens/instelling/v2`
**Auth**: API key via `x-api-key` header or `apikey` query parameter
**Spec**: See `instelling.yaml` in this directory

### Endpoints

| Endpoint | Description |
|---|---|
| `GET /instelling` | Paginated list of all schools |
| `GET /instelling/{instellingsnummer}` | Detail of a single school by its unique number |

### Pagination

`page` (default: 1) and `size` (default: 20) query parameters. Response includes `meta.total_elements`, `meta.total_pages`, and `meta.last` for iterating all pages.

### Useful filters

| Filter | Values | Use case |
|---|---|---|
| `filter_instelling_niveau` | Defined in `instelling.yaml` enum | Filter by education level |
| `filter_instelling_status_erkenning` | `E` (erkend), `I`, `S`, `W`, `X` (meanings not documented in spec, need to verify via API) | Filter by recognition status, `E` = officially recognized |
| `zoek_instelling_gewijzigd_sinds` | Date (e.g. `2026-04-01`) | Only fetch schools modified since a given date |
| `filter_situatiedatum` | Date | Get data as it was on a specific date |
| `filter_instelling_type` | Various codes (see `instelling.yaml` enum) | Filter by school type |

---

## 6. Field Mapping: Instelling to Place

### Direct mappings

| Place field | Instelling field | Notes |
|---|---|---|
| `name.nl` | `instelling_naam_volledig` | Use the full name, not the abbreviation (`instelling_naam`) |
| `address.nl.streetAddress` | `instelling_straatnaam` + `instelling_huisnummer` (+ `instelling_busnummer`) | Concatenate: `"Jef Denynplein 2"` or `"Kerkstraat 10 bus 3"` |
| `address.nl.postalCode` | `instelling_postcode` | |
| `address.nl.addressLocality` | `instelling_gemeente` | API returns uppercase (e.g. `"MECHELEN"`), needs title-casing |
| `address.nl.addressCountry` | (hardcoded) | Always `"BE"` |
| `mainLanguage` | (hardcoded) | Always `"nl"` |
| `calendarType` | (hardcoded) | Always `"permanent"` |
| `terms[0].id` | (hardcoded) | Always `"rJRFUqmd6EiqTD4c7HS90w"` |
| `contactPoint.phone` | `instelling_telefoonnummers` | Array, max 2 items |
| `contactPoint.email` | `instelling_email` | Wrap in array |
| `contactPoint.url` | `instelling_website` | Wrap in array, ensure `https://` prefix |

### Metadata for labels

| Instelling field | Suggested label | Example |
|---|---|---|
| `instelling_nummer` | Hidden label: `instelling-{nummer}` | `instelling-109892` |
| `instelling_niveau.omschrijving` | Label | `Basisonderwijs`, `Secundair onderwijs` |
| `instelling_net.omschrijving` | Label | `Gemeenschapsonderwijs`, `Vrij gesubsidieerd onderwijs` |
| `instelling_type.omschrijving` | Hidden label | e.g. type description |

The `instelling_nummer` as a hidden label is essential - it serves as the **stable link** between the Instelling API and the UDB3 place.

### Existing education labels

UDB3 already has education-related labels in the Cultuurkuur system (`src/Cultuurkuur/data/education-levels.json`):
- `cultuurkuur_Gewoon-basisonderwijs`, `cultuurkuur_Gewoon-kleuteronderwijs`, `cultuurkuur_Gewoon-lager-onderwijs`
- `cultuurkuur_eerste-graad`, `cultuurkuur_tweede-graad`, `cultuurkuur_derde-graad` (secondary)
- `cultuurkuur_Buitengewoon-kleuteronderwijs`, `cultuurkuur_Buitengewoon-lager-onderwijs`
- `cultuurkuur_Hoger-onderwijs`, `cultuurkuur_Volwassenenonderwijs`, `cultuurkuur_Deeltijds-kunstonderwijs-DKO`

These should be reused where possible. The mapping from `instelling_niveau` / `instelling_hoofdstructuur` to these labels needs to be defined.

### Fields NOT mapped

| Field | Reason |
|---|---|
| `instelling_naam` | Abbreviation, use `instelling_naam_volledig` instead |
| `gps_*` / `lambert72_*` | UDB3 geocodes addresses itself |
| `instelling_bestuur` / `instelling_koepel` / etc. | Organizational hierarchy, not needed for place |
| `instelling_begindatum` / `instelling_einddatum` | Used for filtering closed schools, not mapped to place fields |
| `instelling_directeur(s)` | Just a name (no email/UiTID), cannot be used for ownership |

---

## 7. Linking Schools to UDB3 Places

The data team needs a way to know which `instelling_nummer` maps to which UDB3 place, both for updates and to avoid re-importing existing schools. Two approaches:

### Option A: Hidden label on the place

Include `instelling-{nummer}` (e.g. `instelling-109892`) as a hidden label in the `POST`/`PUT` payload. The data team can then search UDB3 for `hiddenLabels:instelling-109892` to find the linked place.

**Pros**:
- No new table, no new endpoint, no cross-boundary problem
- The mapping is part of the normal `POST`/`PUT` payload - no extra infrastructure
- Any UDB3 API consumer can see which places came from the school import

**Cons**:
- Listing all imported schools requires a search query, not a simple table scan
- No place to track sync metadata (`last_synced_at`, `instelling_datum_laatste_wijziging`)

### Option B: `school_api_imported` table in UDB3

A dedicated table in the UDB3 database:

| Column | Type | Description |
|---|---|---|
| `instelling_nummer` | INT (PK) | The school's unique number from the Instelling API |
| `place_uuid` | GUID | The UDB3 place ID that was created/matched |
| `last_synced_at` | DATETIME | When this record was last synced |
| `instelling_datum_laatste_wijziging` | DATE | The `instelling_datum_laatste_wijziging` value at time of last sync |

**Pros**:
- Direct lookup and easy to list all imported schools
- Can track sync metadata
- UDB3 can use it internally (e.g. preventing overwrites, auditing)

**Cons**:
- Needs to be populated somehow: new API endpoint or direct DB write (like `duplicate_places_import`)
- Adds a cross-boundary dependency between the data team and the UDB3 database

### Note

Option A could be used regardless - the hidden label is useful for searchability. Option B is only needed if sync metadata tracking or direct table access is required.

---

## 8. Ownership

Schools need to be able to modify their own data in UDB3. However, ownership is tied to registered **UiTID** users.

The Instelling API provides director names but no email or UiTID, so ownership cannot be assigned automatically during import.

### Possible approaches

1. **Import now, assign ownership later**: When a school registers on UiTID and claims their place, ownership is assigned manually with helpdesk support.
2. **Use `instelling_email` for invitation**: Send invitations to school email addresses to claim their place.
3. **Bulk ownership via data team**: Match school emails to existing UiTID accounts and assign ownership in bulk.

---

## 9. Initial Import Strategy

### Step 1: Fetch all schools

The data team paginates through the Instelling API using `filter_instelling_status_erkenning=E` to get only active schools (see chapter 5 for filters and pagination).

### Step 2: Duplicate analysis

Before creating any places, the data team matches schools against their daily copy of all UDB3 places in BigQuery. Using name matching and geolocation (Google API), they identify which schools already exist as places. This catches near-duplicates that UDB3's exact-match runtime check (chapter 3) would miss.

- For matches: record the school-to-place mapping (no `POST` needed)
- For non-matches: these need to be created

### Step 3: Create new places

For each unmatched school, build the Place JSON (see chapter 6) and call `POST /places/`. UDB3's runtime duplicate check (chapter 3) provides a second safety net:
- **201 Created**: New place. The response contains the place ID.
- **409 Conflict**: Duplicate detected. The response contains `duplicatePlaceUri` with the existing place's URL.

In both cases, the data team can extract the place ID to record the school-to-place mapping (see chapter 7).

### Considerations

- **Rate limiting**: Both the Instelling API (HTTP 429) and the UDB3 API need throttling for bulk operations.
- **Dry run**: Consider building the Place JSON without calling the UDB3 API first, for review.

---

## 10. Update Strategy

The data team runs this on a schedule (frequency TBD).

### Approach

Fetch schools from the Instelling API (full fetch, or `zoek_instelling_gewijzigd_sinds` for efficiency). For known schools: `PUT /places/{placeUuid}/`. For new schools: `POST /places/`.

### Closures and deletions

- Schools where `instelling_einddatum` is now in the past: action TBD (delete, mark unavailable, or leave as-is).
- Schools no longer in the API: flag for manual review rather?

---

## 11. Open Items

### Fields and mapping
- What do the `filter_instelling_status_erkenning` values (`I`, `S`, `W`, `X`) mean? Only `E` (erkend) is clear. Need to verify via API or documentation.
- Which `instelling_niveau` and `instelling_type` values to include? Enum values are in `instelling.yaml`.
- Do we need **translations**? The Instelling API only provides Dutch, but some Brussels schools may need French.
- Fall back to `instelling_naam` when `instelling_naam_volledig` exceeds 90 characters (Place name max)?
- How to format `instelling_busnummer`? E.g. `"Kerkstraat 10 bus 3"` or `"Kerkstraat 10/3"`?
- `instelling_gemeente` is uppercase (e.g. `"MECHELEN"`). Title-case it, or does UDB3 normalize?

### Labels
- How to map `instelling_niveau` / `instelling_hoofdstructuur` to existing `cultuurkuur_` labels?
- Should `instelling_nummer` be a visible or hidden label?

### Rate limits
- Actual rate limits on the Instelling API? The spec mentions HTTP 429 but no numbers.
- UDB3 API throughput for bulk operations?

### Infrastructure
- **Instelling API key**: How does the data team obtain and store it?
- **UDB3 API authentication**: Which API client/token does the data team use?
- **School-to-place link**: Hidden label, dedicated table, or both? (see chapter 7)

### Ownership
- How do schools claim their place?
- Can we match `instelling_email` to existing UiTID accounts?

### Closed schools
- Import schools with `instelling_einddatum` in the past? If yes, with what workflow status? (`DELETED` could be used to keep the place in UDB3 but hide it from public results)
- What happens when a previously active school closes? Should the data team set its `workflowStatus` to `DELETED`?
