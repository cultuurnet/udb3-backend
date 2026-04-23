Feature: Test birthYearRange on events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: New events do not have a birthYearRange
    When I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    Then the response status should be "201"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "birthYearRange"

  Scenario: Set birthYearRange on an event
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "birthYear": "2014-2020" }
        """
    When I send a PUT request to "%{eventUrl}/birthYearRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "birthYearRange" should be "2014-2020"

  Scenario: Set single birth year on an event
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "birthYear": "2018" }
        """
    When I send a PUT request to "%{eventUrl}/birthYearRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "birthYearRange" should be "2018"

  Scenario: Update existing birthYearRange
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "birthYear": "2014-2020" }
        """
    And I send a PUT request to "%{eventUrl}/birthYearRange"
    And I set the JSON request payload to:
        """
        { "birthYear": "2015-2021" }
        """
    When I send a PUT request to "%{eventUrl}/birthYearRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "birthYearRange" should be "2015-2021"

  Scenario: Delete birthYearRange
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "birthYear": "2014-2020" }
        """
    And I send a PUT request to "%{eventUrl}/birthYearRange"
    When I send a DELETE request to "%{eventUrl}/birthYearRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "birthYearRange"

  Scenario: Reject invalid birthYearRange format
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "birthYear": "abc" }
        """
    When I send a PUT request to "%{eventUrl}/birthYearRange"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/birthYear",
        "error":"The string should match pattern: ^\\d{4}(-\\d{4})?$"
      }
    ]
    """

  Scenario: Reject birthYearRange where from is greater than to
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "birthYear": "2020-2014" }
        """
    When I send a PUT request to "%{eventUrl}/birthYearRange"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/birthYear",
        "error":"\"From\" birth year should not be greater than the \"to\" birth year."
      }
    ]
    """
