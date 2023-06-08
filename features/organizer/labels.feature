@api @organizers
Feature: Test organizer labels property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal organizer and save the "url" as "organizerUrl"

  Scenario: Update an organizer's labels with incorrect visibility via complete overwrite
    When I set the JSON request payload to:
    """
    {
      "mainLanguage":"nl",
      "name": {
        "nl": "%{name}"
      },
      "url": "https://www.%{name}.be",
      "labels": ["public-invisible"],
      "hiddenLabels": ["public-visible"]
    }
    """
    And I update the organizer at "%{organizerUrl}"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "labels" should be:
    """
    [ "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """

  Scenario: Update an organizer's labels with forbidden visible label via complete overwrite
    Given I am authorized as JWT provider v1 user "validator_diest"
    And I create a minimal organizer and save the "url" as "organizerUrl"
    When I set the JSON request payload to:
    """
    {
      "mainLanguage":"nl",
      "name": {
        "nl": "%{name}"
      },
      "url": "https://www.%{name}.be",
      "labels": ["public-visible","private-visible"],
      "hiddenLabels": ["public-invisible"]
    }
    """
    And I update the organizer at "%{organizerUrl}"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "labels" should be:
    """
    [ "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """

  Scenario: Update an organizer's labels with forbidden invisible label via complete overwrite
    Given I am authorized as JWT provider v1 user "validator_diest"
    And I create a minimal organizer and save the "url" as "organizerUrl"
    When I set the JSON request payload to:
    """
    {
      "mainLanguage":"nl",
      "name": {
        "nl": "%{name}"
      },
      "url": "https://www.%{name}.be",
      "labels": ["public-visible"],
      "hiddenLabels": ["public-invisible","private-invisible"]
    }
    """
    And I update the organizer at "%{organizerUrl}"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "labels" should be:
    """
    [ "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """

  Scenario: Update an organizer's labels via complete overwrite including private labels that it already has
    Given I am authorized as JWT provider v1 user "validator_diest"
    And I create a minimal organizer and save the "url" as "organizerUrl"

    Given I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send a PUT request to "%{organizerUrl}/labels/private-visible"
    And the response status should be "204"
    And I send a PUT request to "%{organizerUrl}/labels/private-invisible"
    And the response status should be "204"
    And I get the organizer at "%{organizerUrl}"

    Then the JSON response at "labels" should be:
    """
    [ "private-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "private-invisible" ]
    """

    Given I am authorized as JWT provider v1 user "validator_diest"
    When I set the JSON request payload to:
    """
    {
      "mainLanguage":"nl",
      "name": {
        "nl": "%{name}"
      },
      "url": "https://www.%{name}.be",
      "labels": ["private-visible", "public-visible"],
      "hiddenLabels": ["private-invisible", "public-invisible"]
    }
    """
    And I update the organizer at "%{organizerUrl}"
    And I get the organizer at "%{organizerUrl}"

    Then the JSON response at "labels" should be:
    """
    [ "private-visible", "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "private-invisible", "public-invisible" ]
    """

  Scenario: Update an organizer's labels via complete overwrite without removing private labels that it already has
    Given I am authorized as JWT provider v1 user "validator_diest"
    And I create a minimal organizer and save the "url" as "organizerUrl"

    Given I am authorized as JWT provider v1 user "centraal_beheerder"
    When I set the JSON request payload to:
    """
    {
      "mainLanguage":"nl",
      "name": {
        "nl": "%{name}"
      },
      "url": "https://www.%{name}.be",
      "labels": ["private-visible"],
      "hiddenLabels": ["private-invisible"]
    }
    """
    And I update the organizer at "%{organizerUrl}"
    And I get the organizer at "%{organizerUrl}"

    Then the JSON response at "labels" should be:
    """
    [ "private-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "private-invisible" ]
    """

    Given I am authorized as JWT provider v1 user "validator_diest"
    When I set the JSON request payload to:
    """
    {
      "mainLanguage":"nl",
      "name": {
        "nl": "%{name}"
      },
      "url": "https://www.%{name}.be",
      "labels": ["public-visible"],
      "hiddenLabels": ["public-invisible"]
    }
    """
    And I update the organizer at "%{organizerUrl}"
    And I get the organizer at "%{organizerUrl}"

    Then the JSON response at "labels" should be:
    """
    [ "private-visible", "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "private-invisible", "public-invisible" ]
    """

  Scenario: Remove an organizer's private labels via complete overwrite if user has permission to do so
    Given I create a random name of 10 characters
    When I set the JSON request payload to:
    """
    {
      "mainLanguage":"nl",
      "name": {
        "nl": "%{name}"
      },
      "url": "https://www.%{name}.be",
      "labels": ["private-visible"],
      "hiddenLabels": ["private-invisible"]
    }
    """
    And I create an organizer and save the "url" as "organizerUrl"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "labels" should be:
    """
    [ "private-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "private-invisible" ]
    """

    When I set the JSON request payload to:
    """
    {
      "mainLanguage":"nl",
      "name": {
        "nl": "%{name}"
      },
      "url": "https://www.%{name}.be",
      "labels": ["public-visible"],
      "hiddenLabels": ["public-invisible"]
    }
    """
    And I update the organizer at "%{organizerUrl}"

    When I get the organizer at "%{organizerUrl}"
    And the JSON response at "labels" should be:
    """
    [ "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """

  Scenario: Update an organizer's labels via complete overwrite without removing labels added previously via labels endpoint
    When I create a minimal organizer and save the "url" as "organizerUrl"

    When I send a PUT request to "%{organizerUrl}/labels/udb3Label"
    Then the response status should be "204"

    When I get the organizer at "%{organizerUrl}"
    And the JSON response at "labels" should be:
    """
    [ "udb3Label" ]
    """

    When I update the organizer at "%{organizerUrl}" from "organizers/organizer.json"
    And I get the organizer at "%{organizerUrl}"

    Then the response status should be "200"
    And the JSON response at "labels" should be:
    """
    [ "udb3Label", "public-visible" ]
    """

  Scenario: Add a label to an organizer and then delete it via labels endpoint
    When I create a random name of 10 characters
    And I send a PUT request to "%{organizerUrl}/labels/%{name}"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "labels" should be:
    """
    [ "%{name}" ]
    """
    When I send a DELETE request to "%{organizerUrl}/labels/%{name}"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response should not have "labels"

  Scenario: Add an invalid label to an organizer via labels endpoint
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    When I send a PUT request to "%{organizerUrl}/labels/Invalid;Label"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/url/not-found",
     "title": "Not Found",
     "status": 404,
     "detail": "The label should match pattern: ^[^;]{2,255}$"
    }
    """

  Scenario: Remove an invalid label via labels endpoint
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    When I send a DELETE request to "%{organizerUrl}/labels/invalid;label"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/url/not-found",
     "title": "Not Found",
     "status": 404,
     "detail": "The label should match pattern: ^[^;]{2,255}$"
    }
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4652
  Scenario: Create organizer with wrong invisible label that get resets to visible
    Given I create an organizer from "organizers/labels/organizer-with-wrong-invisible-label.json" and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "labels" should be:
    """
    ["public-visible"]
    """
    And the JSON response should not have "hiddenLabels"

  @bugfix # https://jira.uitdatabank.be/browse/III-4652
  Scenario: Create organizer with wrong visible label that get resets to invisible
    Given I create an organizer from "organizers/labels/organizer-with-wrong-visible-label.json" and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response should not have "labels"
    And the JSON response at "hiddenLabels" should be:
    """
    ["public-invisible"]
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4652
  Scenario: Create organizer with wrong invisible label
    Given I create an organizer from "organizers/labels/organizer-with-new-visible-label.json" and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    And the JSON response at "labels" should be:
    """
    [ "%{name}"]
    """
    And the JSON response should not have "hiddenLabels"

  @bugfix # https://jira.uitdatabank.be/browse/III-4652
  Scenario: Create organizer with new invisible label
    Given I create an organizer from "organizers/labels/organizer-with-new-invisible-label.json" and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    And the JSON response should not have "labels"
    And the JSON response at "hiddenLabels" should be:
    """
    [ "%{name}"]
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4592
  Scenario: Create organizer with duplicate label in labels and hiddenLabels
    When I create a random name of 10 characters
    And I set the JSON request payload from "organizers/labels/organizer-with-duplicate-label-in-labels-and-hiddenLabels.json"
    And I send a POST request to "/organizers"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/body/invalid-data",
      "title": "Invalid body data",
      "status": 400,
      "schemaErrors": [
        {
          "jsonPointer": "/labels/0",
          "error": "Label \"%{name}\" cannot be both in labels and hiddenLabels properties."
        }
      ]
    }
    """
