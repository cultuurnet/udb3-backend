Feature: Read events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"

  @bugfix # https://jira.uitdatabank.be/browse/III-5979
  Scenario: Try to get a event that actually is a place
    Given I create a minimal place and save the "id" as "placeId"
    When I send a GET request to "/events/%{placeId}"
    Then the response status should be "404"
