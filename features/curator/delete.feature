Feature: Test the curator API

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"
    And I create a news article and save the id as "articleId"

  Scenario: Delete an article
    Given I send and accept "application/json"
    When I send a DELETE request to "/news-articles/%{articleId}"
    Then the response status should be "204"
    When I send a GET request to "/news-articles/%{articleId}"
    Then the response status should be "404"
