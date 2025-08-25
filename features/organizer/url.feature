Feature: Test organizer url property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal organizer and save the "url" as "organizerUrl"

  Scenario: Update organizer url via url endpoint
    When I create a random name of 10 characters
    And I set the JSON request payload to:
    """
    {"url":"https://www.%{name}.be"}
    """
    And I send a PUT request to "%{organizerUrl}/url"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "url" should be:
    """
    "https://www.%{name}.be"
    """

  Scenario: Update organizer url with existing url via url endpoint
    Given I create a minimal organizer and save the "url" as "organizerUrl2"
    When I set the JSON request payload to:
    """
    {"url":"https://www.%{name}.be"}
    """
    And I send a PUT request to "%{organizerUrl}/url"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/uitdatabank/duplicate-url",
     "title": "Duplicate URL",
     "status": 400,
     "detail": "The url https://www.%{name}.be (normalized to %{name}.be) is already in use."
    }
    """

  Scenario: Update organizer with missing url via url endpoint
    When I create a random name of 10 characters
    And I set the JSON request payload to:
    """
    {"website":"http://www.%{name}.be"}
    """
    And I send a PUT request to "%{organizerUrl}/url"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/body/invalid-data",
     "title": "Invalid body data",
     "status": 400,
     "schemaErrors": [
        {
          "error": "The required properties (url) are missing",
          "jsonPointer": "/"
        }
      ]
    }
    """

  Scenario: Update organizer with invalid url via url endpoint
    When I create a random name of 10 characters
    And I set the JSON request payload to:
    """
    {"url":"ftp://www.%{name}.be"}
    """
    And I send a PUT request to "%{organizerUrl}/url"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/body/invalid-data",
     "title": "Invalid body data",
     "status": 400,
     "schemaErrors": [
        {
          "error": "The string should match pattern: ^http[s]?:\\/\\/\\w",
          "jsonPointer": "/url"
        }
      ]
    }
    """
