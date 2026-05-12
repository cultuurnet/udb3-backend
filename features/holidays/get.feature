# Most edge cases are tested in the unit tests, this is just a basic test to check if the endpoint is working and returns valid JSON, to prevent relying to much on an external API for testing.
Feature: Test the holidays endpoint

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"

  Scenario: Get holidays with default date range
    When I send a GET request to "/holidays/"
    Then the response status should be "200"
    And the response body should be valid JSON
