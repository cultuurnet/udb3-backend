@api @places
Feature: Test opening hours closed days for places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Create periodic place with closed days
    Given I create a random name of 6 characters
    When I set the JSON request payload from "places/opening-hours-closed-days/place-periodic-with-closed-days.json"
    And I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "placeUrl"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "openingHoursClosedDays" should be:
    """
    [
      {
        "startDate": "2026-06-15",
        "endDate": "2026-06-15",
        "description": {"nl": "Gesloten wegens personeelsfeest", "en": "Closed for staff party"}
      },
      {
        "startDate": "2026-12-25",
        "endDate": "2026-12-26",
        "description": {"nl": "Gesloten met kerstdagen", "en": "Closed on Christmas holidays", "fr": "Fermé pendant les vacances de Noël"}
      }
    ]
    """

  Scenario: Create permanent place with closed days
    Given I create a random name of 6 characters
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "%{name}"},
      "terms": [{"id": "Yf4aZBfsUEu2NsQqsprngw"}],
      "address": {
        "nl": {
          "addressCountry": "BE",
          "addressLocality": "Scherpenheuvel-Zichem",
          "postalCode": "3271",
          "streetAddress": "Hoornblaas 107"
        }
      },
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-12-25",
          "endDate": "2026-12-25",
          "description": {
            "nl": "Gesloten: Kerstdag",
            "de": "Geschlossen: Weihnachten"
          }
        }
      ]
    }
    """
    And I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "placeUrl"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "openingHoursClosedDays" should be:
    """
    [
      {
        "startDate": "2026-12-25",
        "endDate": "2026-12-25",
        "description": {"nl": "Gesloten: Kerstdag", "de": "Geschlossen: Weihnachten"}
      }
    ]
    """

  Scenario: Update place calendar to add closed days
    Given I create a random name of 6 characters
    And I set the JSON request payload from "places/opening-hours-closed-days/place-periodic-without-closed-days.json"
    And I send a POST request to "/places/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "placeUrl"
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
      "openingHoursClosedDays": [
        {
          "startDate": "2026-12-25",
          "endDate": "2026-12-25"
        }
      ]
    }
    """
    And I send a PUT request to "%{placeUrl}/calendar"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "openingHoursClosedDays" should be:
    """
    [
      {
        "startDate": "2026-12-25",
        "endDate": "2026-12-25"
      }
    ]
    """

  Scenario: Update place calendar to replace existing closed days
    Given I create a random name of 6 characters
    And I set the JSON request payload from "places/opening-hours-closed-days/place-periodic-with-closed-days.json"
    And I send a POST request to "/places/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "placeUrl"
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
      "openingHoursClosedDays": [
        {
          "startDate": "2026-12-31",
          "endDate": "2026-12-31",
          "description": {"nl": "Oudejaarsdag"}
        }
      ]
    }
    """
    And I send a PUT request to "%{placeUrl}/calendar"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "openingHoursClosedDays" should be:
    """
    [
      {
        "startDate": "2026-12-31",
        "endDate": "2026-12-31",
        "description": {"nl": "Oudejaarsdag"}
      }
    ]
    """

  Scenario: Clear closed days by updating calendar without the field
    Given I create a random name of 6 characters
    And I set the JSON request payload from "places/opening-hours-closed-days/place-periodic-with-closed-days.json"
    And I send a POST request to "/places/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "placeUrl"
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
    And I send a PUT request to "%{placeUrl}/calendar"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    Then the JSON response should not have "openingHoursClosedDays"

  Scenario: Cannot create place when closed day startDate is after endDate
    Given I create a random name of 6 characters
    When I set the variable "calendarStartDate" to "2026-01-01T00:00:00+00:00"
    And I set the variable "calendarEndDate" to "2026-12-31T23:59:59+00:00"
    And I set the variable "closedStartDate" to "2026-12-25"
    And I set the variable "closedEndDate" to "2026-12-24"
    And I set the JSON request payload from "places/opening-hours-closed-days/place-periodic-with-invalid-closed-days.json"
    And I send a POST request to "/places/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHoursClosedDays/0/endDate"
    And the JSON response at "schemaErrors/0/error" should be "endDate should not be before startDate"

  Scenario: Cannot create periodic place when closed day is before calendar startDate
    Given I create a random name of 6 characters
    When I set the variable "calendarStartDate" to "2026-03-01T00:00:00+00:00"
    And I set the variable "calendarEndDate" to "2026-12-31T23:59:59+00:00"
    And I set the variable "closedStartDate" to "2026-01-01"
    And I set the variable "closedEndDate" to "2026-01-01"
    And I set the JSON request payload from "places/opening-hours-closed-days/place-periodic-with-invalid-closed-days.json"
    And I send a POST request to "/places/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHoursClosedDays/0/startDate"
    And the JSON response at "schemaErrors/0/error" should be "the start date of a closed day should not be before the calendar start date"

  Scenario: Cannot create periodic place when closed day is after calendar endDate
    Given I create a random name of 6 characters
    When I set the variable "calendarStartDate" to "2026-01-01T00:00:00+00:00"
    And I set the variable "calendarEndDate" to "2026-11-30T23:59:59+00:00"
    And I set the variable "closedStartDate" to "2026-12-25"
    And I set the variable "closedEndDate" to "2026-12-25"
    And I set the JSON request payload from "places/opening-hours-closed-days/place-periodic-with-invalid-closed-days.json"
    And I send a POST request to "/places/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHoursClosedDays/0/endDate"
    And the JSON response at "schemaErrors/0/error" should be "the end date of a closed day should not be after the calendar end date"

  Scenario: Cannot update place calendar when closed day is invalid
    Given I create a random name of 6 characters
    And I set the JSON request payload from "places/opening-hours-closed-days/place-periodic-without-closed-days.json"
    And I send a POST request to "/places/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "placeUrl"
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
          "dayOfWeek": ["monday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-12-25",
          "endDate": "2026-12-24"
        }
      ]
    }
    """
    And I send a PUT request to "%{placeUrl}/calendar"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHoursClosedDays/0/endDate"
    And the JSON response at "schemaErrors/0/error" should be "endDate should not be before startDate"
