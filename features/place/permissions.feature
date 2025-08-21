Feature: Test the permissions for places in UDB3

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "invoerder_gbm"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "id" as "placeId"

  Scenario: get permissions of the current user who is the creator
    Given I am authorized as JWT provider v2 user "invoerder_gbm"
    When I send a GET request to "/places/%{placeId}/permissions/"
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
    Given I am authorized as JWT provider v2 user "invoerder_gbm"
    When I send a GET request to "/places/%{placeId}/permission/"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "hasPermission": true
        }
        """

  Scenario: get permissions of the current user who is not the creator
    Given I am authorized as JWT provider v2 user "invoerder_lgm"
    When I send a GET request to "/places/%{placeId}/permissions/"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "permissions": []
        }
        """

  Scenario: get legacy permissions of the current user who is not the creator
    Given I am authorized as JWT provider v2 user "invoerder_lgm"
    When I send a GET request to "/places/%{placeId}/permission/"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "hasPermission": false
        }
        """

  Scenario: get permissions of a given user who is the creator
    Given I am authorized as JWT provider v2 user "invoerder_gbm"
    When I send a GET request to "/places/%{placeId}/permissions/1963c5ab-7e2c-416d-a269-243790019f7d"
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
    When I send a GET request to "/places/%{placeId}/permission/1963c5ab-7e2c-416d-a269-243790019f7d"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "hasPermission": true
        }
        """

  Scenario: get permissions of a given user who is not the creator
    Given I am authorized as JWT provider v2 user "centraal_beheerder"
    When I send a GET request to "/places/%{placeId}/permissions/269a8217-57a5-46b1-90e3-e9d2f91d45e5"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "permissions": []
        }
        """

  Scenario: get legacy permissions of a given user who is not the creator
    Given I am authorized as JWT provider v2 user "centraal_beheerder"
    When I send a GET request to "/places/%{placeId}/permission/269a8217-57a5-46b1-90e3-e9d2f91d45e5"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "hasPermission": false
        }
        """
