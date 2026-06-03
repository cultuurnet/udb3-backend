@sapi3
Feature: Test that closed days are excluded from calendar search results

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed

  Scenario: Periodic event closed day is excluded from search results
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodiek event met gesloten dag"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2026-07-01T00:00:00+02:00",
      "endDate": "2026-12-31T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-07-06",
          "endDate": "2026-07-06"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I wait for the event with url "%{eventUrl}" to be indexed
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T09:00:00+02:00 |
      | dateTo                | 2026-07-06T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{eventUrl}               |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-07T09:00:00+02:00 |
      | dateTo                | 2026-07-07T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{eventUrl}               |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  Scenario: Permanent event closed day is excluded from search results
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Permanent event met gesloten dag"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-07-06",
          "endDate": "2026-07-06"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I wait for the event with url "%{eventUrl}" to be indexed
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T09:00:00+02:00 |
      | dateTo                | 2026-07-06T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{eventUrl}               |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-07T09:00:00+02:00 |
      | dateTo                | 2026-07-07T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{eventUrl}               |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  Scenario: Periodic place closed day is excluded from search results
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodieke locatie met gesloten dag"},
      "terms": [{"id": "Yf4aZBfsUEu2NsQqsprngw"}],
      "address": {"nl": {"addressCountry": "BE", "addressLocality": "Leuven", "postalCode": "3000", "streetAddress": "Bondgenotenlaan 1"}},
      "calendarType": "periodic",
      "startDate": "2026-07-01T00:00:00+02:00",
      "endDate": "2026-12-31T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-07-06",
          "endDate": "2026-07-06"
        }
      ]
    }
    """
    And I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "placeUrl2"
    And I wait for the place with url "%{placeUrl2}" to be indexed
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-06T09:00:00+02:00 |
      | dateTo                | 2026-07-06T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{placeUrl2}              |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-07T09:00:00+02:00 |
      | dateTo                | 2026-07-07T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{placeUrl2}              |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  Scenario: Permanent place closed day is excluded from search results
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Permanente locatie met gesloten dag"},
      "terms": [{"id": "Yf4aZBfsUEu2NsQqsprngw"}],
      "address": {"nl": {"addressCountry": "BE", "addressLocality": "Leuven", "postalCode": "3000", "streetAddress": "Bondgenotenlaan 1"}},
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-07-06",
          "endDate": "2026-07-06"
        }
      ]
    }
    """
    And I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "placeUrl2"
    And I wait for the place with url "%{placeUrl2}" to be indexed
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-06T09:00:00+02:00 |
      | dateTo                | 2026-07-06T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{placeUrl2}              |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-07T09:00:00+02:00 |
      | dateTo                | 2026-07-07T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{placeUrl2}              |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
