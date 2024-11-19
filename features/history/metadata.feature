Feature: Test the metadata in the history API

  Background:
    Given I am using the UDB3 base URL
    And I am authorized with an Auth0 client access token for "test_client"
    And I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I create a minimal permanent event and save the "url" as "eventUrl"
    And I wait for the event with url "%{eventUrl}" to be indexed
    
  Scenario: test metaData place
    Given I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I send a GET request to "%{placeUrl}/history"
    And the JSON response should include:
    """
    "auth0ClientName":"UiTdatabank Acceptance Tests"
    """

  Scenario: test metaData event
    Given I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I send a GET request to "%{eventUrl}/history"
    And the JSON response should include:
    """
    "auth0ClientName":"UiTdatabank Acceptance Tests"
    """
