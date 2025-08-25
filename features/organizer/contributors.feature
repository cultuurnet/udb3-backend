Feature: Test the UDB3 organizers contributors endpoint

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal organizer and save the "url" as "organizerUrl"

  Scenario: Organizers have no contributors by default
    When I send a GET request to "%{organizerUrl}/contributors"
    Then the JSON response should be:
    """
    []
    """

  Scenario: Update contributors
    Given I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    When I send a PUT request to "%{organizerUrl}/contributors"
    Then the response status should be "204"
    And I send a GET request to "%{organizerUrl}/contributors"
    Then the JSON response should be:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """

  Scenario: Delete all contributors
    Given I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    And I set the JSON request payload to:
    """
    []
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    Then the response status should be "204"
    And I send a GET request to "%{organizerUrl}/contributors"
    Then the JSON response should be:
    """
    []
    """

  Scenario: Contributors should be visible in the JSON projection if you are authenticated and have the necessary permission
    Given I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    And the response status should be "204"
    When I get the organizer at "%{organizerUrl}?embedContributors=true"
    Then the JSON response at "contributors" should be:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-5388
  Scenario: Contributors should not be saved in the JSON projection
    Given I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    And the response status should be "204"
    And I send a PUT request to "%{organizerUrl}/labels/randomLabel"
    And I am authorized as JWT provider v2 user "invoerder_lgm"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response should not have "contributors"

  Scenario: Contributors should not be visible in the JSON projection if you are authenticated and don't have the necessary permission
    Given I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    And the response status should be "204"
    And I am authorized as JWT provider v2 user "invoerder_lgm"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response should not have "contributors"

  Scenario: Contributors should not be visible in the JSON projection if you are anonymous
    Given I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    And the response status should be "204"
    And I am not authorized
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response should not have "contributors"

  Scenario: Overwrite all contributors
    Given I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    And I set the JSON request payload to:
    """
    [
      "new_user@example.com",
      "extra_information@example.com"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    And I send a GET request to "%{organizerUrl}/contributors"
    Then the JSON response should be:
    """
    [
      "new_user@example.com",
      "extra_information@example.com"
    ]
    """

  Scenario: Reject invalid emails
    Given I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    And I set the JSON request payload to:
    """
    [
      "09/1231212",
      "extra_information@example.com"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/0",
        "error":"The data must match the 'email' format"
      }
    ]
    """
    And I send a GET request to "%{organizerUrl}/contributors"
    Then the JSON response should be:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """

  Scenario: Users should not be allowed to view contributors of other organizers
    Given I set the JSON request payload to:
    """
    [
      "user@example.com",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    And I am authorized as JWT provider v2 user "invoerder_lgm"
    And I send a GET request to "%{organizerUrl}/contributors"
    Then the response status should be "403"
    And the JSON response at "detail" should include 'has no permission "Aanbod bewerken" on resource'

  Scenario: Users should be able to view contributors when they are a contributor
    Given I set the JSON request payload to:
    """
    [
      "dev+invoerder_dfm@publiq.be",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    And I am authorized as JWT provider v2 user "invoerder_dfm"
    And I send a GET request to "%{organizerUrl}/contributors"
    Then the response status should be "200"
    And the JSON response should be:
    """
    [
      "dev+invoerder_dfm@publiq.be",
      "information@example.com"
    ]
    """

  Scenario: Users should be able to edit organizers when they are an admin
    Given I set the JSON request payload to:
    """
    [
      "dev+invoerder_dfm@publiq.be",
      "information@example.com"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    And I set the JSON request payload to:
    """
    {
      "name": "Contributor updated title"
    }
    """
    And I send a PUT request to "%{organizerUrl}/name/nl"
    Then the response status should be "204"
    And I get the organizer at "%{organizerUrl}"
    And the JSON response at "name/nl" should be "Contributor updated title"

  Scenario: Users should be able to edit organizers when they are a contributor
    Given I am authorized as JWT provider v2 user "invoerder_dfm"
    And I send and accept "application/json"
    And I create a minimal organizer and save the "url" as "organizerUrl"
    Given I set the JSON request payload to:
    """
    [
      "dev+invoerder_dfm@publiq.be",
      "test@example.com"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    Then the response status should be "204"
    And I send a GET request to "%{organizerUrl}/contributors"
    Then the response status should be "200"
    And the JSON response should be:
    """
    [
      "dev+invoerder_dfm@publiq.be",
      "test@example.com"
    ]
    """
