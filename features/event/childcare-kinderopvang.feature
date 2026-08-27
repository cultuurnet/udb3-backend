Feature: Test that events with the Kinderopvang term cannot have childcare times

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Cannot create a Kinderopvang event with childcare on a subEvent via POST
    When I set the JSON request payload from "events/childcare-kinderopvang/event-single-kinderopvang-with-childcare.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "detail" should be "childcare is not allowed when the event has term K7mPx3nQrT9bWfH2zL5cYv"

  Scenario: Cannot re-import a Kinderopvang event with childcare on a subEvent via PUT imports
    Given I create an event from "events/childcare-kinderopvang/event-single-kinderopvang.json" and save the "id" as "eventId"
    When I set the JSON request payload from "events/childcare-kinderopvang/event-single-kinderopvang-with-childcare.json"
    And I send a PUT request to "/imports/events/%{eventId}"
    Then the response status should be "400"
    And the JSON response at "detail" should be "childcare is not allowed when the event has term K7mPx3nQrT9bWfH2zL5cYv"

  Scenario: Cannot create a Kinderopvang event with childcare on opening hours via POST
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Kinderopvang De Speelboom"},
      "terms": [{"id": "K7mPx3nQrT9bWfH2zL5cYv", "label": "Kinderopvang", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2026-01-01T00:00:00+00:00",
      "endDate": "2026-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday"],
          "childcare": {
            "start": "08:00",
            "end": "18:00"
          }
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "detail" should be "childcare is not allowed when the event has term K7mPx3nQrT9bWfH2zL5cYv"

  Scenario: Cannot create a Kinderopvang event with childcare on adjusted opening hours via POST
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Kinderopvang De Speelboom"},
      "terms": [{"id": "K7mPx3nQrT9bWfH2zL5cYv", "label": "Kinderopvang", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2026-01-01T00:00:00+00:00",
      "endDate": "2026-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday"]
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
                "start": "12:30",
                "end": "15:30"
              }
            }
          ]
        }
      ]
    }
    """
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
    Given I create an event from "events/childcare-kinderopvang/event-single-concert-with-childcare.json" and save the "url" as "eventUrl"
    When I send a PUT request to "%{eventUrl}/type/K7mPx3nQrT9bWfH2zL5cYv"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "terms/0/id" should be "K7mPx3nQrT9bWfH2zL5cYv"
    And the JSON response should not have "subEvent/0/childcare"

  Scenario: childcare is removed from opening hours and adjusted days when the event type changes to Kinderopvang
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodiek concert met kinderopvang"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2026-01-01T00:00:00+00:00",
      "endDate": "2026-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday"],
          "childcare": {
            "start": "08:00",
            "end": "18:00"
          }
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
                "start": "12:30",
                "end": "15:30"
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
    When I send a PUT request to "%{eventUrl}/type/K7mPx3nQrT9bWfH2zL5cYv"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "terms/0/id" should be "K7mPx3nQrT9bWfH2zL5cYv"
    And the JSON response at "openingHours/0/opens" should be "09:00"
    And the JSON response at "openingHours/0/closes" should be "17:00"
    And the JSON response should not have "openingHours/0/childcare"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/opens" should be "13:00"
    And the JSON response at "openingHoursAdjustedDays/0/openingHours/0/closes" should be "15:00"
    And the JSON response should not have "openingHoursAdjustedDays/0/openingHours/0/childcare"

  Scenario: A Kinderopvang event without childcare is created normally
    When I set the JSON request payload from "events/childcare-kinderopvang/event-single-kinderopvang.json"
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "terms/0/id" should be "K7mPx3nQrT9bWfH2zL5cYv"
    And the JSON response should not have "subEvent/0/childcare"

  Scenario: childcare is still allowed on an event without the Kinderopvang term
    Given I create an event from "events/childcare-kinderopvang/event-single-concert-with-childcare.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "subEvent/0/childcare/start" should be "08:00"
    And the JSON response at "subEvent/0/childcare/end" should be "18:00"
