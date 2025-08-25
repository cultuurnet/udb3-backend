Feature: Test replace labels for events endpoint

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "placeUrl"

  Scenario: Replace labels as normal user on an event without initial labels
    Given I am authorized as JWT provider v2 user "validator_diest"
    And I create a minimal permanent event and save the "url" as "eventUrl"
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
    And I send a PUT request to "%{eventUrl}/labels/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "labels" should be:
    """
    ["public-visible"]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    ["public-invisible"]
    """

  Scenario: Replace labels as admin on an event without initial labels
    And I create a minimal permanent event and save the "url" as "eventUrl"
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
    And I send a PUT request to "%{eventUrl}/labels/"
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

  Scenario: Replace initial manual labels as normal user on an event
    Given I am authorized as JWT provider v2 user "validator_diest"
    And I create a minimal permanent event and save the "url" as "eventUrl"
    And I create a random name of 10 characters and keep it as "label1"
    And I send a PUT request to "%{eventUrl}/labels/%{label1}"
    And I create a random name of 10 characters and keep it as "label2"
    And I send a PUT request to "%{eventUrl}/labels/%{label2}"
    And I create a random name of 10 characters and keep it as "label3"
    And I send a PUT request to "%{eventUrl}/labels/%{label3}"
    When I set the JSON request payload to:
    """
    {
      "labels": [
        "public-visible",
        "private-visible",
        "public-invisible",
        "private-invisible",
        "%{label3}"
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/labels/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "labels" should be:
    """
    ["%{label3}", "public-visible"]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    ["public-invisible"]
    """

  Scenario: Replace initial manual labels but keep private labels as normal user on an event
    Given I am authorized as JWT provider v2 user "validator_diest"
    And I create a minimal permanent event and save the "url" as "eventUrl"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send a PUT request to "%{eventUrl}/labels/private-visible"
    And I send a PUT request to "%{eventUrl}/labels/private-invisible"
    And I create a random name of 10 characters and keep it as "label1"
    And I send a PUT request to "%{eventUrl}/labels/%{label1}"
    And I am authorized as JWT provider v2 user "validator_diest"
    When I set the JSON request payload to:
    """
    {
      "labels": [
        "public-visible",
        "public-invisible",
        "%{label1}"
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/labels/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "labels" should be:
    """
    ["private-visible", "%{label1}", "public-visible"]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    ["private-invisible", "public-invisible"]
    """

  Scenario: Remove all initial manual labels as normal user on an event
    Given I am authorized as JWT provider v2 user "validator_diest"
    And I create a minimal permanent event and save the "url" as "eventUrl"
    And I create a random name of 10 characters and keep it as "label1"
    And I send a PUT request to "%{eventUrl}/labels/%{label1}"
    And I create a random name of 10 characters and keep it as "label2"
    And I send a PUT request to "%{eventUrl}/labels/%{label2}"
    And I create a random name of 10 characters and keep it as "label3"
    And I send a PUT request to "%{eventUrl}/labels/%{label3}"
    When I set the JSON request payload to:
    """
    {
      "labels": [
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/labels/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "labels"
    And the JSON response should not have "hiddenLabels"
