Feature: Read events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  @bugfix # https://jira.uitdatabank.be/browse/III-5979
  Scenario: Try to get a event that actually is a place
    Given I create a minimal place and save the "id" as "placeId"
    Then I get the event at "http://host.docker.internal:8000/events/%{placeId}" and get response code "404"
