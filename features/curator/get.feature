Feature: Test the curator API

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"
    And I create a news article and save the id as "articleId"

  Scenario: Get an article
    When I send a GET request to "/news-articles/%{articleId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "headline" should be "Curator API migrated"

  Scenario: Get an article via the old underscored path
    When I send a GET request to "/news_articles/%{articleId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "headline" should be "Curator API migrated"

  Scenario: Get an non-existing article article
    When I send a GET request to "/news-articles/18827e56-3666-4961-a5c8-7acd5dcfed9a"
    Then the response status should be "404"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/url/not-found",
     "title": "Not Found",
     "status": 404,
     "detail": "The News Article with id \"18827e56-3666-4961-a5c8-7acd5dcfed9a\" was not found."
    }
    """

  Scenario: Get an non-existing article article via the old underscored path
    When I send a GET request to "/news_articles/18827e56-3666-4961-a5c8-7acd5dcfed9a"
    Then the response status should be "404"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/url/not-found",
     "title": "Not Found",
     "status": 404,
     "detail": "The News Article with id \"18827e56-3666-4961-a5c8-7acd5dcfed9a\" was not found."
    }
    """

  Scenario: Get all articles in linked data
    Given I accept "application/ld+json"
    When I send a GET request to "/news-articles"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response should have "hydra:member"

  Scenario: Get all articles
    Given I accept "application/json"
    When I send a GET request to "/news-articles"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response should not have "hydra:member"

  Scenario: Get all articles via the old underscored path in linked data
    Given I accept "application/ld+json"
    When I send a GET request to "/news_articles"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response should have "hydra:member"

  Scenario: Get all articles via the old underscored path
    Given I accept "application/json"
    When I send a GET request to "/news_articles"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response should not have "hydra:member"

  Scenario: Get articles past last result in linked data
    Given I accept "application/ld+json"
    When I send a GET request to "/news-articles?page=1000000"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
      "hydra:member": [
      ]
    }
    """

  Scenario: Get articles past last result
    Given I accept "application/json"
    When I send a GET request to "/news-articles?page=1000000"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    []
    """

  Scenario: Search an article
    When I send a GET request to "/news-articles?publisher=BILL&about=17284745-7bcf-461a-aad0-d3ad54880e75&url=https://www.publiq.be/blog/curator-migratie"
    Then the response status should be "200"
    And the response body should be valid JSON

  Scenario: Search an article via the old underscored path
    When I send a GET request to "/news_articles?publisher=BILL&about=17284745-7bcf-461a-aad0-d3ad54880e75&url=https://www.publiq.be/blog/curator-migratie"
    Then the response status should be "200"
    And the response body should be valid JSON
