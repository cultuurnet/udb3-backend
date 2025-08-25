Feature: Test the UDB3 labels API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Create label
    When I create a label with a random name of 10 characters
     And I keep the value of the JSON response at "uuid" as "uuid"
     And I send a GET request to "/labels/%{uuid}"
    Then the response status should be "200"
     And the JSON response at "visibility" should be "visible"
     And the JSON response at "privacy" should be "public"

  Scenario: Create invalid label
    When I create a random name of 8 characters
    And I create a label with name "%{name}#*"
    And I keep the value of the JSON response at "uuid" as "uuid"
    And I send a GET request to "/labels/%{uuid}"
    Then the response status should be 200
    And the JSON response at "excluded" should be true