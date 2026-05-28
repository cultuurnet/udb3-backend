Feature: Test birthdateRange on events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: By defaults events should not have a birthdateRange
    When I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    Then the response status should be "201"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "birthdateRange"

  Scenario: Set birthdateRange on an event
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "from": "2014-01-01", "to": "2020-12-31" }
        """
    When I send a PUT request to "%{eventUrl}/birthdate-range"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "birthdateRange/from" should be "2014-01-01"
    And the JSON response at "birthdateRange/to" should be "2020-12-31"

  Scenario: Update existing birthdateRange
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "from": "2014-01-01", "to": "2020-12-31" }
        """
    And I send a PUT request to "%{eventUrl}/birthdate-range"
    And I set the JSON request payload to:
        """
        { "from": "2015-01-01", "to": "2021-12-31" }
        """
    When I send a PUT request to "%{eventUrl}/birthdate-range"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "birthdateRange/from" should be "2015-01-01"
    And the JSON response at "birthdateRange/to" should be "2021-12-31"

  Scenario: Delete birthdateRange
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "from": "2014-01-01", "to": "2020-12-31" }
        """
    And I send a PUT request to "%{eventUrl}/birthdate-range"
    When I send a DELETE request to "%{eventUrl}/birthdate-range"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "birthdateRange"

  Scenario: Reject birthdateRange where from is greater than to
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "from": "2020-12-31", "to": "2014-01-01" }
        """
    When I send a PUT request to "%{eventUrl}/birthdate-range"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer": "\/birthdateRange",
        "error": "\"From\" birthdate should not be greater than the \"to\" birthdate."
      }
    ]
    """

  Scenario: Reject birthdateRange where the dates are not in correctly formatted strings
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "from": 1609372800, "to": "1 January 2024" }
        """
    When I send a PUT request to "%{eventUrl}/birthdate-range"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer": "\/from",
        "error": "The data (integer) must match the type: string"
      },
      {
        "jsonPointer": "\/to",
        "error": "The data must match the 'date' format"
      }
    ]
    """

  Scenario: Reject birthdateRange where to is missing
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "from": "2020-12-31" }
        """
    When I send a PUT request to "%{eventUrl}/birthdate-range"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer": "\/",
        "error": "The required properties (to) are missing"
      }
    ]
    """

  Scenario: Reject birthdateRange where from is missing
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        { "to": "2024-12-31" }
        """
    When I send a PUT request to "%{eventUrl}/birthdate-range"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer": "\/",
        "error": "The required properties (from) are missing"
      }
    ]
    """
