Feature: Test the UDB3 events API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Events have a no contactPoint by default
    When I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    Then the response status should be "201"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "contactPoint"

  Scenario: Update contactPoint
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    {
      "url": [
        "https://google.be"
      ],
      "email": [
        "user@example.com"
      ],
      "phone": [
        "0123456789"
      ]
    }
    """
    When I send a PUT request to "%{eventUrl}/contactPoint"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "contactPoint" should be:
    """
    {
      "email": [
        "user@example.com"
      ],
      "phone": [
        "0123456789"
      ],
      "url": [
        "https://google.be"
      ]
    }
    """

  Scenario: Update contactPoint via legacy endpoint and legacy JSON schema
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    {
      "contactPoint": {
        "url": [
          "https://google.be"
        ],
        "email": [
          "user@example.com"
        ],
        "phone": [
          "0123456789"
        ]
      }
    }
    """
    When I send a POST request to "%{eventUrl}/contactPoint"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "contactPoint" should be:
    """
    {
      "email": [
        "user@example.com"
      ],
      "phone": [
        "0123456789"
      ],
      "url": [
        "https://google.be"
      ]
    }
    """

  Scenario: When updating contact point with invalid request body an error is returned
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    {
      "email": [
        "user@example.com"
      ],
      "phone": [
        "0123456789"
      ]
    }
    """
    When I send a PUT request to "%{eventUrl}/contactPoint"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/",
        "error":"The required properties (url) are missing"
      }
    ]
    """
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "contactPoint"

  Scenario: Update with empty strings
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "email": [
        ""
      ],
      "phone": [
        ""
      ],
      "url": [
        ""
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/contact-point"
    Then the response status should be "204"
    When I get the event at "%{eventUrl}"
    Then the JSON response should not have contactPoint

  Scenario: Update with faulty contactpoints
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "email": [
        "This is not a email"
      ],
      "phone": [
        ""
      ],
      "url": [
        "This is not a url"
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/contact-point"
    Then the response status should be "400"
    Then the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "Minimum string length is 1, found 0",
          "jsonPointer": "/phone/0"
        },
        {
          "error": "The data must match the 'email' format",
          "jsonPointer": "/email/0"
        },
        {
          "error": "The data must match the 'uri' format",
          "jsonPointer": "/url/0"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

