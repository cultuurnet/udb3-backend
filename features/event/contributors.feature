Feature: Test the UDB3 events contributors endpoint

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Events have no contributors by default
    When I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    Then the response status should be "201"
    And I send a GET request to "%{eventUrl}/contributors"
    Then the JSON response should be:
    """
    []
    """

  Scenario: Update contributors
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    When I send a PUT request to "%{eventUrl}/contributors"
    Then the response status should be "204"
    And I send a GET request to "%{eventUrl}/contributors"
    Then the JSON response should be:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """

  Scenario: Delete all contributors
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    And I set the JSON request payload to:
    """
    []
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    Then the response status should be "204"
    And I send a GET request to "%{eventUrl}/contributors"
    Then the JSON response should be:
    """
    []
    """

  Scenario: Contributors should be visible in the JSON projection if you are authenticated and have the necessary permission
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    And the response status should be "204"
    When I get the event at "%{eventUrl}?embedContributors=true"
    Then the JSON response at "contributors" should be:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-5388
  Scenario: Contributors should not be saved in the JSON projection
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    And the response status should be "204"
    And I send a PUT request to "%{eventUrl}/labels/randomLabel"
    And I am authorized as JWT provider v1 user "invoerder_lgm"
    When I get the event at "%{eventUrl}"
    Then the JSON response should not have "contributors"

  Scenario: Contributors should not be visible in the JSON projection if you are authenticated and don't have the necessary permission
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    And the response status should be "204"
    And I am authorized as JWT provider v1 user "invoerder_lgm"
    When I get the event at "%{eventUrl}"
    Then the JSON response should not have "contributors"

  Scenario: Contributors should not be visible in the JSON projection if you are anonymous
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    And the response status should be "204"
    And I am not authorized
    When I get the event at "%{eventUrl}"
    Then the JSON response should not have "contributors"

  Scenario: Overwrite all contributors
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    And I set the JSON request payload to:
    """
    [
      "new_user@example.com",
      "extra_information@example.com"
    ]
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    And I send a GET request to "%{eventUrl}/contributors"
    Then the JSON response should be:
    """
    [
      "new_user@example.com",
      "extra_information@example.com"
    ]
    """

  Scenario: Invalid emails are rejected
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    And I set the JSON request payload to:
    """
    [
      "09/1231212",
      "extra_information@example.com"
    ]
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/0",
        "error":"The data must match the 'email' format"
      }
    ]
    """
    And I send a GET request to "%{eventUrl}/contributors"
    Then the JSON response should be:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """

  Scenario: Users should not be allowed to view contributors of other events
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    And I am authorized as JWT provider v1 user "invoerder_lgm"
    And I send a GET request to "%{eventUrl}/contributors"
    Then the response status should be "403"
    And the JSON response at "detail" should include 'has no permission "Aanbod bewerken" on resource'

  Scenario: Users should be able to view contributors when they are a contributor
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "stan.vertessen+DFM@cultuurnet.be",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    And I am authorized as JWT provider v1 user "invoerder_dfm"
    And I send a GET request to "%{eventUrl}/contributors"
    Then the response status should be "200"
    And the JSON response should be:
    """
    [
      "stan.vertessen+DFM@cultuurnet.be",
      "information@example.com"
    ]
    """

  Scenario: Users should be able to edit events when they are a contributor
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "stan.vertessen+DFM@cultuurnet.be",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    And I am authorized as JWT provider v1 user "invoerder_dfm"
    And I set the JSON request payload to:
    """
    {
      "name": "Contributor updated title"
    }
    """
    And I send a PUT request to "%{eventUrl}/name/nl"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "name/nl" should be "Contributor updated title"
