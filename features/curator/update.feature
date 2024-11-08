Feature: Test the curator API

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"
    And I create a news article and save the id as "articleId"
    And I create a random name of 12 characters

  Scenario: Update a news article
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint opnieuw API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BUZZ",
      "publisherLogo": "https://www.buzz.be/img/favicon.png",
      "url": "https://www.buzz.be/blog/%{name}"
    }
    """
    When I send a PUT request to "/news-articles/%{articleId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
      "headline": "publiq wint opnieuw API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BUZZ",
      "publisherLogo": "https://www.buzz.be/img/favicon.png",
      "url": "https://www.buzz.be/blog/%{name}",
      "id": "%{articleId}"
    }
    """
    And I keep the value of the JSON response at "id" as "articleId"
    When I send a GET request to "/news-articles/%{articleId}"
    Then the response status should be "200"
    And the JSON response at "headline" should be "publiq wint opnieuw API award"

  Scenario: Update a news article with an image
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint opnieuw API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BUZZ",
      "publisherLogo": "https://www.buzz.be/img/favicon.png",
      "url": "https://www.buzz.be/blog/%{name}",
      "image": {
        "url": "https://www.buzz.be/img.png",
        "copyrightHolder": "Buzz"
      }
    }
    """
    When I send a PUT request to "/news-articles/%{articleId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
      "headline": "publiq wint opnieuw API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BUZZ",
      "publisherLogo": "https://www.buzz.be/img/favicon.png",
      "url": "https://www.buzz.be/blog/%{name}",
      "image": {
        "url": "https://www.buzz.be/img.png",
        "copyrightHolder": "Buzz"
      },
      "id": "%{articleId}"
    }
    """
    And I keep the value of the JSON response at "id" as "articleId"
    When I send a GET request to "/news-articles/%{articleId}"
    Then the response status should be "200"
    And the JSON response at "image" should be:
    """
    {
      "copyrightHolder": "Buzz",
      "url": "https://www.buzz.be/img.png"
    }
    """

  Scenario: Update a news article with an url that should have been encoded
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint opnieuw API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BUZZ",
      "publisherLogo": "https://www.buzz.be/img/favicon.png",
      "url": "https://www.buzz.be/blog/café/%{name}"
    }
    """
    When I send a PUT request to "/news-articles/%{articleId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
      "headline": "publiq wint opnieuw API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BUZZ",
      "publisherLogo": "https://www.buzz.be/img/favicon.png",
      "url": "https://www.buzz.be/blog/caf%C3%A9/%{name}",
      "id": "%{articleId}"
    }
    """
    And I keep the value of the JSON response at "id" as "articleId"

    When I send a GET request to "/news-articles/%{articleId}"
    Then the response status should be "200"
    And the JSON response at "headline" should be "publiq wint opnieuw API award"
    And the JSON response at "url" should be "https://www.buzz.be/blog/caf%C3%A9/%{name}"

  Scenario: Update a non-existing news article
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint opnieuw API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BUZZ",
      "publisherLogo": "https://www.buzz.be/img/favicon.png",
      "url": "https://www.buzz.be/blog/%{name}"
    }
    """
    When I send a PUT request to "/news-articles/18827e56-3666-4961-a5c8-7acd5dcfed9a"
    Then the response status should be "404"

  Scenario: Update a news article via the old underscored path
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint opnieuw API award (UPDATED)",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BUZZ",
      "publisherLogo": "https://www.buzz.be/img/favicon.png",
      "url": "https://www.buzz.be/blog/%{name}"
    }
    """
    When I send a PUT request to "/news_articles/%{articleId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
      "headline": "publiq wint opnieuw API award (UPDATED)",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BUZZ",
      "publisherLogo": "https://www.buzz.be/img/favicon.png",
      "url": "https://www.buzz.be/blog/%{name}",
      "id": "%{articleId}"
    }
    """
    And I keep the value of the JSON response at "id" as "articleId"
    When I send a GET request to "/news_articles/%{articleId}"
    Then the response status should be "200"
    And the JSON response at "headline" should be "publiq wint opnieuw API award (UPDATED)"

  Scenario: Try to update a news article with an image without copyright
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint opnieuw API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BUZZ",
      "publisherLogo": "https://www.buzz.be/img/favicon.png",
      "url": "https://www.buzz.be/blog/%{name}",
      "image": {
        "url": "https://www.buzz.be/pic.jpeg"
      }
    }
    """
    When I send a PUT request to "/news-articles/%{articleId}"
    Then the response status should be "400"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "The required properties (copyrightHolder) are missing",
          "jsonPointer": "/image"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try to update a news article with an invalid image url
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint opnieuw API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BUZZ",
      "publisherLogo": "https://www.buzz.be/img/favicon.png",
      "url": "https://www.buzz.be/blog/%{name}",
      "image": {
        "url": "https://www.buzz.be/pic.mp4",
        "copyrightHolder": "Buzz"
      }
    }
    """
    When I send a PUT request to "/news-articles/%{articleId}"
    Then the response status should be "400"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "The string should match pattern: ^http(s?):([/|.|\\w|%20|-])*\\.(?:jpeg|jpg|gif|png)$",
          "jsonPointer": "/image/url"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """