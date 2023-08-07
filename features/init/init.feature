Feature: Setup the initial data

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  @init
  Scenario: Setup Labels Data
    Given Labels test data is available

  @init
  Scenario: Setup Roles Data
    Given Roles test data is available
