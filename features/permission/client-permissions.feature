Feature: Test the client permissions in UDB3

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"
    And I am not using an UiTID v1 API key
    And I am authorized with an OAuth client access token for "test_client"
    And I create a place from "places/molenhuis.json" and save the "id" as "uuid_place_molenhuis"

  Scenario: update place not created by the client but WITH permission
    When I set the JSON request payload from "places/molenhuis-updated-name.json"
    And I send a PUT request to "/places/%{uuid_place_molenhuis}"
    Then the response status should be "200"
    And I send a GET request to "/places/%{uuid_place_molenhuis}"
    Then the response status should be "200"
    And the JSON response at "name/nl" should be "Aangepaste naam"

  Scenario: update place not created by the client but WITHOUT permission
    Given I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "invoerder_gbm"
    And I set the JSON request payload from "places/hemmekes.json"
    And I send a POST request to "/imports/places/"
    And I keep the value of the JSON response at "id" as "uuid_place_hemmekes"
    When I am not using an UiTID v1 API key
    And I am authorized with an OAuth client access token for "test_client"
    And I set the JSON request payload from "places/molenhuis-updated-name.json"
    And I send a PUT request to "/places/%{uuid_place_hemmekes}"
    Then the response status should be "403"
    And I send a GET request to "/places/%{uuid_place_hemmekes}"
    And the response status should be "200"
    And the JSON response at "name/nl" should not be "Aangepaste naam"

  Scenario: add private invisible label WITH permission
    Given I create a minimal place and save the "url" as "placeUrl"
    When I send a PUT request to "%{placeUrl}/labels/private-invisible"
    Then the response status should be "204"
    And I send a GET request to "%{placeUrl}"
    And the JSON response at "hiddenLabels" should include "private-invisible"

  Scenario: add private visible label WITHOUT permission
    Given I create a minimal place and save the "url" as "placeUrl"
    When I send a PUT request to "%{placeUrl}/labels/private-visible"
    Then the response status should be "403"
    And I send a GET request to "%{placeUrl}"
    And the JSON response should not have "labels"

  Scenario: add public label not in permissions config
    Given I create a minimal place and save the "url" as "placeUrl"
    When I send a PUT request to "%{placeUrl}/labels/public-invisible"
    Then the response status should be "204"
    And I send a GET request to "%{placeUrl}"
    And the JSON response at "hiddenLabels" should include "public-invisible"

  Scenario: perform restricted request WITH permission
    When I create a role with a random name of 12 characters
    Then the response status should be "201"

  Scenario: perform restricted request WITHOUT permission
    When I set the JSON request payload to:
    """
    {
      "name": "Mock production",
      "eventIds": ["4123A28B-5C14-401A-8A83-906D5C215A07","2AB14F4E-CDD1-4AB8-80D6-607AFEC29E62"]
    }
    """
    And I send a POST request to "/productions"
    Then the response status should be "403"
