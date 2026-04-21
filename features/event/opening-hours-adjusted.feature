@api @events
Feature: Test opening hours adjusted for events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Create periodic event with opening hours adjusted
    When I set the JSON request payload from "events/opening-hours-adjusted/event-periodic-with-adjusted-hours-described.json"
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response should have "openingHoursAdjustedDays"
    And the JSON response at "openingHoursAdjustedDays/0/startDate" should be "2026-12-21"
    And the JSON response at "openingHoursAdjustedDays/0/endDate" should be "2026-12-26"
    And the JSON response at "openingHoursAdjustedDays/0/description/nl" should be "Kerstvakantie"
    And the JSON response at "openingHoursAdjustedDays/0/description/fr" should be "fêtes de Noël"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/opens" should be "13:00"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/closes" should be "15:00"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/dayOfWeek/0" should be "friday"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/dayOfWeek/1" should be "saturday"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/dayOfWeek/2" should be "sunday"
    And the JSON response at "openingHoursAdjustedDays/1/startDate" should be "2026-12-27"
    And the JSON response at "openingHoursAdjustedDays/1/endDate" should be "2026-12-31"
    And the JSON response at "openingHoursAdjustedDays/1/openingHours/0/opens" should be "14:00"
    And the JSON response at "openingHoursAdjustedDays/1/openingHours/0/closes" should be "16:00"
    And the JSON response at "openingHoursAdjustedDays/1/openingHours/0/dayOfWeek/0" should be "saturday"
    And the JSON response at "openingHoursAdjustedDays/1/openingHours/0/dayOfWeek/1" should be "sunday"

  Scenario: Create permanent event with opening hours adjusted
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Permanent event met aangepaste openingsuren"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]
        }
      ],
      "openingHoursAdjustedDays": [
        {
          "startDate": "2026-12-21",
          "endDate": "2026-12-26",
          "description": {
            "nl": "Kerstvakantie"
          },
          "openingHours": [
            {
              "opens": "13:00",
              "closes": "15:00",
              "dayOfWeek": ["friday", "saturday", "sunday"]
            }
          ]
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response should have "openingHoursAdjustedDays"
    And the JSON response at "openingHoursAdjustedDays/0/startDate" should be "2026-12-21"
    And the JSON response at "openingHoursAdjustedDays/0/endDate" should be "2026-12-26"
    And the JSON response at "openingHoursAdjustedDays/0/description/nl" should be "Kerstvakantie"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/opens" should be "13:00"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/closes" should be "15:00"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/dayOfWeek/0" should be "friday"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/dayOfWeek/1" should be "saturday"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/dayOfWeek/2" should be "sunday"

  Scenario: Update event calendar to add opening hours adjusted
    Given I set the JSON request payload from "events/opening-hours-adjusted/event-periodic-without-adjusted-hours.json"
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-01-01T00:00:00+00:00",
      "endDate": "2026-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursAdjustedDays": [
        {
          "startDate": "2026-12-21",
          "endDate": "2026-12-26",
          "openingHours": [
            {
              "opens": "13:00",
              "closes": "15:00",
              "dayOfWeek": ["friday", "saturday", "sunday"]
            }
          ]
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    Then the JSON response should have "openingHoursAdjustedDays"
    And the JSON response at "openingHoursAdjustedDays/0/startDate" should be "2026-12-21"
    And the JSON response at "openingHoursAdjustedDays/0/endDate" should be "2026-12-26"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/opens" should be "13:00"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/closes" should be "15:00"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/dayOfWeek/0" should be "friday"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/dayOfWeek/1" should be "saturday"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/dayOfWeek/2" should be "sunday"

  Scenario: Update event calendar to replace existing opening hours adjusted
    Given I set the JSON request payload from "events/opening-hours-adjusted/event-periodic-with-adjusted-hours.json"
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-01-01T00:00:00+00:00",
      "endDate": "2026-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursAdjustedDays": [
        {
          "startDate": "2026-12-27",
          "endDate": "2026-12-31",
          "description": {"nl": "Nieuwjaar"},
          "openingHours": [
            {
              "opens": "10:00",
              "closes": "12:00",
              "dayOfWeek": ["sunday"]
            }
          ]
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    Then the JSON response should have "openingHoursAdjustedDays"
    And the JSON response at "openingHoursAdjustedDays/0/startDate" should be "2026-12-27"
    And the JSON response at "openingHoursAdjustedDays/0/endDate" should be "2026-12-31"
    And the JSON response at "openingHoursAdjustedDays/0/description/nl" should be "Nieuwjaar"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/opens" should be "10:00"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/closes" should be "12:00"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/dayOfWeek/0" should be "sunday"

  Scenario: Clear opening hours adjusted by updating calendar without the field
    Given I set the JSON request payload from "events/opening-hours-adjusted/event-periodic-with-adjusted-hours.json"
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-01-01T00:00:00+00:00",
      "endDate": "2026-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    Then the JSON response should not have "openingHoursAdjustedDays"

  Scenario: Opening hours adjusted with childcare
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodiek event met kinderopvang"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2026-01-01T00:00:00+00:00",
      "endDate": "2026-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursAdjustedDays": [
        {
          "startDate": "2026-12-21",
          "endDate": "2026-12-26",
          "openingHours": [
            {
              "opens": "13:00",
              "closes": "15:00",
              "dayOfWeek": ["friday"],
              "childcare": {
                "start": "13:30",
                "end": "14:30"
              }
            }
          ]
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response should have "openingHoursAdjustedDays"
    And the JSON response at "openingHoursAdjustedDays/0/startDate" should be "2026-12-21"
    And the JSON response at "openingHoursAdjustedDays/0/endDate" should be "2026-12-26"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/opens" should be "13:00"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/closes" should be "15:00"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/dayOfWeek/0" should be "friday"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/childcare/start" should be "13:30"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/childcare/end" should be "14:30"

  Scenario: Cannot create event when adjusted opening hours startDate is after endDate
    When I set the variable "calendarStartDate" to "2026-01-01T00:00:00+00:00"
    And I set the variable "calendarEndDate" to "2026-12-31T23:59:59+00:00"
    And I set the variable "adjustedStartDate" to "2026-12-26"
    And I set the variable "adjustedEndDate" to "2026-12-21"
    And I set the JSON request payload from "events/opening-hours-adjusted/event-periodic-with-invalid-adjusted-hours.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHoursAdjustedDays/0/endDate"
    And the JSON response at "schemaErrors/0/error" should be "startDate should not be later than endDate"

  Scenario: Cannot create periodic event when adjusted opening hours is before calendar startDate
    When I set the variable "calendarStartDate" to "2026-03-01T00:00:00+00:00"
    And I set the variable "calendarEndDate" to "2026-12-31T23:59:59+00:00"
    And I set the variable "adjustedStartDate" to "2026-01-01"
    And I set the variable "adjustedEndDate" to "2026-01-15"
    And I set the JSON request payload from "events/opening-hours-adjusted/event-periodic-with-invalid-adjusted-hours.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHoursAdjustedDays/0/startDate"
    And the JSON response at "schemaErrors/0/error" should be "the start date of adjusted opening hours should not be before the calendar start date"

  Scenario: Cannot create periodic event when adjusted opening hours is after calendar endDate
    When I set the variable "calendarStartDate" to "2026-01-01T00:00:00+00:00"
    And I set the variable "calendarEndDate" to "2026-11-30T23:59:59+00:00"
    And I set the variable "adjustedStartDate" to "2026-12-21"
    And I set the variable "adjustedEndDate" to "2026-12-26"
    And I set the JSON request payload from "events/opening-hours-adjusted/event-periodic-with-invalid-adjusted-hours.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHoursAdjustedDays/0/endDate"
    And the JSON response at "schemaErrors/0/error" should be "the end date of adjusted opening hours should not be after the calendar end date"

  Scenario: Cannot create event when adjusted opening hours entries overlap
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Ongeldig event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2026-01-01T00:00:00+00:00",
      "endDate": "2026-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday"]
        }
      ],
      "openingHoursAdjustedDays": [
        {
          "startDate": "2026-12-21",
          "endDate": "2026-12-26",
          "openingHours": [{"opens": "13:00", "closes": "15:00", "dayOfWeek": ["friday"]}]
        },
        {
          "startDate": "2026-12-25",
          "endDate": "2026-12-31",
          "openingHours": [{"opens": "14:00", "closes": "16:00", "dayOfWeek": ["saturday"]}]
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHoursAdjustedDays/1/startDate"
    And the JSON response at "schemaErrors/0/error" should be "adjusted opening hours entries must not overlap"
