@sapi3
Feature: Test the Search API v3 boa feature

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-children-only.json" and save the "id" as "otherChildrenOnlyEventId"
    And I publish the event at "/events/%{otherChildrenOnlyEventId}"
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I wait for the event with url "/events/%{otherChildrenOnlyEventId}" to be indexed
    And I am not authorized
    And I am not using an UiTID v1 API key

  Scenario: When I do not have the boa scope I can only find children only events created by myself
    When I am authorized with an OAuth client access token for "test_client"
    And I create an event from "events/event-children-only.json" and save the "id" as "myChildrenOnlyEventId"
    And I publish the event at "/events/%{myChildrenOnlyEventId}"
    And I wait for the event with url "/events/%{myChildrenOnlyEventId}" to be indexed
    And I am using the Search API v3 base URL
    And I am using a x-client-id header for client "test_client"
    When I send a GET request to "/events" with parameters:
      | childrenOnly | true                                                         |
      | q            | id:(%{otherChildrenOnlyEventId} OR %{myChildrenOnlyEventId}) |
    And the JSON response should include:
    """
    %{myChildrenOnlyEventId}
    """
    And the JSON response should not include:
    """
    %{otherChildrenOnlyEventId}
    """
    And I send a GET request to "/events" with parameters:
      | q | id:(%{otherChildrenOnlyEventId} OR %{myChildrenOnlyEventId}) |
    And the JSON response should not include:
    """
    %{myChildrenOnlyEventId}
    """
    And the JSON response should not include:
    """
    %{otherChildrenOnlyEventId}
    """

  Scenario: When I have the boa scope I can search for all children only events
    When I am authorized with an OAuth client access token for "test_client"
    And I create an event from "events/event-children-only.json" and save the "id" as "myChildrenOnlyEventId"
    And I publish the event at "/events/%{myChildrenOnlyEventId}"
    And I wait for the event with url "/events/%{myChildrenOnlyEventId}" to be indexed
    And I am using the Search API v3 base URL
    And I am using a x-client-id header for client "boa_client"
    When I send a GET request to "/events" with parameters:
      | childrenOnly | true                                                 |
      | q            | id:(%{otherChildrenOnlyEventId} OR %{myChildrenOnlyEventId}) |
    And the JSON response should include:
    """
    %{myChildrenOnlyEventId}
    """
    And the JSON response should include:
    """
    %{otherChildrenOnlyEventId}
    """

  Scenario: With an UiTID v1 API that is not matched to a clientId I cannot find any children only events
    When I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I create an event from "events/event-children-only.json" and save the "id" as "myChildrenOnlyEventId"
    And I publish the event at "/events/%{myChildrenOnlyEventId}"
    And I wait for the event with url "/events/%{myChildrenOnlyEventId}" to be indexed
    And I am using the Search API v3 base URL
    And I am not authorized
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    When I send a GET request to "/events" with parameters:
      | childrenOnly | true                                                         |
      | q            | id:(%{otherChildrenOnlyEventId} OR %{myChildrenOnlyEventId}) |
    And the JSON response should not include:
    """
    %{myChildrenOnlyEventId}
    """
    And the JSON response should not include:
    """
    %{otherChildrenOnlyEventId}
    """
