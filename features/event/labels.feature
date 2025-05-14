Feature: Test labelling events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Create event with only public labels
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/labels/event-with-public-labels.json"
    When I send a POST request to "/imports/events/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the JSON response at "labels" should be:
    """
    [ "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """

  Scenario: Create event with public and private labels
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/labels/event-with-public-and-private-labels.json"
    When I send a POST request to "/imports/events/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the JSON response at "labels" should be:
    """
    [ "public-visible", "private-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible", "private-invisible" ]
    """

  Scenario: Create event with public and forbidden private labels for user
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I am authorized as JWT provider v1 user "validator_scherpenheuvel"

    Given I set the JSON request payload from "events/labels/event-with-public-and-private-labels.json"
    When I send a POST request to "/imports/events/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the JSON response at "labels" should be:
    """
    [ "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """

  Scenario: Update event by adding public labels
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/labels/event-without-labels.json"
    When I send a POST request to "/imports/events/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the JSON response should not have "labels"
    And the JSON response should not have "hiddenLabels"

    Given I set the JSON request payload from "events/labels/event-with-public-labels.json"
    When I send a PUT request to "/imports/events/%{eventId}"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the JSON response at "labels" should be:
    """
    [ "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """

  Scenario: Update event by removing public labels
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/labels/event-with-public-labels.json"
    When I send a POST request to "/imports/events/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the JSON response at "labels" should be:
    """
    [ "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """

    Given I set the JSON request payload from "events/labels/event-without-labels.json"
    When I send a PUT request to "/imports/events/%{eventId}"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the JSON response should not have "labels"
    And the JSON response should not have "hiddenLabels"

  Scenario: Prevent removing labels added via UI when updating via complete overwrite
    Given I am authorized as JWT provider v1 user "validator_scherpenheuvel"

    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/labels/event-without-labels.json"
    When I send a POST request to "/imports/events/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a PUT request to "/events/%{eventId}/labels/udb3label"
    Then the response status should be "204"
    When I send a PUT request to "/events/%{eventId}/labels/public-visible"
    Then the response status should be "204"
    And I send a PUT request to "/events/%{eventId}/labels/public-invisible"
    Then the response status should be "204"
    And I send a GET request to "/events/%{eventId}"
    Then the JSON response at "labels" should be:
    """
    [ "udb3label", "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """

    Given I set the JSON request payload from "events/labels/event-without-labels.json"
    When I send a PUT request to "/imports/events/%{eventId}"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the JSON response at "labels" should be:
    """
    [ "udb3label", "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """

  Scenario: Prevent removing private labels added via UI that are forbidden for user when updating via complete overwrite
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/labels/event-without-labels.json"
    When I send a POST request to "/imports/events/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a PUT request to "/events/%{eventId}/labels/public-visible"
    Then the response status should be "204"
    When I send a PUT request to "/events/%{eventId}/labels/private-visible"
    Then the response status should be "204"
    And I send a PUT request to "/events/%{eventId}/labels/public-invisible"
    Then the response status should be "204"
    And I send a PUT request to "/events/%{eventId}/labels/private-invisible"
    Then the response status should be "204"
    And I send a GET request to "/events/%{eventId}"
    Then the JSON response at "labels" should be:
    """
    [ "public-visible", "private-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible", "private-invisible" ]
    """

    Given I am authorized as JWT provider v1 user "validator_scherpenheuvel"

    Given I set the JSON request payload from "events/labels/event-without-labels.json"
    When I send a PUT request to "/imports/events/%{eventId}"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the JSON response at "labels" should be:
    """
    [ "public-visible", "private-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible", "private-invisible" ]
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4652
  Scenario: Create event with wrong invisible label that get resets to visible
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/labels/event-with-wrong-invisible-label.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the JSON response at "labels" should be:
    """
    ["public-visible"]
    """
    And the JSON response should not have "hiddenLabels"

  @bugfix # https://jira.uitdatabank.be/browse/III-4652
  Scenario: Create event with wrong visible label that get resets to invisible
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/labels/event-with-wrong-visible-label.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the JSON response should not have "labels"
    And the JSON response at "hiddenLabels" should be:
    """
    ["public-invisible"]
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4652
  Scenario: Create event with new visible label
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I create a random name of 10 characters
    When I set the JSON request payload from "events/labels/event-with-new-visible-label.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the JSON response at "labels" should be:
    """
    [ "%{name}"]
    """
    And the JSON response should not have "hiddenLabels"

  @bugfix # https://jira.uitdatabank.be/browse/III-4652
  Scenario: Create event with new invisible
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I create a random name of 10 characters
    Given I set the JSON request payload from "events/labels/event-with-new-invisible-label.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the JSON response should not have "labels"
    And the JSON response at "hiddenLabels" should be:
    """
    ["%{name}"]
    """

  Scenario: Create event and add a label via default endpoint
    Given I create a random name of 10 characters
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create a minimal permanent event and save the "id" as "eventId"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I send a PUT request to "/events/%{eventId}/labels/%{name}"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "labels" should be:
    """
    ["%{name}"]
    """

  Scenario: Create event and delete a label of the event
    Given I create a random name of 10 characters
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create a minimal permanent event and save the "id" as "eventId"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I send a PUT request to "/events/%{eventId}/labels/%{name}"
    When I send a DELETE request to "/events/%{eventId}/labels/%{name}"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "labels"

  Scenario: Create event and add a label via legacy endpoint
    Given I create a random name of 10 characters
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create a minimal permanent event and save the "id" as "eventId"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
	  "label": "%{name}"
    }
    """
    And I send a POST request to "/events/%{eventId}/labels/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "labels" should be:
    """
    ["%{name}"]
    """

  Scenario: Bulk update labels on an event with initially no labels
    Given I create a minimal place and save the "url" as "placeUrl"
    And the response status should be "201"
    And I create a minimal permanent event and save the "id" as "eventId"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And the response status should be "201"
    When I set the JSON request payload to:
    """
    {
      "labels": [
        "public-visible",
        "private-visible",
        "public-invisible",
        "private-invisible"
      ]
    }
    """
    And I send a PUT request to "/events/%{eventId}/labels/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "labels" should be:
    """
    ["public-visible", "private-visible"]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    ["public-invisible", "private-invisible"]
    """

  Scenario: Bulk update labels on an event with initial labels
    Given I create a minimal place and save the "url" as "placeUrl"
    And the response status should be "201"
    And I create a minimal permanent event and save the "id" as "eventId"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I create a random name of 10 characters
    And I send a PUT request to "/events/%{eventId}/labels/%{name}"
    Then the response status should be "204"
    When I set the JSON request payload to:
    """
    {
      "labels": [
        "public-visible",
        "private-visible",
        "public-invisible",
        "private-invisible"
      ]
    }
    """
    And I send a PUT request to "/events/%{eventId}/labels/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "labels" should be:
    """
    ["%{name}", "public-visible", "private-visible"]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    ["public-invisible", "private-invisible"]
    """
