Feature: Test the permissions for events in UDB3

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "invoerder_gbm"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create a minimal permanent event and save the "id" as "eventId"

  Scenario: get permissions of the current user who is the creator
    Given I am authorized as JWT provider v1 user "invoerder_gbm"
    When I send a GET request to "/events/%{eventId}/permissions/"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "permissions": [
            "Aanbod bewerken",
            "Aanbod modereren",
            "Aanbod verwijderen"
          ]
        }
        """

  Scenario: get legacy permissions of the current user who is the creator
    Given I am authorized as JWT provider v1 user "invoerder_gbm"
    When I send a GET request to "/events/%{eventId}/permission/"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "hasPermission": true
        }
        """

  Scenario: get permissions of the current user who is not the creator
    Given I am authorized as JWT provider v1 user "invoerder_lgm"
    When I send a GET request to "/events/%{eventId}/permissions/"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "permissions": []
        }
        """

  Scenario: get legacy permissions of the current user who is not the creator
    Given I am authorized as JWT provider v1 user "invoerder_lgm"
    When I send a GET request to "/events/%{eventId}/permission/"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "hasPermission": false
        }
        """

  Scenario: get permissions of a given user who is the creator
    Given I am authorized as JWT provider v1 user "invoerder_gbm"
    When I send a GET request to "/events/%{eventId}/permissions/f9045efa-5954-498b-864c-457eb9da6e0b"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "permissions": [
            "Aanbod bewerken",
            "Aanbod modereren",
            "Aanbod verwijderen"
          ]
        }
        """

  Scenario: get legacy permissions of a given user who is the creator
    Given I am authorized as JWT provider v2 user "centraal_beheerder"
    When I send a GET request to "/events/%{eventId}/permission/f9045efa-5954-498b-864c-457eb9da6e0b"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "hasPermission": true
        }
        """

  Scenario: get permissions of a given user who is not the creator
    Given I am authorized as JWT provider v2 user "centraal_beheerder"
    When I send a GET request to "/events/%{eventId}/permissions/40fadfd3-c4a6-4936-b1fe-20542ac56610"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "permissions": []
        }
        """

  Scenario: get legacy permissions of a given user who is not the creator
    Given I am authorized as JWT provider v2 user "centraal_beheerder"
    When I send a GET request to "/events/%{eventId}/permission/40fadfd3-c4a6-4936-b1fe-20542ac56610"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "hasPermission": false
        }
        """
