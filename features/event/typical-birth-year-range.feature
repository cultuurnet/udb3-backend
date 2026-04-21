Feature: Test typicalBirthYearRange on events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: New events do not have a typicalBirthYearRange
    When I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    Then the response status should be "201"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "typicalBirthYearRange"

  Scenario: Set typicalBirthYearRange on an event
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "typicalBirthYearRange": "2014-2020" }
        """
    When I send a PUT request to "%{eventUrl}/typicalBirthYearRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "typicalBirthYearRange" should be "2014-2020"

  Scenario: Update existing typicalBirthYearRange
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "typicalBirthYearRange": "2014-2020" }
        """
    And I send a PUT request to "%{eventUrl}/typicalBirthYearRange"
    And I set the JSON request payload to:
        """
        { "typicalBirthYearRange": "2015-2021" }
        """
    When I send a PUT request to "%{eventUrl}/typicalBirthYearRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "typicalBirthYearRange" should be "2015-2021"

  Scenario: Delete typicalBirthYearRange
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "typicalBirthYearRange": "2014-2020" }
        """
    And I send a PUT request to "%{eventUrl}/typicalBirthYearRange"
    When I send a DELETE request to "%{eventUrl}/typicalBirthYearRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "typicalBirthYearRange"

  Scenario: Set typicalBirthYearRange with open range
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "typicalBirthYearRange": "2014-" }
        """
    When I send a PUT request to "%{eventUrl}/typicalBirthYearRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "typicalBirthYearRange" should be "2014-"

  Scenario: Reject invalid typicalBirthYearRange format
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "typicalBirthYearRange": "abc" }
        """
    When I send a PUT request to "%{eventUrl}/typicalBirthYearRange"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/typicalBirthYearRange",
        "error":"The string should match pattern: ^[\\d]*-[\\d]*$"
      }
    ]
    """

  Scenario: Reject typicalBirthYearRange where from is greater than to
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "typicalBirthYearRange": "2020-2014" }
        """
    When I send a PUT request to "%{eventUrl}/typicalBirthYearRange"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/typicalBirthYearRange",
        "error":"\"From\" birth year should not be greater than the \"to\" birth year."
      }
    ]
    """
