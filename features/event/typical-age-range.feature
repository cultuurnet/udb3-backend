Feature: Test the UDB3 events API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Events have a default typicalAgeRange of all ages
    When I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    Then the response status should be "201"
    And I get the event at "%{eventUrl}"
    And the JSON response at "typicalAgeRange" should be "-"

  Scenario: Update with - as all ages
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "typicalAgeRange": "-" }
        """
    When I send a PUT request to "%{eventUrl}/typicalAgeRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "typicalAgeRange" should be "-"

  Scenario: Update with 0- as all ages
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "typicalAgeRange": "0-" }
        """
    When I send a PUT request to "%{eventUrl}/typicalAgeRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "typicalAgeRange" should be "-"

  Scenario: Update and delete event typical age range
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "typicalAgeRange": "1-12" }
        """
    When I send a PUT request to "%{eventUrl}/typicalAgeRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "typicalAgeRange" should be "1-12"

    When I send a DELETE request to "%{eventUrl}/typicalAgeRange"
    Then the response status should be "204"
    And I send a GET request to "%{eventUrl}"
    And the JSON response at "typicalAgeRange" should be "-"

  Scenario: Update and delete event typical age range via legacy endpoint
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "typicalAgeRange": "1-12" }
        """
    When I send a POST request to "%{eventUrl}/typicalAgeRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "typicalAgeRange" should be "1-12"

    When I send a DELETE request to "%{eventUrl}/typicalAgeRange"
    Then the response status should be "204"
    And I send a GET request to "%{eventUrl}"
    And the JSON response at "typicalAgeRange" should be "-"

  Scenario: When the request body is invalid an error is returned
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "typicalAgeRange": "" }
        """
    When I send a POST request to "%{eventUrl}/typicalAgeRange"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/typicalAgeRange",
        "error":"The string should match pattern: ^[\\d]*-[\\d]*$"
      }
    ]
    """
    And I get the event at "%{eventUrl}"
    And the JSON response at "typicalAgeRange" should be "-"

  Scenario: When the minimum age is bigger than the maximum age an error is returned
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "typicalAgeRange": "12-6" }
        """
    When I send a POST request to "%{eventUrl}/typicalAgeRange"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/typicalAgeRange",
        "error":"\"From\" age should not be greater than the \"to\" age."
      }
    ]
    """
    And I get the event at "%{eventUrl}"
    And the JSON response at "typicalAgeRange" should be "-"
