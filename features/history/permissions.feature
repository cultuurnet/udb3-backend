Feature: Test the permissions of the history API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I create a minimal permanent event and save the "url" as "eventUrl"
    And I wait for the event with url "%{eventUrl}" to be indexed

  Scenario: As a regular user I cannot get the history of an event or a place
    Given I am authorized as JWT provider user "invoerder_lgm"
    When I send a GET request to "%{eventUrl}/history"
    Then the response status should be "403"
    When I send a GET request to "%{placeUrl}/history"
    Then the response status should be "403"

  Scenario: As a user with the AANBOD_HISTORIEK permission I can get the history of an event or a place
    Given I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I send a PUT request to "/roles/%{roleId}/permissions/AANBOD_HISTORIEK"
    And I send a PUT request to "/roles/%{roleId}/users/2eaab859-fd3d-425f-9796-80eddaa2b0a6"
    And I am authorized as JWT provider user "invoerder_2"
    When I send a GET request to "%{eventUrl}/history"
    Then the response status should be "200"
    When I send a GET request to "%{placeUrl}/history"
    Then the response status should be "200"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send a DELETE request to "/roles/%{roleId}"
