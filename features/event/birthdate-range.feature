Feature: Test birthdateRange on events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: New events do not have a birthdateRange
    When I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    Then the response status should be "201"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "birthdateRange"

  Scenario: Set birthdateRange on an event
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "birthdateRange": { "from": "2014-01-01", "to": "2020-12-31" } }
        """
    When I send a PUT request to "%{eventUrl}/birthdateRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "birthdateRange/from" should be "2014-01-01"
    And the JSON response at "birthdateRange/to" should be "2020-12-31"

  Scenario: Update existing birthdateRange
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "birthdateRange": { "from": "2014-01-01", "to": "2020-12-31" } }
        """
    And I send a PUT request to "%{eventUrl}/birthdateRange"
    And I set the JSON request payload to:
        """
        { "birthdateRange": { "from": "2015-01-01", "to": "2021-12-31" } }
        """
    When I send a PUT request to "%{eventUrl}/birthdateRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "birthdateRange/from" should be "2015-01-01"
    And the JSON response at "birthdateRange/to" should be "2021-12-31"

  Scenario: Delete birthdateRange
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "birthdateRange": { "from": "2014-01-01", "to": "2020-12-31" } }
        """
    And I send a PUT request to "%{eventUrl}/birthdateRange"
    When I send a DELETE request to "%{eventUrl}/birthdateRange"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "birthdateRange"

  Scenario: Reject birthdateRange where from is greater than to
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "birthdateRange": { "from": "2020-12-31", "to": "2014-01-01" } }
        """
    When I send a PUT request to "%{eventUrl}/birthdateRange"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/birthdateRange",
        "error":"\"From\" birthdate should not be greater than the \"to\" birthdate."
      }
    ]
    """
