Feature: Test creating places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Be prevented from creating a new place if we already have one on that address
    Given I prevent duplicate creation
    Given I create a random name of 6 characters and keep it as "name"
    Given I create a minimal place and save the "id" as "originalPlaceId" then I should get a "201" response code
    Given I create a minimal place and save the "originalPlace" as "newPlaceUri" then I should get a "409" response code
    Then the JSON response at "originalPlace" should be:
    """
    "http://host.docker.internal:9000/places/%{originalPlaceId}"
    """
    Then I allow duplicate creation
