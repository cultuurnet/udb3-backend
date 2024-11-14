Feature: Test deleting places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

   Scenario: Delete place
    When I create a minimal place and save the "url" as "placeUrl"
    And I delete the place at "%{placeUrl}"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "DELETED"

   Scenario: Prevent deletion of UiTPAS place
    Given I set the value of name to "UiTPAS"
    And I create a place from "places/labels/place-with-new-invisible-label.json" and save the "url" as "placeUrl"
    And I send a DELETE request to "%{placeUrl}"
    Then the response status should be "403"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
        "type": "https://api.publiq.be/probs/uitdatabank/cannot-delete-uitpas-place",
        "title": "Cannot delete UiTPAS place",
        "status": 403,
        "detail": "Place is an UiTPAS counter. UiTPAS places cannot be deleted."
    }
    """

    Scenario: An admin creates a place, a normal users tries to delete this place, this should fail
        When I create a minimal place and save the "url" as "placeUrl"
        Given I am authorized as JWT provider v2 user "invoerder"
        And I send a DELETE request to "%{placeUrl}"
        Then the response status should be "403"

    Scenario: A normal user creates a place, this users tries to delete this place, this should work
        Given I am authorized as JWT provider v2 user "invoerder"
        When I create a minimal place and save the "url" as "placeUrl"
        And I delete the place at "%{placeUrl}"
        And I get the place at "%{placeUrl}"
        Then the JSON response at "workflowStatus" should be "DELETED"
