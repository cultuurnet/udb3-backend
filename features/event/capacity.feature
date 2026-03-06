Feature: Test capacity and remainingCapacity on sub-events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"

  Scenario: Set remainingCapacity on a subEvent, type is derived as Available
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingAvailability": {
          "remainingCapacity": 42
        }
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Available"
    }
    """
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {
      "type": "Available",
      "remainingCapacity": 42
    }
    """

  Scenario: Set remainingCapacity to 0 on a subEvent, type is derived as Unavailable
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingAvailability": {
          "remainingCapacity": 0
        }
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Unavailable"
    }
    """
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {
      "type": "Unavailable",
      "remainingCapacity": 0
    }
    """

  Scenario: Set capacity and remainingCapacity on a subEvent
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingAvailability": {
          "capacity": 100,
          "remainingCapacity": 42
        }
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {
      "type": "Available",
      "capacity": 100,
      "remainingCapacity": 42
    }
    """

  Scenario: Set capacity only on a subEvent (explicit type required)
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingAvailability": {
          "type": "Available",
          "capacity": 100
        }
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {
      "type": "Available",
      "capacity": 100
    }
    """

  Scenario: Sending remainingCapacity greater than capacity on a subEvent returns 400
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingAvailability": {
          "capacity": 10,
          "remainingCapacity": 99
        }
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "remainingCapacity must be less than or equal to capacity",
          "jsonPointer": "/0/bookingAvailability/remainingCapacity"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Set capacity via PUT booking-availability
    When I set the JSON request payload to:
    """
    {
      "type": "Available",
      "capacity": 100
    }
    """
    And I send a PUT request to "%{eventUrl}/booking-availability"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Available",
      "capacity": 100
    }
    """

  Scenario: SubEvent type is Available when remainingCapacity=100 (type derivation)
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingAvailability": {
          "remainingCapacity": 100
        }
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "bookingAvailability/type" should be "Available"
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {
      "type": "Available",
      "remainingCapacity": 100
    }
    """

  Scenario: SubEvent type is Available when remainingCapacity=100 even with top-level Unavailable
    Given I set the JSON request payload from "places/place.json"
    And I send a POST request to "/places/"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/event-with-unavailable-sub-events.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "unavailableEventUrl"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingAvailability": {
          "remainingCapacity": 100
        }
      }
    ]
    """
    And I send a PATCH request to "%{unavailableEventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{unavailableEventUrl}"
    And the JSON response at "bookingAvailability/type" should be "Available"
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {
      "type": "Available",
      "remainingCapacity": 100
    }
    """
    And the JSON response at "subEvent/1/bookingAvailability" should be:
    """
    {
      "type": "Unavailable"
    }
    """
