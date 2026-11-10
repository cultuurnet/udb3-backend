Feature: Test SubEvent capacity

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Create a multiple event with capacities
    Given I create an event from "events/sub-event-capacity/event-multiple-with-capacities.json" and save the "url" as "eventUrl"
    When I send a GET request to "%{eventUrl}" with parameters:
      | embedCapacities | true |
    Then the response status should be "200"
    And the JSON response at "subEvent/0/capacity/type" should be "Limited"
    And the JSON response at "subEvent/0/capacity/maximum" should be 30
    And the JSON response at "subEvent/0/capacity/taken" should be 12
    And the JSON response at "subEvent/1/capacity/type" should be "Unlimited"
    And the JSON response should not have "subEvent/1/capacity/maximum"
    And the JSON response at "subEvent/1/capacity/taken" should be 140
    And the JSON response should not have "subEvent/2/capacity"

  Scenario: Capacities are not returned without embedCapacities
    Given I create an event from "events/sub-event-capacity/event-multiple-with-capacities.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response should not have "subEvent/0/capacity"
    And the JSON response should not have "subEvent/1/capacity"

  Scenario: Capacities are not returned to a user who is not allowed to edit the event
    Given I create an event from "events/sub-event-capacity/event-multiple-with-capacities.json" and save the "url" as "eventUrl"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I send a GET request to "%{eventUrl}" with parameters:
      | embedCapacities | true |
    Then the response status should be "200"
    And the JSON response should not have "subEvent/0/capacity"
    And the JSON response should not have "subEvent/1/capacity"

  Scenario: Asking for capacities requires authentication
    Given I create an event from "events/sub-event-capacity/event-multiple-with-capacities.json" and save the "url" as "eventUrl"
    And I am not authorized
    When I send a GET request to "%{eventUrl}" with parameters:
      | embedCapacities | true |
    Then the response status should be "401"

  Scenario: Set, keep and clear a capacity via PATCH subEvents
    Given I create an event from "events/sub-event-capacity/event-multiple-with-capacities.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "capacity": {"type": "Limited", "maximum": 40, "taken": 5}
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    When I send a GET request to "%{eventUrl}" with parameters:
      | embedCapacities | true |
    Then the JSON response at "subEvent/0/capacity/type" should be "Limited"
    And the JSON response at "subEvent/0/capacity/maximum" should be 40
    And the JSON response at "subEvent/0/capacity/taken" should be 5
    And the JSON response at "subEvent/1/capacity/type" should be "Unlimited"
    And the JSON response at "subEvent/1/capacity/taken" should be 140
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "startDate": "2027-07-02T10:00:00+02:00",
        "endDate": "2027-07-02T12:00:00+02:00"
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    When I send a GET request to "%{eventUrl}" with parameters:
      | embedCapacities | true |
    Then the JSON response at "subEvent/0/startDate" should be "2027-07-02T10:00:00+02:00"
    And the JSON response at "subEvent/0/capacity/type" should be "Limited"
    And the JSON response at "subEvent/0/capacity/maximum" should be 40
    And the JSON response at "subEvent/0/capacity/taken" should be 5
    And the JSON response at "subEvent/1/capacity/type" should be "Unlimited"
    And the JSON response at "subEvent/1/capacity/taken" should be 140
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "capacity": {}
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    When I send a GET request to "%{eventUrl}" with parameters:
      | embedCapacities | true |
    Then the JSON response should not have "subEvent/0/capacity"
    And the JSON response at "subEvent/1/capacity/type" should be "Unlimited"
    And the JSON response at "subEvent/1/capacity/taken" should be 140

  Scenario: PUT calendar keeps the capacity of a subEvent with the same dates
    Given I create an event from "events/sub-event-capacity/event-multiple-with-capacities.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "multiple",
      "subEvent": [
        {
          "startDate": "2027-07-01T10:00:00+02:00",
          "endDate": "2027-07-01T12:00:00+02:00"
        },
        {
          "startDate": "2027-07-08T10:00:00+02:00",
          "endDate": "2027-07-08T12:00:00+02:00",
          "capacity": {}
        },
        {
          "startDate": "2027-07-22T10:00:00+02:00",
          "endDate": "2027-07-22T12:00:00+02:00"
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    When I send a GET request to "%{eventUrl}" with parameters:
      | embedCapacities | true |
    Then the JSON response at "subEvent/0/capacity/type" should be "Limited"
    And the JSON response at "subEvent/0/capacity/maximum" should be 30
    And the JSON response at "subEvent/0/capacity/taken" should be 12
    And the JSON response should not have "subEvent/1/capacity"
    And the JSON response should not have "subEvent/2/capacity"

  Scenario: A copy does not take over capacities
    Given I create an event from "events/sub-event-capacity/event-multiple-with-capacities.json" and save the "id" as "eventId"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "multiple",
      "subEvent": [
        {
          "startDate": "2027-08-01T10:00:00+02:00",
          "endDate": "2027-08-01T12:00:00+02:00",
          "capacity": {"type": "Limited", "maximum": 30, "taken": 12}
        },
        {
          "startDate": "2027-08-08T10:00:00+02:00",
          "endDate": "2027-08-08T12:00:00+02:00"
        }
      ]
    }
    """
    And I send a POST request to "/events/%{eventId}/copies"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "copyUrl"
    When I send a GET request to "%{copyUrl}" with parameters:
      | embedCapacities | true |
    Then the response status should be "200"
    And the JSON response should not have "subEvent/0/capacity"
    And the JSON response should not have "subEvent/1/capacity"

  Scenario: Cannot create a periodic event with a capacity
    When I set the JSON request payload from "events/sub-event-capacity/event-periodic-with-capacity.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/capacity"
    And the JSON response at "schemaErrors/0/error" should be "capacity is only allowed when calendarType is single or multiple"

  Scenario: Cannot set a capacity on a permanent calendar via PUT calendar
    Given I create an event from "events/sub-event-capacity/event-single.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "permanent",
      "subEvent": [
        {
          "startDate": "2027-07-01T10:00:00+02:00",
          "endDate": "2027-07-01T12:00:00+02:00",
          "capacity": {"type": "Limited", "maximum": 30}
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/capacity"
    And the JSON response at "schemaErrors/0/error" should be "capacity is only allowed when calendarType is single or multiple"
