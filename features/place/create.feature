Feature: Test creating places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Create a place with only the required fields
    Given I create a minimal place and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "name" should be:
    """
    {
      "nl":"Cafe Den Hemel"
    }
    """
    And the JSON response at "terms" should be:
    """
    [{
      "id": "Yf4aZBfsUEu2NsQqsprngw",
      "domain": "eventtype",
      "label": "Cultuur- of ontmoetingscentrum"
    }]
    """
    And the JSON response at "terms/0/id" should be "Yf4aZBfsUEu2NsQqsprngw"
    And the JSON response at "address" should be:
    """
    {
      "nl" : {
        "addressCountry": "BE",
        "addressLocality": "Scherpenheuvel-Zichem",
        "postalCode": "3271",
        "streetAddress": "Hoornblaas 107"
      }
    }
    """
    And the JSON response at "workflowStatus" should be "DRAFT"
    And the JSON response at "calendarType" should be "permanent"
    And the JSON response at "completeness" should be 53

  Scenario: Create a place with contact point with missing fields
    Given I create a place from "places/place-with-contact-point-with-missing-fields.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "contactPoint" should be:
    """
    {
      "phone": [],
      "email": ["info@publiq.be"],
      "url": []
    }
    """
    And the JSON response at "completeness" should be 56

  Scenario: Create a place with workflowStatus set to APPROVED
    Given I create a place from "places/place-with-workflow-status-approved.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    And the JSON response at "workflowStatus" should be "DRAFT"

  Scenario: Create a place with workflowStatus set to DELETED
    Given I create a place from "places/place-with-workflow-status-deleted.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "DELETED"

  Scenario: Create a place with workflowStatus set to READY_FOR_VALIDATION
    Given I create a place from "places/place-with-workflow-status-ready-for-validation.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"

  Scenario: Create a place with only the required fields via the legacy imports path
    Given I import a new place from "places/place-with-required-fields.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "name" should be:
    """
    {
      "nl":"Cafe Den Hemel"
    }
    """
    And the JSON response at "terms" should be:
    """
    [{
      "id": "Yf4aZBfsUEu2NsQqsprngw",
      "domain": "eventtype",
      "label": "Cultuur- of ontmoetingscentrum"
    }]
    """
    And the JSON response at "terms/0/id" should be "Yf4aZBfsUEu2NsQqsprngw"
    And the JSON response at "address" should be:
    """
    {
      "nl" : {
        "addressCountry": "BE",
        "addressLocality": "Scherpenheuvel-Zichem",
        "postalCode": "3271",
        "streetAddress": "Hoornblaas 107"
      }
    }
    """
    And the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"
    And the JSON response at "calendarType" should be "permanent"
    And the JSON response at "completeness" should be 53

  Scenario: Create a place with only the required fields and workflowStatus approved via legacy imports path
    Given I import a new place from "places/place-with-workflow-status-approved.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "name" should be:
    """
    {
      "nl":"Cafe Den Hemel"
    }
    """
    And the JSON response at "terms" should be:
    """
    [{
      "id": "Yf4aZBfsUEu2NsQqsprngw",
      "domain": "eventtype",
      "label": "Cultuur- of ontmoetingscentrum"
    }]
    """
    And the JSON response at "terms/0/id" should be "Yf4aZBfsUEu2NsQqsprngw"
    And the JSON response at "address" should be:
    """
    {
      "nl" : {
        "addressCountry": "BE",
        "addressLocality": "Scherpenheuvel-Zichem",
        "postalCode": "3271",
        "streetAddress": "Hoornblaas 107"
      }
    }
    """
    And the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"
    And the JSON response at "calendarType" should be "permanent"

  Scenario: Create a place with all fields
    Given I create a place from "places/place-with-all-fields.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name" should be:
    """
    {
      "nl":"Cafe Den Hemel"
    }
    """
    And the JSON response at "terms" should be:
    """
    [{
      "id": "Yf4aZBfsUEu2NsQqsprngw",
      "domain": "eventtype",
      "label": "Cultuur- of ontmoetingscentrum"
    }]
    """
    And the JSON response at "terms/0/id" should be "Yf4aZBfsUEu2NsQqsprngw"
    And the JSON response at "address" should be:
    """
    {
      "nl" : {
        "addressCountry": "BE",
        "addressLocality": "Scherpenheuvel-Zichem",
        "postalCode": "3271",
        "streetAddress": "Hoornblaas 107"
      }
    }
    """
    And the JSON response at "workflowStatus" should be "DRAFT"
    And the JSON response at "calendarType" should be "permanent"
    And the JSON response at "openingHours" should be:
    """
    [
      {
        "dayOfWeek": [
          "monday",
          "wednesday",
          "thursday",
          "friday"
        ],
        "opens": "17:00",
        "closes": "23:59"
      },
      {
        "dayOfWeek": [
          "saturday",
          "sunday"
        ],
        "opens": "15:00",
        "closes": "23:59"
      }
    ]
    """
    And the JSON response at "status" should be:
    """
    {
      "type": "Unavailable",
      "reason": {
        "nl": "We zijn nog steeds gesloten."
      }
    }
    """
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Available"
    }
    """
    And the JSON response at "priceInfo" should be:
    """
    [{
      "category": "base",
      "price": 10.5,
      "priceCurrency": "EUR",
      "name": {
        "nl": "Basistarief",
        "de": "Basisrate",
        "en": "Base tariff",
        "fr": "Tarif de base"
      }
    }]
    """
    And the JSON response at "contactPoint" should be:
    """
    {
      "phone": [
        "016 10 20 30"
      ],
      "email": [
        "info@denhemel.be"
      ],
      "url": [
        "https://www.denhemel.be"
      ]
    }
    """
    And the JSON response at "bookingInfo" should be:
    """
    {
      "phone": "016 10 20 30",
      "email": "booking@denhemel.be",
      "url": "https://www.denhemel.be/booking",
      "urlLabel": {
        "nl": "Bestel hier je tickets"
      },
      "availabilityStarts": "2020-05-17T22:00:00+00:00",
      "availabilityEnds": "2028-05-17T22:00:00+00:00"
    }
    """
    And the JSON response at "videos/0" should be:
    """
    {
      "id": "b504cf44-9ab8-4641-9934-38d1cc67242c",
      "url": "https://www.youtube.com/watch?v=cEItmb_a20D",
      "embedUrl": "https://www.youtube.com/embed/cEItmb_a20D",
      "language": "nl",
      "copyrightHolder": "Den Hemel"
    }
    """
    And the JSON response at "videos/1" should be:
    """
    {
      "id": "5c549a24-bb97-4f83-8ea5-21a6d56aff72",
      "url": "https://vimeo.com/98765432",
      "embedUrl": "https://player.vimeo.com/video/98765432",
      "language": "nl",
      "copyrightHolder": "Copyright afgehandeld door Vimeo"
    }
    """
    And the JSON response at "labels" should be:
    """
    [
      "public-visible"
    ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [
      "public-invisible"
    ]
    """
    And the JSON response at "completeness" should be 68

  @bugfix # https://jira.uitdatabank.be/browse/III-4669
  Scenario: Create a place with all fields and remove the optional ones again using null values and empty lists
    Given I create a place from "places/place-with-all-fields.json" and save the "url" as "placeUrl"
    When I update the place at "%{placeUrl}" from "places/place-with-required-fields-and-null-or-empty-values.json"
    And I get the place at "%{placeUrl}"
    Then the JSON response should not have "labels"
    And the JSON response should not have "hiddenLabels"
    And the JSON response should not have "openingHours"
    And the JSON response at "contactPoint" should be:
    """
    {
      "phone": [],
      "email": [],
      "url": []
    }
    """
    And the JSON response should not have "bookingInfo"
    # Note that priceInfo cannot be removed once set currently
    And the JSON response should have "priceInfo"
    And the JSON response at "completeness" should be 60

  Scenario: Create place with permanent calendar from legacy JSON source
    Given I create a place from "places/legacy/create-permanent-place.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name" should be:
    """
    {
      "nl": "Cafe Den Hemel"
    }
    """
    And the JSON response at "address" should be:
    """
    {
      "nl": {
        "addressCountry": "BE",
        "addressLocality": "Scherpenheuvel-Zichem",
        "postalCode": "3271",
        "streetAddress": "Hoornblaas 107"
      }
    }
    """
    And the JSON response at "terms" should be:
    """
    [{
      "id": "ekdc4ATGoUitCa0e6me6xA",
      "label": "Horeca",
      "domain": "eventtype"
    }]
    """
    And the JSON response at "workflowStatus" should be "DRAFT"
    And the JSON response at "calendarType" should be "permanent"
    And the JSON response at "openingHours" should be:
    """
    [
      {
        "dayOfWeek": [
          "monday",
          "wednesday",
          "thursday",
          "friday"
        ],
        "opens": "17:00",
        "closes": "23:59"
      },
      {
        "dayOfWeek": [
          "saturday",
          "sunday"
        ],
        "opens": "15:00",
        "closes": "23:59"
      }
    ]
    """
    And the JSON response at "status" should be:
    """
    {
      "type": "Available"
    }
    """
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Available"
    }
    """
    And the JSON response should not have "startDate"
    And the JSON response should not have "endDate"

  Scenario: Create place with permanent calendar and incomplete type from legacy JSON source
    Given I create a place from "places/legacy/create-place-with-incomplete-type.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name" should be:
    """
    {
      "nl": "Cafe Den Hemel"
    }
    """
    And the JSON response at "address" should be:
    """
    {
      "nl": {
        "addressCountry": "BE",
        "addressLocality": "Scherpenheuvel-Zichem",
        "postalCode": "3271",
        "streetAddress": "Hoornblaas 107"
      }
    }
    """
    And the JSON response at "terms" should be:
    """
    [{
      "id": "ekdc4ATGoUitCa0e6me6xA",
      "label": "Horeca",
      "domain": "eventtype"
    }]
    """
    And the JSON response at "workflowStatus" should be "DRAFT"
    And the JSON response at "calendarType" should be "permanent"
    And the JSON response at "openingHours" should be:
    """
    [
      {
        "dayOfWeek": [
          "monday",
          "wednesday",
          "thursday",
          "friday"
        ],
        "opens": "17:00",
        "closes": "23:59"
      },
      {
        "dayOfWeek": [
          "saturday",
          "sunday"
        ],
        "opens": "15:00",
        "closes": "23:59"
      }
    ]
    """
    And the JSON response at "status" should be:
    """
    {
      "type": "Available"
    }
    """
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Available"
    }
    """
    And the JSON response should not have "startDate"
    And the JSON response should not have "endDate"

  Scenario: Create place with periodic calendar from legacy JSON source
    Given I create a place from "places/legacy/create-periodic-place.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name" should be:
    """
    {
      "nl": "Cafe Den Hemel"
    }
    """
    And the JSON response at "address" should be:
    """
    {
      "nl": {
        "addressCountry": "BE",
        "addressLocality": "Scherpenheuvel-Zichem",
        "postalCode": "3271",
        "streetAddress": "Hoornblaas 107"
      }
    }
    """
    And the JSON response at "terms" should be:
    """
    [{
      "id": "ekdc4ATGoUitCa0e6me6xA",
      "label": "Horeca",
      "domain": "eventtype"
    }]
    """
    And the JSON response at "workflowStatus" should be "DRAFT"
    And the JSON response at "calendarType" should be "periodic"
    And the JSON response at "startDate" should be "2022-01-01T11:22:33+00:00"
    And the JSON response at "endDate" should be "2032-01-01T11:22:33+00:00"
    And the JSON response should not have "openingHours"
    And the JSON response at "status" should be:
    """
    {
      "type": "Available"
    }
    """
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Available"
    }
    """

  Scenario: Create a place with a non existing organizer
    Given I set the JSON request payload from "places/place-with-non-existing-organizer.json"
    When I send a POST request to "/places/"
    Then the response status should be "400"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "The organizer with id \"bcbf3a32-0c55-4ece-bb91-66f653725d66\" was not found.",
          "jsonPointer": "/organizer"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4670
  Scenario: Create place with missing calendar from legacy JSON source
    Given I create a place from "places/legacy/create-place-missing-calendar.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "calendarType" should be "permanent"

  @bugfix # https://jira.uitdatabank.be/browse/III-4670
  Scenario: Create place with missing calendar from legacy JSON source
    Given I create a place from "places/legacy/create-place-missing-calendar-type-but-start-date-and-end-date.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "calendarType" should be "periodic"
    And the JSON response at "startDate" should be "2022-01-01T11:22:33+00:00"
    And the JSON response at "endDate" should be "2032-01-01T11:22:33+00:00"

  @bugfix # https://jira.publiq.be/browse/III-4793
  Scenario: Try creating a place with missing body
    When I send a POST request to "/places/"
    Then the response status should be "400"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/body/missing",
      "title": "Body missing",
      "status": 400
    }
    """

  Scenario: I should not be able to create a place with a very long title
    Given I create a random name of 100 characters and keep it as "name"
    Given I create a minimal place then I should get a "400" response code
    Then the JSON response should be:
    """
    {
        "type": "https://api.publiq.be/probs/body/invalid-data",
        "title": "Invalid body data",
        "status": 400,
        "schemaErrors": [
            {
                "jsonPointer": "/title",
                "error": "Given string should not be longer than 90 characters."
            }
        ]
    }
    """