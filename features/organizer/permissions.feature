Feature: Test the permissions for organizers in UDB3

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "invoerder_gbm"
    And I send and accept "application/json"
    And I create a minimal organizer and save the "id" as "organizerId"

  Scenario: get permissions of the current user who is the creator
    Given I am authorized as JWT provider v1 user "invoerder_gbm"
    When I send a GET request to "/organizers/%{organizerId}/permissions/"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "permissions": [
            "Organisaties bewerken"
          ]
        }
        """

  Scenario: get permissions of the current user who is not the creator
    Given I am authorized as JWT provider v2 user "invoerder_lgm"
    When I send a GET request to "/organizers/%{organizerId}/permissions/"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "permissions": []
        }
        """

  Scenario: get permissions of a given user who is the creator
    Given I am authorized as JWT provider v1 user "invoerder_gbm"
    When I send a GET request to "/organizers/%{organizerId}/permissions/f9045efa-5954-498b-864c-457eb9da6e0b"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "permissions": [
            "Organisaties bewerken"
          ]
        }
        """

  Scenario: get permissions of a given user who is not the creator
    Given I am authorized as JWT provider v2 user "centraal_beheerder"
    When I send a GET request to "/organizers/%{organizerId}/permissions/269a8217-57a5-46b1-90e3-e9d2f91d45e5"
    Then the response status should be "200"
    And the JSON response should be:
        """
        {
          "permissions": []
        }
        """
