Feature: Test place contactPoint property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Update place contactPoint
    When I set the JSON request payload to:
    """
    {
      "email": [
        "user@example.com"
      ],
      "phone": [
        "0123456789"
      ],
      "url": [
        "http://google.be"
      ]
    }
    """
    And I send a PUT request to "%{placeUrl}/contact-point"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
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
        "http://google.be"
      ]
    }
    """

  Scenario: Update place contactPoint via legacy endpoint and legacy JSON schema
    When I set the JSON request payload to:
    """
    {
      "contactPoint": {
        "email": [
          "user@example.be"
        ],
        "phone": [
          "9876543210"
        ],
        "url": [
          "https://google.be"
        ]
      }
    }
    """
    And I send a POST request to "%{placeUrl}/contact-point"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    And the JSON response at "contactPoint" should be:
    """
    {
      "email": [
        "user@example.be"
      ],
      "phone": [
        "9876543210"
      ],
      "url": [
        "https://google.be"
      ]
    }
    """

  Scenario: When updating contact point with invalid request body an error is returned
    Given I set the JSON request payload to:
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
    When I send a PUT request to "%{placeUrl}/contactPoint"
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
    And I get the place at "%{placeUrl}"
    And the JSON response should not have "contactPoint"

  Scenario: Update with empty strings
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
    And I send a PUT request to "%{placeUrl}/contact-point"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response should not have contactPoint

  Scenario: Update with faulty contactpoints
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
    And I send a PUT request to "%{placeUrl}/contact-point"
    Then the response status should be "400"
    Then the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "The string should match pattern: ^(|[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,})$",
          "jsonPointer": "/email/0"
        },
        {
          "error": "The string should match pattern: ^http[s]?:\\/\\/\\w|^$",
          "jsonPointer": "/url/0"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """