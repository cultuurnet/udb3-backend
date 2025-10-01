Feature: Setup the initial data

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  @init
  Scenario: Setup Labels Data
    Given labels test data is available

  @init
  Scenario: Setup Roles Data
    Given roles test data is available
