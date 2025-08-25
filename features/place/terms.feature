Feature: Test place terms property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Update place type
    When I send a PUT request to "%{placeUrl}/type/3CuHvenJ+EGkcvhXLg9Ykg"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "terms/0/id" should be "3CuHvenJ+EGkcvhXLg9Ykg"

  Scenario: Update place type with a term that is not an eventtype
    When I send a PUT request to "%{placeUrl}/type/1.17.0.0.0"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/url/not-found",
      "title": "Not Found",
      "status": 404,
      "detail": "Category with id 1.17.0.0.0 not found in eventtype domain or not applicable for Place."
    }
    """

  Scenario: Update place type with a term that does not exist
    When I send a PUT request to "%{placeUrl}/type/foo"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/url/not-found",
      "title": "Not Found",
      "status": 404,
      "detail": "Category with id foo not found in eventtype domain or not applicable for Place."
    }
    """

  Scenario: Update place type with a term that it is not an eventtype for places
    When I send a PUT request to "%{placeUrl}/type/0.5.0.0.0"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/url/not-found",
      "title": "Not Found",
      "status": 404,
      "detail": "Category with id 0.5.0.0.0 not found in eventtype domain or not applicable for Place."
    }
    """

  Scenario: Update place facilities in legacy format
    When I set the JSON request payload to:
    """
    {
      "facilities": [
        "3.25.0.0.0"
      ]
    }
    """
    And I send a PUT request to "%{placeUrl}/facilities"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "terms/1/id" should be "3.25.0.0.0"

  Scenario: Update place facilities
    When I set the JSON request payload to:
    """
    [
      "3.13.1.0.0",
      "3.23.3.0.0"
    ]
    """
    And I send a PUT request to "%{placeUrl}/facilities"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "terms/1/id" should be "3.13.1.0.0"
    And the JSON response at "terms/2/id" should be "3.23.3.0.0"

  Scenario: Update place facilities with invalid facility id
    When I set the JSON request payload to:
    """
    [
      "3.23.3.0.0",
      "foobar"
    ]
    """
    And I send a PUT request to "%{placeUrl}/facilities"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/body/invalid-data",
      "title": "Invalid body data",
      "status": 400,
      "detail": "Category with id foobar not found in facility domain or not applicable for Place."
    }
    """
    When I get the place at "%{placeUrl}"
    Then the JSON response at "terms/0/id" should be "Yf4aZBfsUEu2NsQqsprngw"
    And the JSON response should not have "terms/1"

  Scenario: Update place facilities without permissions
    Given I am authorized as JWT provider user "validator_diest"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    When I set the JSON request payload to:
    """
    [
      "3.23.3.0.0"
    ]
    """
    And I send a PUT request to "%{placeUrl}/facilities"
    Then the response status should be "403"
    And the JSON response at "type" should be "https://api.publiq.be/probs/auth/forbidden"
    And the JSON response at "title" should be "Forbidden"
    And the JSON response at "status" should be 403
    And the JSON response should have "detail"