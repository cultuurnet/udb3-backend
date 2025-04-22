Feature: Test the cultuurkuur region endpoint

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"

  Scenario: Create a news article
    When I send a GET request to "/cultuurkuur/regions"
    Then the response status should be "200"
    And the response body should be valid JSON
