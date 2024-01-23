Feature: Read places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  @bugfix # https://jira.uitdatabank.be/browse/III-5979
  Scenario: Try to get a event that actually is a place
    Given I create a minimal place and save the "url" as "placeUrl"
    # The line below gets an event which is actually a place - this is on purpose and should fail
    Then I fail to get the event at "%{placeUrl}"

  @bugfix # https://jira.uitdatabank.be/browse/III-5979
  Scenario: Try to get a place that actually is an event
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    # The line below gets a place which is actually a event - this is on purpose and should fail
    Then I fail to get the place at "%{eventUrl}"
