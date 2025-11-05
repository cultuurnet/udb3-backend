Feature: Test labelling places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Update the labels of a place with incorrect visibility via complete overwrite
    Given I create a place from "places/place-with-required-fields.json" and save the "url" as "placeUrl"
    When I set the JSON request payload to:
    """
    {
      "name": {
        "nl": "Cafe Den Hemel"
      },
      "terms": [
        {
          "id": "Yf4aZBfsUEu2NsQqsprngw"
        }
      ],
      "address": {
        "nl" : {
          "addressCountry": "BE",
          "addressLocality": "Scherpenheuvel-Zichem",
          "postalCode": "3271",
          "streetAddress": "Hoornblaas 107"
        }
      },
      "calendarType": "permanent",
      "mainLanguage": "nl",
      "labels": ["public-invisible"],
      "hiddenLabels": ["public-visible"]
    }
    """
    And I update the place at "%{placeUrl}"
    When I get the place at "%{placeUrl}"
    And the JSON response at "labels" should be:
    """
    [ "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """

  Scenario: Ignore a new forbidden private visible or hidden label on a place when updating via complete overwrite
    Given I am authorized as JWT provider user "validator_scherpenheuvel"
    And I create a place from "places/place-with-required-fields.json" and save the "url" as "placeUrl"
    When I set the JSON request payload to:
    """
    {
      "name": {
        "nl": "Cafe Den Hemel"
      },
      "terms": [
        {
          "id": "Yf4aZBfsUEu2NsQqsprngw"
        }
      ],
      "address": {
        "nl" : {
          "addressCountry": "BE",
          "addressLocality": "Scherpenheuvel-Zichem",
          "postalCode": "3271",
          "streetAddress": "Hoornblaas 107"
        }
      },
      "calendarType": "permanent",
      "mainLanguage": "nl",
      "labels": ["public-visible", "private-visible"],
      "hiddenLabels": ["public-invisible","private-invisible"]
    }
    """
    And I update the place at "%{placeUrl}"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "labels" should be:
    """
    [ "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """

  Scenario: Update private labels added by UI on a place via complete overwrite
    Given I am authorized as JWT provider user "validator_scherpenheuvel"
    And I create a place from "places/place-with-required-fields.json" and save the "url" as "placeUrl"
    And I am authorized as JWT provider user "centraal_beheerder"
    When I send a PUT request to "%{placeUrl}/labels/private-visible"
    Then the response status should be "204"
    When I send a PUT request to "%{placeUrl}/labels/private-invisible"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "labels" should be:
    """
    [ "private-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "private-invisible" ]
    """
    Given I am authorized as JWT provider user "validator_scherpenheuvel"
    When I set the JSON request payload to:
    """
    {
      "name": {
        "nl": "Cafe Den Hemel"
      },
      "terms": [
        {
          "id": "Yf4aZBfsUEu2NsQqsprngw"
        }
      ],
      "address": {
        "nl" : {
          "addressCountry": "BE",
          "addressLocality": "Scherpenheuvel-Zichem",
          "postalCode": "3271",
          "streetAddress": "Hoornblaas 107"
        }
      },
      "calendarType": "permanent",
      "mainLanguage": "nl",
      "labels": ["public-visible", "private-visible"],
      "hiddenLabels": ["public-invisible","private-invisible"]
    }
    """
    And I update the place at "%{placeUrl}"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "labels" should be:
    """
    [ "private-visible", "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [  "private-invisible", "public-invisible" ]
    """

  Scenario: Update private labels added by UI on a place via complete overwrite and avoid remove
    Given I am authorized as JWT provider user "validator_scherpenheuvel"
    And I create a place from "places/place-with-required-fields.json" and save the "url" as "placeUrl"
    And I am authorized as JWT provider user "centraal_beheerder"
    When I send a PUT request to "%{placeUrl}/labels/private-visible"
    Then the response status should be "204"
    When I send a PUT request to "%{placeUrl}/labels/private-invisible"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "labels" should be:
    """
    [ "private-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "private-invisible" ]
    """
    Given I am authorized as JWT provider user "validator_scherpenheuvel"
    When I set the JSON request payload to:
    """
    {
      "name": {
        "nl": "Cafe Den Hemel"
      },
      "terms": [
        {
          "id": "Yf4aZBfsUEu2NsQqsprngw"
        }
      ],
      "address": {
        "nl" : {
          "addressCountry": "BE",
          "addressLocality": "Scherpenheuvel-Zichem",
          "postalCode": "3271",
          "streetAddress": "Hoornblaas 107"
        }
      },
      "calendarType": "permanent",
      "mainLanguage": "nl",
      "labels": ["public-visible"],
      "hiddenLabels": ["public-invisible"]
    }
    """
    And I update the place at "%{placeUrl}"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "labels" should be:
    """
    [ "private-visible", "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [  "private-invisible", "public-invisible" ]
    """

  Scenario: Remove private labels added by UI on a place via complete overwrite
    Given I am authorized as JWT provider user "centraal_beheerder"
    When I set the JSON request payload to:
    """
    {
      "name": {
        "nl": "Cafe Den Hemel"
      },
      "terms": [
        {
          "id": "Yf4aZBfsUEu2NsQqsprngw"
        }
      ],
      "address": {
        "nl" : {
          "addressCountry": "BE",
          "addressLocality": "Scherpenheuvel-Zichem",
          "postalCode": "3271",
          "streetAddress": "Hoornblaas 107"
        }
      },
      "calendarType": "permanent",
      "mainLanguage": "nl",
      "labels": ["private-visible"],
      "hiddenLabels": ["private-invisible"]
    }
    """
    And I create a place and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
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
      "name": {
        "nl": "Cafe Den Hemel"
      },
      "terms": [
        {
          "id": "Yf4aZBfsUEu2NsQqsprngw"
        }
      ],
      "address": {
        "nl" : {
          "addressCountry": "BE",
          "addressLocality": "Scherpenheuvel-Zichem",
          "postalCode": "3271",
          "streetAddress": "Hoornblaas 107"
        }
      },
      "calendarType": "permanent",
      "mainLanguage": "nl",
      "labels": ["public-visible"],
      "hiddenLabels": ["public-invisible"]
    }
    """
    And I update the place at "%{placeUrl}"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "labels" should be:
    """
    ["public-visible"]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    ["public-invisible"]
    """

  Scenario: Keep a label added via the UI on a place when not included in complete overwrite
    Given I am authorized as JWT provider user "validator_scherpenheuvel"
    And I create a place from "places/place-with-required-fields.json" and save the "url" as "placeUrl"
    When I send a PUT request to "%{placeUrl}/labels/udb3label"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "labels" should be:
    """
    [ "udb3label" ]
    """
    And the JSON response should not have "hiddenLabels"
    When I set the JSON request payload to:
    """
    {
      "name": {
        "nl": "Cafe Den Hemel"
      },
      "terms": [
        {
          "id": "Yf4aZBfsUEu2NsQqsprngw"
        }
      ],
      "address": {
        "nl" : {
          "addressCountry": "BE",
          "addressLocality": "Scherpenheuvel-Zichem",
          "postalCode": "3271",
          "streetAddress": "Hoornblaas 107"
        }
      },
      "calendarType": "permanent",
      "mainLanguage": "nl",
      "labels": ["public-visible"],
      "hiddenLabels": ["public-invisible"]
    }
    """
    And I update the place at "%{placeUrl}"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "labels" should be:
    """
    [ "udb3label", "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4652
  Scenario: Create place with wrong invisible label that get resets to visible
    Given I create a place from "places/labels/place-with-wrong-invisible-label.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "labels" should be:
    """
    ["public-visible"]
    """
    And the JSON response should not have "hiddenLabels"

  @bugfix # https://jira.uitdatabank.be/browse/III-4652
  Scenario: Create place with wrong visible label that get resets to invisible
    Given I create a place from "places/labels/place-with-wrong-visible-label.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response should not have "labels"
    And the JSON response at "hiddenLabels" should be:
    """
    ["public-invisible"]
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4652
  Scenario: Create place with new visible label
    Given I create a random labelname of 10 characters
    And I create a place from "places/labels/place-with-new-visible-label.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "labels" should be:
    """
    ["%{labelname}"]
    """
    And the JSON response should not have "hiddenLabels"

  @bugfix # https://jira.uitdatabank.be/browse/III-4652
  Scenario: Create place with new invisible label
    Given I create a random labelname of 10 characters
    And I create a place from "places/labels/place-with-new-invisible-label.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response should not have "labels"
    And the JSON response at "hiddenLabels" should be:
    """
    ["%{labelname}"]
    """

  Scenario: Create place and add a label via default endpoint
    Given I create a random name of 10 characters
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I keep the value of the JSON response at "id" as "placeId"
    When I create a random labelname of 10 characters
    And I send a PUT request to "/places/%{placeId}/labels/%{labelname}"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "labels" should be:
    """
    ["%{labelname}"]
    """

  Scenario: Create place and delete a label of the place
    Given I create a random name of 10 characters
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    Given I create a random labelname of 10 characters
    And I keep the value of the JSON response at "id" as "placeId"
    And I send a PUT request to "/places/%{placeId}/labels/%{labelname}"
    When I send a DELETE request to "/places/%{placeId}/labels/%{labelname}"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response should not have "labels"

  Scenario: Create place and add a label via legacy endpoint
    Given I create a random name of 10 characters
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I keep the value of the JSON response at "id" as "placeId"
    Given I create a random labelname of 10 characters
    When I set the JSON request payload to:
    """
    {
	  "label": "%{labelname}"
    }
    """
    And I send a POST request to "/places/%{placeId}/labels/"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "labels" should be:
    """
    ["%{labelname}"]
    """
