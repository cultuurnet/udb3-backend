@sapi3
Feature: Test the Search API v3 boa feature

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/audience-type/event-audience-type-children-only.json" and save the "id" as "otherChildrenOnlyEventId"
    And I publish the event at "/events/%{otherChildrenOnlyEventId}"
    And I am not authorized
    And I am not using an UiTID v1 API key

 Scenario: I can only search my own children only events
   When I am authorized with an OAuth client access token for "test_client"
   And I create an event from "events/audience-type/event-audience-type-children-only.json" and save the "id" as "myChildrenOnlyEventId"
   And I publish the event at "/events/%{myChildrenOnlyEventId}"
   And I wait 2 seconds
   And I am using the Search API v3 base URL
   And I am using a x-client-id header for client "test_client"
   And I send a GET request to "/events" with parameters:
     | audienceType | childrenOnly |
     | q            | id:(%{otherChildrenOnlyEventId} OR %{myChildrenOnlyEventId}) |
   Then the JSON response at "totalItems" should be 1
   And the JSON response should include:
   """
   %{myChildrenOnlyEventId}
   """
   And the JSON response should not include:
   """
   %{otherChildrenOnlyEventId}
   """
