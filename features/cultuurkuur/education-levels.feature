Feature: Test the cultuurkuur education levels endpoint

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"

  Scenario: Get cultuurkuur education levels
    When I send a GET request to "/cultuurkuur/education-levels"
    Then the response status should be "200"
    And the response body should be valid JSON
