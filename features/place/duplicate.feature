Feature: Test creating places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Allow creating a new place, if a "duplicate" place before was rejected
    Given I create a random name of 6 characters
    Given I create a minimal place and save the "id" as "originalPlaceId" then I should get a "201" response code
    When I publish the place at "/places/%{originalPlaceId}"
    And I wait for the place with url "/places/%{originalPlaceId}" to be indexed
    And I reject the place at "/places/%{originalPlaceId}" with reason "Rejected"
    And I am using the Search API v3 base URL
    When I send a GET request to "/places" with parameters:
      | workflowStatus | REJECTED              |
      | q              | id:%{originalPlaceId} |
    And I wait for the JSON response at "totalItems" to be "1"
    And I am using the UDB3 base URL
    And I create a minimal place then I should get a "201" response code

  Scenario: Be prevented from creating a new place if we already have one on that address
    Given I create a random name of 6 characters
    Given I create a minimal place and save the "id" as "originalPlaceId" then I should get a "201" response code
    Then I wait for the place with url "/places/%{originalPlaceId}" to be indexed
    Given I create a minimal place then I should get a "409" response code
    Then the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/uitdatabank/duplicate-place",
      "title": "Duplicate place",
      "status": 409,
      "detail": "A place with this address / name combination already exists. Please use the existing place for your purposes.",
      "duplicatePlaceUri": "%{baseUrl}/places/%{originalPlaceId}"
    }
    """

  Scenario: Be prevented from creating a new place if we already have one on that address when the the address contains special chars
    Given I create a name that includes special characters of elastic search and keep it as "name"
    Given I create a minimal place and save the "id" as "originalPlaceId" then I should get a "201" response code
    Then I wait for the place with url "/places/%{originalPlaceId}" to be indexed
    Given I create a minimal place then I should get a "409" response code
    Then the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/uitdatabank/duplicate-place",
      "title": "Duplicate place",
      "status": 409,
      "detail": "A place with this address / name combination already exists. Please use the existing place for your purposes.",
      "duplicatePlaceUri": "%{baseUrl}/places/%{originalPlaceId}"
    }
    """
