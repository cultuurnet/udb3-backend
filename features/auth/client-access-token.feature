Feature: Test authentication with OAuth client access tokens

  Background:
    Given I am using the UDB3 base URL
    And I am not using an UiTID v1 API key
    And I send and accept "application/json"

  Scenario: I cannot get my user details with an OAuth client access token
    Given I am authorized with an OAuth client access token for "test_client"
    When I send a GET request to "/user"
    Then the response status should be "401"

  Scenario: I can create a place and an event with an OAuth client access token
    Given I am authorized with an OAuth client access token for "test_client"
    And I create a minimal place and save the "url" as "placeUrl"
    And the response status should be "201"
    When I create a minimal permanent event and save the "url" as "eventUrl"
    Then the response status should be "201"

  Scenario: I cannot create a place with an OAuth client access token if the client cannot access EntryAPI
    Given I am authorized with an OAuth client access token for "test_client_sapi3_only"
    When I set the JSON request payload from "places/place.json"
    And I send a POST request to "/places"
    Then the response status should be "403"

