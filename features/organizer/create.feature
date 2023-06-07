Feature: Test creating organizers
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Create a new organizer with minimal properties
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    And the JSON response at "@id" should be "%{organizerUrl}"
    And the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name/nl" should be "%{name}"
    And the JSON response at "url" should be "https://www.%{name}.be"