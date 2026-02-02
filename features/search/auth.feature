@sapi3
Feature: Test the Search API v3 authentication

  Background:
    Given I am using the Search API v3 base URL
    And I send and accept "application/json"
    And I am not authorized
    And I am not using an UiTID v1 API key
    And I am not using a x-client-id header
    And I am not using an API key URL parameter
    And I am not using a clientId URL parameter

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

  Scenario: Search with an invalid API key
    Given I am using an invalid UiTID v1 API key
    When I send a GET request to "/events"
    Then the response status should be "401"
    And the JSON response should be:
    """
    {
      "title": "Unauthorized",
      "type": "https:\/\/api.publiq.be\/probs\/auth\/unauthorized",
      "status": 401,
      "detail": "The provided api key invalid-api-key is invalid"
    }
    """

  Scenario: Search with an API key that will be matched to a client id
    Given I am using an UiTID v1 API key of consumer "apiKeyMatchedToClientId"
    When I send a GET request to "/events"
    Then the response status should be "200"

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

  Scenario: Search with an invalid client id
    Given I am using an invalid x-client-id header
    When I send a GET request to "/events"
    Then the response status should be "403"
    And the JSON response should be:
    """
    {
      "title": "Forbidden",
      "type": "https:\/\/api.publiq.be\/probs\/auth\/forbidden",
      "status": 403,
      "detail": "The provided client id invalid-client-id is not allowed to access this API."
    }
    """

  Scenario: Search with a client access token of a client that has access to Search API v3
    Given I am authorized with an OAuth client access token for "test_client_sapi3_only"
    And I am using the Search API v3 base URL
    When I send a GET request to "/events"
    Then the response status should be "200"

  Scenario: Search with a user access token of a client that has access to Search API v3
    Given I am authorized with an OAuth user access token for "invoerder" via client "test_client_sapi3_only"
    And I am using the Search API v3 base URL
    When I send a GET request to "/events"
    Then the response status should be "200"

  Scenario: Search with a client access token of a client that has no access to Search API v3
    Given I am authorized with an OAuth client access token for "test_client_no_apis"
    And I am using the Search API v3 base URL
    When I send a GET request to "/events"
    Then the response status should be "403"

  Scenario: Search with API key URL parameter that has access to Search API v3
    Given I am using an API key URL parameter of consumer "uitdatabank"
    When I send a GET request to "/events"
    Then the response status should be "200"

  Scenario: Search with clientId URL parameter that has access to Search API v3
    Given I am using a clientId URL parameter for client "test_client_sapi3_only"
    When I send a GET request to "/events"
    Then the response status should be "200"
