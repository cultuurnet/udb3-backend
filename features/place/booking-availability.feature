Feature: Test place bookingAvailability property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Update booking availability of place
    Given I set the JSON request payload to:
    """
    {"type":"Available"}
    """
    And I send a PUT request to "%{placeUrl}/booking-availability"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/uitdatabank/calendar-type-not-supported",
      "title": "Calendar type not supported",
      "status": 400,
      "detail": "Updating booking availability on calendar type: \"PERMANENT\" is not supported. Only single and multiple calendar types can be updated."
    }
    """

  Scenario: Create a place with top-level booking availability capacity
    Given I set the JSON request payload from "places/place-with-capacity.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "placeWithCapacityUrl"
    And I get the place at "%{placeWithCapacityUrl}"
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Available",
      "capacity": 200
    }
    """

  Scenario: Update a place calendar with top-level booking availability capacity
    Given I set the JSON request payload to:
    """
    {
      "calendarType": "permanent",
      "bookingAvailability": {
        "type": "Available",
        "capacity": 150
      },
      "openingHours": [
        {
          "dayOfWeek": ["monday", "tuesday"],
          "opens": "09:00",
          "closes": "17:00"
        }
      ]
    }
    """
    When I send a PUT request to "%{placeUrl}/calendar"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Available",
      "capacity": 150
    }
    """

  Scenario: Update a periodic place calendar with top-level booking availability capacity
    Given I set the JSON request payload to:
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-01-01T00:00:00+00:00",
      "endDate": "2026-12-31T23:59:59+00:00",
      "bookingAvailability": {
        "type": "Available",
        "capacity": 75
      },
      "openingHours": [
        {
          "dayOfWeek": ["monday", "tuesday"],
          "opens": "09:00",
          "closes": "17:00"
        }
      ]
    }
    """
    When I send a PUT request to "%{placeUrl}/calendar"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Available",
      "capacity": 75
    }
    """

  Scenario: Remove capacity from a place by omitting it on calendar update
    Given I set the JSON request payload to:
    """
    {
      "calendarType": "permanent",
      "bookingAvailability": {
        "type": "Available",
        "capacity": 200
      },
      "openingHours": [
        {
          "dayOfWeek": ["monday"],
          "opens": "09:00",
          "closes": "17:00"
        }
      ]
    }
    """
    And I send a PUT request to "%{placeUrl}/calendar"
    Then the response status should be "204"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "permanent",
      "bookingAvailability": {
        "type": "Available"
      },
      "openingHours": [
        {
          "dayOfWeek": ["monday"],
          "opens": "09:00",
          "closes": "17:00"
        }
      ]
    }
    """
    And I send a PUT request to "%{placeUrl}/calendar"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Available"
    }
    """

  Scenario: Reject a place calendar with negative capacity
    Given I set the JSON request payload to:
    """
    {
      "calendarType": "permanent",
      "bookingAvailability": {
        "type": "Available",
        "capacity": -1
      },
      "openingHours": [
        {
          "dayOfWeek": ["monday"],
          "opens": "09:00",
          "closes": "17:00"
        }
      ]
    }
    """
    When I send a PUT request to "%{placeUrl}/calendar"
    Then the response status should be "400"
    And the JSON response at "type" should be "https://api.publiq.be/probs/body/invalid-data"