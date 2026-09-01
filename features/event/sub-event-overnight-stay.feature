Feature: Test SubEvent hasOvernightStay

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Create a single event with hasOvernightStay true
    When I set the JSON request payload from "events/sub-event-overnight-stay/event-single-with-overnight-stay.json"
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/hasOvernightStay" should be true

  Scenario: Create a multiple event with hasOvernightStay on one subEvent
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Meerdaags kamp"},
      "terms": [{"id": "0.57.0.0.0", "label": "Kamp of vakantie", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "multiple",
      "subEvent": [
        {
          "startDate": "2026-07-01T09:00:00+02:00",
          "endDate": "2026-07-05T17:00:00+02:00",
          "hasOvernightStay": true
        },
        {
          "startDate": "2026-07-10T09:00:00+02:00",
          "endDate": "2026-07-14T17:00:00+02:00"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/hasOvernightStay" should be true
    And the JSON response should not have "subEvent/1/hasOvernightStay"

  Scenario: hasOvernightStay false is omitted from the GET response
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Zomerkamp"},
      "terms": [{"id": "0.57.0.0.0", "label": "Kamp of vakantie", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2026-07-01T09:00:00+02:00",
      "endDate": "2026-07-05T17:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-07-01T09:00:00+02:00",
          "endDate": "2026-07-05T17:00:00+02:00",
          "hasOvernightStay": false
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "subEvent/0/hasOvernightStay"

  Scenario: hasOvernightStay is omitted from GET response when not set
    Given I create an event from "events/sub-event-overnight-stay/event-single-kamp.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "subEvent/0/hasOvernightStay"

  Scenario: Update hasOvernightStay to true via PUT calendar
    Given I create an event from "events/sub-event-overnight-stay/event-single-kamp.json" and save the "url" as "eventUrl"
    When I set the JSON request payload from "events/sub-event-overnight-stay/calendar-single-with-overnight-stay.json"
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/hasOvernightStay" should be true

  Scenario: Update hasOvernightStay to false via PATCH subEvents
    Given I create an event from "events/sub-event-overnight-stay/event-single-with-overnight-stay.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "hasOvernightStay": false
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "subEvent/0/hasOvernightStay"

  Scenario: hasOvernightStay is preserved when omitted from PATCH
    Given I create an event from "events/sub-event-overnight-stay/event-single-with-overnight-stay.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "status": {"type": "Available"}
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/hasOvernightStay" should be true

  Scenario: hasOvernightStay is reset when the event type changes away from kamp of vakantie
    Given I create an event from "events/sub-event-overnight-stay/event-single-with-overnight-stay.json" and save the "url" as "eventUrl"
    When I send a PUT request to "%{eventUrl}/type/0.50.4.0.0"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "subEvent/0/hasOvernightStay"

  Scenario: Cannot set hasOvernightStay on an event without the kamp of vakantie term via PUT calendar
    Given I create an event from "events/sub-event-overnight-stay/event-single-concert.json" and save the "url" as "eventUrl"
    When I set the JSON request payload from "events/sub-event-overnight-stay/calendar-single-with-overnight-stay.json"
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "400"
    And the JSON response at "detail" should be "hasOvernightStay is only allowed when the event has term 0.57.0.0.0"

  Scenario: Cannot set hasOvernightStay on an event without the kamp of vakantie term via PATCH subEvents
    Given I create an event from "events/sub-event-overnight-stay/event-single-concert.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "hasOvernightStay": true
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "400"
    And the JSON response at "detail" should be "hasOvernightStay is only allowed when the event has term 0.57.0.0.0"

  Scenario: Cannot create an event with hasOvernightStay true on a non-kamp event type via POST
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Concert"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2026-07-01T09:00:00+02:00",
      "endDate": "2026-07-05T17:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-07-01T09:00:00+02:00",
          "endDate": "2026-07-05T17:00:00+02:00",
          "hasOvernightStay": true
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "detail" should be "hasOvernightStay is only allowed when the event has term 0.57.0.0.0"

  Scenario: Cannot re-import an existing event with hasOvernightStay true on a non-kamp event type via PUT imports
    Given I create an event from "events/sub-event-overnight-stay/event-single-concert.json" and save the "id" as "eventId"
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Concert"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2026-07-01T09:00:00+02:00",
      "endDate": "2026-07-05T17:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-07-01T09:00:00+02:00",
          "endDate": "2026-07-05T17:00:00+02:00",
          "hasOvernightStay": true
        }
      ]
    }
    """
    And I send a PUT request to "/imports/events/%{eventId}"
    Then the response status should be "400"
    And the JSON response at "detail" should be "hasOvernightStay is only allowed when the event has term 0.57.0.0.0"

  Scenario: hasOvernightStay with wrong type is rejected by the schema on POST
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Zomerkamp"},
      "terms": [{"id": "0.57.0.0.0", "label": "Kamp of vakantie", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2026-07-01T09:00:00+02:00",
      "endDate": "2026-07-05T17:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-07-01T09:00:00+02:00",
          "endDate": "2026-07-05T17:00:00+02:00",
          "hasOvernightStay": "yes"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/hasOvernightStay"
    And the JSON response at "schemaErrors/0/error" should be "The data (string) must match the type: boolean"

  Scenario: hasOvernightStay with wrong type is rejected by the schema on PUT calendar
    Given I create an event from "events/sub-event-overnight-stay/event-single-kamp.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "single",
      "subEvent": [
        {
          "startDate": "2026-07-01T09:00:00+02:00",
          "endDate": "2026-07-05T17:00:00+02:00",
          "hasOvernightStay": 1
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/hasOvernightStay"
    And the JSON response at "schemaErrors/0/error" should be "The data (integer) must match the type: boolean"

  Scenario: hasOvernightStay with wrong type is rejected by the schema on PATCH subEvents
    Given I create an event from "events/sub-event-overnight-stay/event-single-kamp.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "hasOvernightStay": "yes"
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/0/hasOvernightStay"
    And the JSON response at "schemaErrors/0/error" should be "The data (string) must match the type: boolean"
