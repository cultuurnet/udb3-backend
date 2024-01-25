Feature: Test creating places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Be prevented from creating a new place if we already have one on that address
    Given I create a minimal place and save the "id" as "placeId" then I should get a "201" response code
    Given I create a minimal place and save the "id" as "placeId" then I should get a "303" response code
    Then the JSON response should be:
    """
    {
      "message": "A place with this address / location name combination already exists. Please use the existing place for your purposes.",
      "placeId": "%{placeId}",
    }
    """
