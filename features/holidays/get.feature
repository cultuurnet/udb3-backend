Feature: Test the holidays endpoint

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"

  Scenario: Get holidays with default date range
    When I send a GET request to "/holidays/"
    Then the response status should be "200"
    And the response body should be valid JSON

  Scenario: Get holidays with explicit date range
    When I send a GET request to "/holidays/?startDate=2025-01-01&endDate=2025-12-31"
    Then the response status should be "200"
    And the response body should be valid JSON

  Scenario: Get holidays returns error when end date exceeds 5 years in the future
    When I send a GET request to "/holidays/?endDate=2040-01-01"
    Then the response status should be "400"
    And the JSON response at "title" should be "Date range exceeds limit"

  Scenario: Get holidays returns error on invalid startDate format
    When I send a GET request to "/holidays/?startDate=not-a-date"
    Then the response status should be "404"

  Scenario: Get holidays returns error on invalid endDate format
    When I send a GET request to "/holidays/?endDate=not-a-date"
    Then the response status should be "404"
