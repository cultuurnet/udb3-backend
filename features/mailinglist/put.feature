Feature: Test the UDB3 labels API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Subscribe to newsletter
    When I create a random name of 10 characters
    And I send a PUT request to "mailinglist/%{name}@test.be/1746977"
    Then the response status should be "200"
    And the JSON response at "status" should be "ok"