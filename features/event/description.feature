Feature: Test event description property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

    Given I set the form data properties to:
      | description     | publiq logo |
      | copyrightHolder | publiq vzw  |
      | language        | nl          |
    When I upload "file" from path "images/publiq.png" to "/images/"
    And I keep the value of the JSON response at "imageId" as "image_id"

    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-referenced-location.json"
    
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"

  Scenario: Update event description
    And I set the JSON request payload to:
        """
        { "description": "Updated description test_event in Dutch" }
        """
    When I send a PUT request to "/events/%{uuid_testevent}/description/nl"
    Then the response status should be "204"
    And I set the JSON request payload to:
        """
        { "description": "Updated description test_event in English" }
        """
    When I send a PUT request to "/events/%{uuid_testevent}/description/en"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "description/nl" should be:
        """
        "Updated description test_event in Dutch"
        """
    And the JSON response at "description/en" should be:
        """
        "Updated description test_event in English"
        """

  Scenario: It should strip tags
    And I set the JSON request payload to:
        """
        { "description": "<img src=\"https://foobar.com/1f457.png\" alt=\":dress:\" style=\"height: ;width: \"/><strong>Onze nieuwste jurk</strong>" }
        """
    When I send a PUT request to "/events/%{uuid_testevent}/description/nl"
    Then the response status should be "204"
    And I set the JSON request payload to:
        """
        { "description": "<img src=\"https://foobar.com/1f457.png\" alt=\":dress:\" style=\"height: ;width: \"/><strong>Our latest dress</strong>" }
        """
    When I send a PUT request to "/events/%{uuid_testevent}/description/en"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "description/nl" should be:
        """
        "<strong>Onze nieuwste jurk</strong>"
        """
    And the JSON response at "description/en" should be:
        """
        "<strong>Our latest dress</strong>"
        """

  Scenario: Update event description (with legacy POST)
    And I set the JSON request payload to:
    """
    { "description": "Updated description test_event in Dutch" }
    """
    When I send a PUT request to "/events/%{uuid_testevent}/description/nl"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    And the response status should be "200"
    And the JSON response at "description/nl" should be:
    """
    "Updated description test_event in Dutch"
    """

  Scenario: Update event description shows error for invalid body
    And I set the JSON request payload to:
    """
    {}
    """
    When I send a PUT request to "/events/%{uuid_testevent}/description/nl"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/",
        "error":"The required properties (description) are missing"
      }
    ]
    """

  # Relates to https://jira.uitdatabank.be/browse/III-5150
  # Right now the JSON response returns an empty string when the description is empty, it shouldn't return any value
  Scenario: Remove an event description by passing an empty one
    And I set the JSON request payload to:
        """
        { "description": "Updated description test_event in Dutch" }
        """
    And I send a PUT request to "/events/%{uuid_testevent}/description/nl"
    When I set the JSON request payload to:
        """
        { "description": "" }
        """
    When I send a PUT request to "/events/%{uuid_testevent}/description/nl"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "description/nl" should be:
        """
        ""
        """

  Scenario: Delete the last description of an event
    When I set the JSON request payload to:
    """
    { "description": "Beschrijving" }
    """
    And I send a PUT request to "/events/%{uuid_testevent}/description/nl"
    Then the response status should be "204"
    When I send a DELETE request to "/events/%{uuid_testevent}/description/nl"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response should not have "description"

  Scenario: Delete a description of an event, with one description left
    When I set the JSON request payload to:
    """
    { "description": "Le description" }
    """
    And I send a PUT request to "/events/%{uuid_testevent}/description/fr"
    Then the response status should be "204"
    When I set the JSON request payload to:
    """
    { "description": "Beschrijving" }
    """
    And I send a PUT request to "/events/%{uuid_testevent}/description/nl"
    Then the response status should be "204"
    When I send a DELETE request to "/events/%{uuid_testevent}/description/nl"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "description" should be:
    """
      {"fr": "Le description"}
    """