Feature: Test setting capacity on top level event via bookingAvailability

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I keep the value of the JSON response at "id" as "placeId"

  Scenario: Set top-level capacity via PUT booking-availability on single calendar event
    Given I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"
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

  Scenario: Set top-level capacity via PUT booking-availability on multiple calendar event
    Given I create an event from "events/event-with-multiple-calendar.json" and save the "url" as "eventUrl"
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

  Scenario: Set top-level capacity via PUT booking-availability on periodic calendar event
    Given I create an event from "events/event-with-periodic-calendar-and-opening-hours.json" and save the "url" as "eventUrl"
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

  Scenario: Set top-level capacity via PUT booking-availability on permanent calendar event
    Given I create an event from "events/event-with-permanent-calendar-and-opening-hours.json" and save the "url" as "eventUrl"
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

  Scenario: Sending remainingCapacity via PUT booking-availability on permanent calendar event returns 400
    Given I create an event from "events/event-with-permanent-calendar-and-opening-hours.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "type": "Available",
      "capacity": 100,
      "remainingCapacity": 42
    }
    """
    And I send a PUT request to "%{eventUrl}/booking-availability"
    Then the response status should be "400"
    And the JSON response at "type" should be "https://api.publiq.be/probs/uitdatabank/remaining-capacity-not-supported"

  Scenario: Set top-level capacity and remainingCapacity via PUT booking-availability on periodic calendar event
    Given I create an event from "events/event-with-periodic-calendar-and-opening-hours.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "type": "Available",
      "capacity": 100,
      "remainingCapacity": 42
    }
    """
    And I send a PUT request to "%{eventUrl}/booking-availability"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Available",
      "capacity": 100,
      "remainingCapacity": 42
    }
    """
