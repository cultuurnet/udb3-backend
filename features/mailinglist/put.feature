Feature: Test the UDB3 labels API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  @external
  Scenario: Subscribe to newsletter
    When I create a random name of 10 characters
    And I set the JSON request payload to:
      """
      { "email": "%{name}@test.be" }
      """
    And I send a PUT request to "mailing-list/1746977"
    Then the response status should be "204"