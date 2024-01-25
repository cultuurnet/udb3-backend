Feature: Read places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  @bugfix # https://jira.uitdatabank.be/browse/III-5979
  Scenario: Try to get a place that actually is an event
    Given I create a minimal place and save the "url" as "placeUrl"
    Given I create a minimal permanent event and save the "id" as "eventId"
    Then I send a GET request to "/places/%{eventId}"
    Then the response status should be "404"
