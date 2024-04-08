@sapi3
Feature: Test the Search API v3 authentication

  Background:
    Given I am using the Search API v3 base URL
    And I send and accept "application/json"
    And I am not authorized
    And I am not using an UiTID v1 API key
    And I am not using a x-client-id header

  Scenario: Search without authentication
    When I send a GET request to "/events"
    Then the response status should be "401"

  Scenario: Search with API key that has access to Search API v3
    Given I am using an UiTID v1 API key of consumer "uitdatabank"
    When I send a GET request to "/events"
    Then the response status should be "200"

  Scenario: Search with API key that does not exist
    Given I am using an UiTID v1 API key of consumer "nonExisting"
    When I send a GET request to "/events"
    Then the response status should be "401"

  Scenario: Search with a client id that has access to Search API v3
    Given I am using a x-client-id header for client "test_client_sapi3_only"
    And I am using the Search API v3 base URL
    When I send a GET request to "/events"
    Then the response status should be "200"

  Scenario: Search with a client id that has no access to Search API v3
    Given I am using a x-client-id header for client "test_client_no_apis"
    And I am using the Search API v3 base URL
    When I send a GET request to "/events"
    Then the response status should be "403"

  Scenario: Search with a client access token of a client that has access to Search API v3
    Given I am authorized with an Auth0 client access token for "test_client_sapi3_only"
    And I am using the Search API v3 base URL
    When I send a GET request to "/events"
    Then the response status should be "200"

  Scenario: Search with a user access token of a client that has access to Search API v3
    Given I am authorized with an Auth0 user access token for "invoerder" via client "test_client_sapi3_only"
    And I am using the Search API v3 base URL
    When I send a GET request to "/events"
    Then the response status should be "200"

  Scenario: Search with a client access token of a client that has no access to Search API v3
    Given I am authorized with an Auth0 client access token for "test_client_no_apis"
    And I am using the Search API v3 base URL
    When I send a GET request to "/events"
    Then the response status should be "403"

  Scenario: Search with a JWT provider v2 token
    Given I am authorized as JWT provider v2 user "invoerder"
    And I am using the Search API v3 base URL
    When I send a GET request to "/events"
    Then the response status should be "403"
