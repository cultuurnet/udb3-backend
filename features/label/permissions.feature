Feature: Test the UDB3 labels API permissions

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I send and accept "application/json"

  Scenario: As an anonymous user I cannot create a label directly
    Given I am not authorized
    When I create a label with a random name of 10 characters
    Then the response status should be "401"

  Scenario: As a regular user I cannot create a label directly
    Given I am authorized as JWT provider v1 user "invoerder_lgm"
    When I create a label with a random name of 10 characters
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot update a label's settings
    Given I am authorized as JWT provider v2 user "centraal_beheerder"
    And I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "uuid"
    And I am not authorized
    When I set the JSON request payload to:
    """
    { "command": "MakeInvisible" }
    """
    And I send a PATCH request to "/labels/%{uuid}"
    Then the response status should be "401"

  Scenario: As a regular user I cannot update a label's settings
    Given I am authorized as JWT provider v2 user "centraal_beheerder"
    And I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "uuid"
    And I am authorized as JWT provider v1 user "invoerder_lgm"
    When I set the JSON request payload to:
    """
    { "command": "MakeInvisible" }
    """
    And I send a PATCH request to "/labels/%{uuid}"
    Then the response status should be "403"

  # See https://jira.uitdatabank.be/browse/III-4855
  Scenario: As an anonymous user I can search labels
    When I send a GET request to "/labels"
    Then the response status should be "200"

  # See https://jira.uitdatabank.be/browse/III-4855
  Scenario: As a regular user I can search labels
    Given I am authorized as JWT provider v1 user "invoerder_lgm"
    When I send a GET request to "/labels"
    Then the response status should be "200"
