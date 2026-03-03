@sapi3
Feature: Test the Search API v3 pagination and sorting

  Background:
    Given I am using the Search API v3 base URL
    And I am using a x-client-id header for client "test_client_sapi3_only"
    And I send and accept "application/json"

  Scenario: Default itemsPerPage should be 30
    When I send a GET request to "/offers"
    Then the response status should be "200"
    And the JSON response at "itemsPerPage" should be 30

  Scenario: Custom limit is accepted
    When I send a GET request to "/offers" with parameters:
      | limit | 50 |
    Then the response status should be "200"
    And the JSON response at "itemsPerPage" should be 50

  Scenario: Limit above 2000 returns error
    When I send a GET request to "/offers" with parameters:
      | limit | 3000 |
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
      "title": "Not Found",
      "type": "https:\/\/api.publiq.be\/probs\/url\/not-found",
      "status": 404,
      "detail": "The \"limit\" parameter should be between 0 and 2000"
    }
    """

  Scenario: Different start is possible
    When I send a GET request to "/offers" with parameters:
      | start | 10 |
    Then the response status should be "200"

  Scenario: Start above 10000 returns error
    When I send a GET request to "/offers" with parameters:
      | start | 1000000 |
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
      "title": "Not Found",
      "type": "https:\/\/api.publiq.be\/probs\/url\/not-found",
      "status": 404,
      "detail": "The \"start\" parameter should be between 0 and 10000"
    }
    """

  Scenario: Sort by availableTo ascending
    When I send a GET request to "/offers" with parameters:
      | sort[availableTo] | asc |
    Then the response status should be "200"

  Scenario: Sort by availableTo descending
    When I send a GET request to "/offers" with parameters:
      | sort[availableTo] | desc |
    Then the response status should be "200"

  Scenario: Sort by completeness ascending
    When I send a GET request to "/offers" with parameters:
      | sort[completeness] | asc |
    Then the response status should be "200"

  Scenario: Sort by created ascending
    When I send a GET request to "/offers" with parameters:
      | sort[created] | asc |
    Then the response status should be "200"

  Scenario: Sort by distance ascending
    When I send a GET request to "/offers" with parameters:
      | coordinates    | 50.8511740,4.3386740 |
      | distance       | 10km                 |
      | sort[distance] | asc                  |
    Then the response status should be "200"

  Scenario: Sort by modified ascending
    When I send a GET request to "/offers" with parameters:
      | sort[modified] | asc |
    Then the response status should be "200"

  Scenario: Sort by modified descending
    When I send a GET request to "/offers" with parameters:
      | sort[modified] | desc |
    Then the response status should be "200"

  Scenario: Sort by score ascending
    When I send a GET request to "/offers" with parameters:
      | sort[score] | asc |
    Then the response status should be "200"

  Scenario: Sort by score descending
    When I send a GET request to "/offers" with parameters:
      | sort[score] | desc |
    Then the response status should be "200"
