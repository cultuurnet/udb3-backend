Feature: Test that events with the Kinderopvang term cannot have childcare times

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Cannot create a Kinderopvang event with childcare on a subEvent via POST
    Given I set the variable "termId" to "K7mPx3nQrT9bWfH2zL5cYv"
    When I set the JSON request payload from "events/childcare-kinderopvang/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "detail" should be "childcare is not allowed when the event has term K7mPx3nQrT9bWfH2zL5cYv"

  Scenario: Cannot re-import a Kinderopvang event with childcare on a subEvent via PUT imports
    Given I create an event from "events/childcare-kinderopvang/event-single-kinderopvang.json" and save the "id" as "eventId"
    And I set the variable "termId" to "K7mPx3nQrT9bWfH2zL5cYv"
    When I set the JSON request payload from "events/childcare-kinderopvang/event-single-with-childcare.json"
    And I send a PUT request to "/imports/events/%{eventId}"
    Then the response status should be "400"
    And the JSON response at "detail" should be "childcare is not allowed when the event has term K7mPx3nQrT9bWfH2zL5cYv"

  Scenario: Cannot update a Kinderopvang event with childcare on a subEvent via PUT
    Given I create an event from "events/childcare-kinderopvang/event-single-kinderopvang.json" and save the "id" as "eventId"
    And I set the variable "termId" to "K7mPx3nQrT9bWfH2zL5cYv"
    When I set the JSON request payload from "events/childcare-kinderopvang/event-single-with-childcare.json"
    And I send a PUT request to "/events/%{eventId}"
    Then the response status should be "400"
    And the JSON response at "detail" should be "childcare is not allowed when the event has term K7mPx3nQrT9bWfH2zL5cYv"

  Scenario: Cannot change the type to Kinderopvang and keep the childcare in the same PUT
    Given I set the variable "termId" to "0.50.4.0.0"
    And I create an event from "events/childcare-kinderopvang/event-single-with-childcare.json" and save the "id" as "eventId"
    And I set the variable "termId" to "K7mPx3nQrT9bWfH2zL5cYv"
    When I set the JSON request payload from "events/childcare-kinderopvang/event-single-with-childcare.json"
    And I send a PUT request to "/events/%{eventId}"
    Then the response status should be "400"
    And the JSON response at "detail" should be "childcare is not allowed when the event has term K7mPx3nQrT9bWfH2zL5cYv"

  Scenario: Cannot create a Kinderopvang event with childcare on opening hours via POST
    Given I set the variable "termId" to "K7mPx3nQrT9bWfH2zL5cYv"
    When I set the JSON request payload from "events/childcare-kinderopvang/event-periodic-with-childcare-on-opening-hours.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "detail" should be "childcare is not allowed when the event has term K7mPx3nQrT9bWfH2zL5cYv"

  Scenario: Cannot create a Kinderopvang event with childcare on adjusted opening hours via POST
    Given I set the variable "termId" to "K7mPx3nQrT9bWfH2zL5cYv"
    When I set the JSON request payload from "events/childcare-kinderopvang/event-periodic-with-childcare-on-adjusted-days.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "detail" should be "childcare is not allowed when the event has term K7mPx3nQrT9bWfH2zL5cYv"

  Scenario: Cannot set childcare on a Kinderopvang event via PUT calendar
    Given I create an event from "events/childcare-kinderopvang/event-single-kinderopvang.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "single",
      "subEvent": [
        {
          "startDate": "2026-07-01T09:00:00+02:00",
          "endDate": "2026-07-05T17:00:00+02:00",
          "childcare": {
            "start": "08:00",
            "end": "18:00"
          }
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "400"
    And the JSON response at "detail" should be "childcare is not allowed when the event has term K7mPx3nQrT9bWfH2zL5cYv"

  Scenario: Cannot set childcare on a Kinderopvang event via PATCH subEvents
    Given I create an event from "events/childcare-kinderopvang/event-single-kinderopvang.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "childcare": {
          "start": "08:00",
          "end": "18:00"
        }
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "400"
    And the JSON response at "detail" should be "childcare is not allowed when the event has term K7mPx3nQrT9bWfH2zL5cYv"

  Scenario: childcare is removed from subEvents when the event type changes to Kinderopvang
    Given I set the variable "termId" to "0.50.4.0.0"
    And I create an event from "events/childcare-kinderopvang/event-single-with-childcare.json" and save the "url" as "eventUrl"
    When I send a PUT request to "%{eventUrl}/type/K7mPx3nQrT9bWfH2zL5cYv"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "terms/0/id" should be "K7mPx3nQrT9bWfH2zL5cYv"
    And the JSON response at "subEvent" should have 1 entry
    And the JSON response at "subEvent/0" should be:
    """
    {
      "id": 0,
      "@type": "Event",
      "startDate": "2026-07-01T09:00:00+02:00",
      "endDate": "2026-07-05T17:00:00+02:00",
      "status": {"type": "Available"},
      "bookingAvailability": {"type": "Available"}
    }
    """

  Scenario: childcare is removed from opening hours when the event type changes to Kinderopvang
    Given I set the variable "termId" to "0.50.4.0.0"
    And I create an event from "events/childcare-kinderopvang/event-periodic-with-childcare-on-opening-hours.json" and save the "url" as "eventUrl"
    When I send a PUT request to "%{eventUrl}/type/K7mPx3nQrT9bWfH2zL5cYv"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "terms/0/id" should be "K7mPx3nQrT9bWfH2zL5cYv"
    And the JSON response at "openingHours" should have 1 entry
    And the JSON response at "openingHours/0" should be:
    """
    {
      "opens": "09:00",
      "closes": "17:00",
      "dayOfWeek": ["monday", "tuesday", "wednesday"]
    }
    """

  Scenario: childcare is removed from adjusted opening hours when the event type changes to Kinderopvang
    Given I set the variable "termId" to "0.50.4.0.0"
    And I create an event from "events/childcare-kinderopvang/event-periodic-with-childcare-on-adjusted-days.json" and save the "url" as "eventUrl"
    When I send a PUT request to "%{eventUrl}/type/K7mPx3nQrT9bWfH2zL5cYv"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "terms/0/id" should be "K7mPx3nQrT9bWfH2zL5cYv"
    And the JSON response at "openingHoursAdjustedDays" should have 1 entry
    And the JSON response at "openingHoursAdjustedDays/0" should be:
    """
    {
      "startDate": "2026-12-21",
      "endDate": "2026-12-26",
      "openingHours": [
        {
          "opens": "13:00",
          "closes": "15:00",
          "dayOfWeek": ["friday"]
        }
      ]
    }
    """
    And the JSON response at "openingHours/0" should be:
    """
    {
      "opens": "09:00",
      "closes": "17:00",
      "dayOfWeek": ["monday", "tuesday", "wednesday"]
    }
    """

  Scenario: A Kinderopvang event without childcare is created normally
    When I set the JSON request payload from "events/childcare-kinderopvang/event-single-kinderopvang.json"
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "terms/0/id" should be "K7mPx3nQrT9bWfH2zL5cYv"
    And the JSON response should not have "subEvent/0/childcare"

  Scenario: childcare is still allowed on an event without the Kinderopvang term
    Given I set the variable "termId" to "0.50.4.0.0"
    And I create an event from "events/childcare-kinderopvang/event-single-with-childcare.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "terms/0/id" should be "0.50.4.0.0"
    And the JSON response at "subEvent/0/childcare/start" should be "08:00"
    And the JSON response at "subEvent/0/childcare/end" should be "18:00"
