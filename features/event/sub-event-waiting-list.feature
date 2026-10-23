Feature: Test SubEvent waiting list url

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Create an event with a waiting list url on a subEvent
    When I create an event from "events/sub-event-waiting-list/event-multiple-with-waiting-list.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {"type": "Unavailable", "waitingListUrl": "https://publiq.be/wachtlijst"}
    """
    And the JSON response at "subEvent/1/bookingAvailability" should be:
    """
    {"type": "Available"}
    """
    And the JSON response at "bookingAvailability" should be:
    """
    {"type": "Available"}
    """

  Scenario: Set a waiting list url on a subEvent via PATCH
    Given I create an event from "events/sub-event-waiting-list/event-multiple.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [{"id": 0, "bookingAvailability": {"type": "Unavailable", "waitingListUrl": "https://publiq.be/wachtlijst"}}]
    """
    And I send a PATCH request to "%{eventUrl}/sub-events"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {"type": "Unavailable", "waitingListUrl": "https://publiq.be/wachtlijst"}
    """
    And the JSON response at "subEvent/1/bookingAvailability" should be:
    """
    {"type": "Available"}
    """

  Scenario: The waiting list url is kept when a PATCH has no bookingAvailability
    Given I create an event from "events/sub-event-waiting-list/event-multiple-with-waiting-list.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [{"id": 0, "status": {"type": "TemporarilyUnavailable"}}]
    """
    And I send a PATCH request to "%{eventUrl}/sub-events"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/status/type" should be "TemporarilyUnavailable"
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {"type": "Unavailable", "waitingListUrl": "https://publiq.be/wachtlijst"}
    """

  Scenario: The waiting list url is kept when a PATCH has a bookingAvailability without waitingListUrl
    Given I create an event from "events/sub-event-waiting-list/event-multiple-with-waiting-list.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [{"id": 0, "bookingAvailability": {"type": "Available"}}]
    """
    And I send a PATCH request to "%{eventUrl}/sub-events"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {"type": "Available", "waitingListUrl": "https://publiq.be/wachtlijst"}
    """

  Scenario: Clear the waiting list url of a subEvent via PATCH with an empty string
    Given I create an event from "events/sub-event-waiting-list/event-multiple-with-waiting-list.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [{"id": 0, "bookingAvailability": {"type": "Unavailable", "waitingListUrl": ""}}]
    """
    And I send a PATCH request to "%{eventUrl}/sub-events"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {"type": "Unavailable"}
    """

  Scenario: The waiting list url is kept when a PATCH sends it as null
    Given I create an event from "events/sub-event-waiting-list/event-multiple-with-waiting-list.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [{"id": 0, "bookingAvailability": {"type": "Unavailable", "waitingListUrl": null}}]
    """
    And I send a PATCH request to "%{eventUrl}/sub-events"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {"type": "Unavailable", "waitingListUrl": "https://publiq.be/wachtlijst"}
    """

  Scenario: An empty waiting list url is not allowed in a calendar update
    Given I create an event from "events/sub-event-waiting-list/event-multiple.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "single",
      "subEvent": [
        {
          "startDate": "2030-05-17T20:00:00+02:00",
          "endDate": "2030-05-17T22:00:00+02:00",
          "bookingAvailability": {"type": "Unavailable", "waitingListUrl": ""}
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/bookingAvailability/waitingListUrl"

  Scenario: An invalid waiting list url is rejected
    Given I create an event from "events/sub-event-waiting-list/event-multiple.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [{"id": 0, "bookingAvailability": {"type": "Unavailable", "waitingListUrl": "not-a-url"}}]
    """
    And I send a PATCH request to "%{eventUrl}/sub-events"
    Then the response status should be "400"
    And the JSON response at "type" should be "https://api.publiq.be/probs/body/invalid-data"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/0/bookingAvailability/waitingListUrl"

  Scenario: Set a waiting list url on a subEvent via a calendar update
    Given I create an event from "events/sub-event-waiting-list/event-multiple.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "single",
      "subEvent": [
        {
          "startDate": "2030-05-17T20:00:00+02:00",
          "endDate": "2030-05-17T22:00:00+02:00",
          "bookingAvailability": {"type": "Unavailable", "waitingListUrl": "https://publiq.be/wachtlijst"}
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {"type": "Unavailable", "waitingListUrl": "https://publiq.be/wachtlijst"}
    """

  Scenario: A calendar update without waiting list url removes it
    Given I create an event from "events/sub-event-waiting-list/event-multiple-with-waiting-list.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "multiple",
      "subEvent": [
        {
          "startDate": "2030-05-17T20:00:00+02:00",
          "endDate": "2030-05-17T22:00:00+02:00",
          "bookingAvailability": {"type": "Unavailable"}
        },
        {
          "startDate": "2030-05-18T20:00:00+02:00",
          "endDate": "2030-05-18T22:00:00+02:00",
          "bookingAvailability": {"type": "Available"}
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {"type": "Unavailable"}
    """

  Scenario: Marking an event as sold out keeps the waiting list urls of its subEvents
    Given I create an event from "events/sub-event-waiting-list/event-multiple-with-waiting-list.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {"type": "Unavailable"}
    """
    And I send a PUT request to "%{eventUrl}/booking-availability"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {"type": "Unavailable", "waitingListUrl": "https://publiq.be/wachtlijst"}
    """
    And the JSON response at "subEvent/1/bookingAvailability" should be:
    """
    {"type": "Unavailable"}
    """

  Scenario: A waiting list url is not allowed on the booking availability endpoint of an event
    Given I create an event from "events/sub-event-waiting-list/event-multiple.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {"type": "Unavailable", "waitingListUrl": "https://publiq.be/wachtlijst"}
    """
    And I send a PUT request to "%{eventUrl}/booking-availability"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/body/invalid-data",
      "title": "Invalid body data",
      "status": 400,
      "schemaErrors": [
        {
          "jsonPointer": "/",
          "error": "Property 'waitingListUrl' is not allowed"
        }
      ]
    }
    """

  Scenario: A waiting list url is not allowed on the event level of a calendar update
    Given I create an event from "events/sub-event-waiting-list/event-multiple.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "single",
      "bookingAvailability": {"type": "Unavailable", "waitingListUrl": "https://publiq.be/wachtlijst"},
      "subEvent": [
        {
          "startDate": "2030-05-17T20:00:00+02:00",
          "endDate": "2030-05-17T22:00:00+02:00"
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/body/invalid-data",
      "title": "Invalid body data",
      "status": 400,
      "schemaErrors": [
        {
          "jsonPointer": "/bookingAvailability",
          "error": "Property 'waitingListUrl' is not allowed"
        }
      ]
    }
    """

  Scenario: A waiting list url is not allowed on the event level when creating an event
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event met wachtlijst op het eventniveau"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "bookingAvailability": {"type": "Unavailable", "waitingListUrl": "https://publiq.be/wachtlijst"},
      "subEvent": [
        {
          "startDate": "2030-05-17T20:00:00+02:00",
          "endDate": "2030-05-17T22:00:00+02:00"
        }
      ]
    }
    """
    When I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer": "/bookingAvailability",
        "error": "Property 'waitingListUrl' is not allowed"
      }
    ]
    """

  Scenario: A waiting list url is not allowed on the booking availability of a place
    When I set the JSON request payload to:
    """
    {
      "calendarType": "permanent",
      "bookingAvailability": {"type": "Available", "waitingListUrl": "https://publiq.be/wachtlijst"}
    }
    """
    And I send a PUT request to "%{placeUrl}/calendar"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer": "/bookingAvailability",
        "error": "Property 'waitingListUrl' is not allowed"
      }
    ]
    """
