Feature: Test that the removed capacity properties on bookingAvailability are ignored

  The capacity and remainingCapacity properties were documented and accepted between March and August 2026.
  Since their removal they are silently ignored, so integrators that still send them keep getting a successful response.

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"

  Scenario: Capacity on the top level bookingAvailability endpoint is ignored
    Given I set the JSON request payload to:
    """
    {"type": "Unavailable", "capacity": 100}
    """
    When I send a PUT request to "%{eventUrl}/booking-availability"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "bookingAvailability" should be:
    """
    {"type": "Unavailable"}
    """

  Scenario: Capacity on the top level bookingAvailability of a calendar update is ignored
    Given I set the JSON request payload to:
    """
    {
      "calendarType": "single",
      "bookingAvailability": {"type": "Unavailable", "capacity": 100},
      "subEvent": [
        {
          "startDate": "2021-05-17T08:00:00+00:00",
          "endDate": "2021-05-18T22:00:00+00:00"
        }
      ]
    }
    """
    When I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "bookingAvailability" should be:
    """
    {"type": "Unavailable"}
    """

  Scenario: Capacity and remainingCapacity on a subEvent bookingAvailability are ignored
    Given I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingAvailability": {"type": "Unavailable", "capacity": 100, "remainingCapacity": 0}
      }
    ]
    """
    When I send a PATCH request to "%{eventUrl}/sub-events"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability" should be:
    """
    {"type": "Unavailable"}
    """
