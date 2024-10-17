Feature: Test the curator API

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"
    And I create a random name of 12 characters

  Scenario: Create a news article
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/%{name}"
    }
    """
    When I send a POST request to "/news-articles"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "id" as "articleId"
    When I send a GET request to "/news-articles/%{articleId}"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "headline": "publiq wint API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/%{name}",
      "id": "%{articleId}"
    }
    """

  Scenario: Create a news article with a png image
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/%{name}",
      "image": {
        "url": "https://www.uitinvlaanderen.be/img.png",
        "copyrightHolder": "publiq vzw"
      }
    }
    """
    When I send a POST request to "/news-articles"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "id" as "articleId"
    When I send a GET request to "/news-articles/%{articleId}"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "headline": "publiq wint API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/%{name}",
      "image": {
        "url": "https://www.uitinvlaanderen.be/img.png",
        "copyrightHolder": "publiq vzw"
      },
      "id": "%{articleId}"
    }
    """

  Scenario: Create a news article with a jpg image
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/%{name}",
      "image": {
        "url": "https://www.uitinvlaanderen.be/img.jpg",
        "copyrightHolder": "publiq vzw"
      }
    }
    """
    When I send a POST request to "/news-articles"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "id" as "articleId"
    When I send a GET request to "/news-articles/%{articleId}"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "headline": "publiq wint API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/%{name}",
      "image": {
        "url": "https://www.uitinvlaanderen.be/img.jpg",
        "copyrightHolder": "publiq vzw"
      },
      "id": "%{articleId}"
    }
    """

  Scenario: Create a news article with url that should have been encoded
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/café/%{name}"
    }
    """
    When I send a POST request to "/news-articles"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "id" as "articleId"
    And the JSON response should be:
    """
    {
      "headline": "publiq wint API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/caf%C3%A9/%{name}",
      "id": "%{articleId}"
    }
    """
    And I keep the value of the JSON response at "id" as "articleId"

    When I send a GET request to "/news-articles/%{articleId}"
    Then the response status should be "200"
    And the JSON response at "url" should be "https://www.publiq.be/blog/caf%C3%A9/%{name}"

  Scenario: Create a news article with an existing url and about
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/%{name}"
    }
    """
    When I send a POST request to "/news-articles"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "id" as "articleId"
    When I set the JSON request payload to:
    """
    {
      "headline": "publiq wint API award (again!)",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/%{name}"
    }
    """
    And I send a POST request to "/news-articles"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/body/invalid-data",
      "title": "Invalid body data",
      "status": 400,
      "detail": "A news article with the given url and about already exists. (%{articleId}) Do a GET /news-articles request with `url` and `about` parameters to find it programmatically."
    }
    """

  Scenario: Try to create a news article with missing properties
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint API award",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/%{name}"
    }
    """
    When I send a POST request to "/news-articles"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "The required properties (inLanguage, publisher) are missing",
          "jsonPointer": "/"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try to create a news article with an image without copyrightHolder
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/%{name}",
      "image": {
        "url": "https://www.uitinvlaanderen.be/img.png"
      }
    }
    """
    When I send a POST request to "/news-articles"
    Then the response status should be "400"
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

  Scenario: Try to create a news article with an image with an invalid image url
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/%{name}",
      "image": {
        "url": "https://www.uitinvlaanderen.be/img.pdf",
        "copyrightHolder": "Publiq vzw"
      }
    }
    """
    When I send a POST request to "/news-articles"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "The string should match pattern: ^http(s?):([/|.|\\w|\\s|-])*\\.(?:jpeg|jpeg|gif|png)$",
          "jsonPointer": "/image/url"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Applies label on about event
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-referenced-location.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"

    Given I am not using an UiTID v1 API key
    And I am not authorized
    When I set the JSON request payload to:
    """
    {
      "headline": "publiq wint API award",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "%{uuid_testevent}",
      "publisher": "BRUZZ",
      "publisherLogo": "https://www.bruzz.be/img/favicon.png",
      "url": "https://www.bruzz.be/blog/%{name}"
    }
    """
    And I send a POST request to "/news-articles"
    Then the response status should be "201"

    Given I am using the UDB3 base URL
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "hiddenLabels" should be:
    """
    [
      "BRUZZ-redactioneel"
    ]
    """

  Scenario: Create a news article via the old underscored path
    Given I set the JSON request payload to:
    """
    {
      "headline": "publiq wint API award (UNDERSCORED)",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/%{name}"
    }
    """
    When I send a POST request to "/news_articles"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "id" as "articleIdUnderscored"
    When I send a GET request to "/news_articles/%{articleIdUnderscored}"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "headline": "publiq wint API award (UNDERSCORED)",
      "inLanguage": "nl",
      "text": "Op 10 januari 2020 wint publiq de API award",
      "about": "17284745-7bcf-461a-aad0-d3ad54880e75",
      "publisher": "BILL",
      "publisherLogo": "https://www.bill.be/img/favicon.png",
      "url": "https://www.publiq.be/blog/%{name}",
      "id": "%{articleIdUnderscored}"
    }
    """
