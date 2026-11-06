@sapi3
Feature: Test the Search API v3 childrenOnly filter

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-children-only.json" and save the "id" as "otherChildrenOnlyEventId"
    And I publish the event at "/events/%{otherChildrenOnlyEventId}"
    And I create an event from "events/event-minimal-permanent.json" and save the "id" as "basicEventId"
    And I publish the event at "/events/%{basicEventId}"
    And I am using a x-client-id header for client "test_client"
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I wait for the event with url "/events/%{otherChildrenOnlyEventId}" to be indexed
    And I wait for the event with url "/events/%{basicEventId}" to be indexed
    And I am not using a x-client-id header
    And I am not authorized
    And I am not using an UiTID v1 API key

  Scenario: Without childrenOnly parameter I find all children only events
    When I am authorized with an OAuth client access token for "test_client"
    And I create an event from "events/event-children-only.json" and save the "id" as "myChildrenOnlyEventId"
    And I publish the event at "/events/%{myChildrenOnlyEventId}"
    And I am using a x-client-id header for client "test_client"
    And I wait for the event with url "/events/%{myChildrenOnlyEventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q | id:(%{otherChildrenOnlyEventId} OR %{myChildrenOnlyEventId} OR %{basicEventId}) |
    And the JSON response should include:
    """
    %{myChildrenOnlyEventId}
    """
    And the JSON response should include:
    """
    %{otherChildrenOnlyEventId}
    """
    And the JSON response should include:
    """
    %{basicEventId}
    """

  Scenario: With childrenOnly=true I only find children only events
    When I am authorized with an OAuth client access token for "test_client"
    And I create an event from "events/event-children-only.json" and save the "id" as "myChildrenOnlyEventId"
    And I publish the event at "/events/%{myChildrenOnlyEventId}"
    And I am using a x-client-id header for client "test_client"
    And I wait for the event with url "/events/%{myChildrenOnlyEventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | childrenOnly | true                                                                           |
      | q            | id:(%{otherChildrenOnlyEventId} OR %{myChildrenOnlyEventId} OR %{basicEventId}) |
    And the JSON response should include:
    """
    %{myChildrenOnlyEventId}
    """
    And the JSON response should include:
    """
    %{otherChildrenOnlyEventId}
    """
    And the JSON response should not include:
    """
    %{basicEventId}
    """

  Scenario: With childrenOnly=false I find no children only events
    When I am authorized with an OAuth client access token for "test_client"
    And I create an event from "events/event-children-only.json" and save the "id" as "myChildrenOnlyEventId"
    And I publish the event at "/events/%{myChildrenOnlyEventId}"
    And I am using a x-client-id header for client "test_client"
    And I wait for the event with url "/events/%{myChildrenOnlyEventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | childrenOnly | false                                                                          |
      | q            | id:(%{otherChildrenOnlyEventId} OR %{myChildrenOnlyEventId} OR %{basicEventId}) |
    And the JSON response should include:
    """
    %{basicEventId}
    """
    And the JSON response should not include:
    """
    %{myChildrenOnlyEventId}
    """
    And the JSON response should not include:
    """
    %{otherChildrenOnlyEventId}
    """

  Scenario: With an UiTID v1 API key that is not matched to a clientId I find all children only events
    When I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I create an event from "events/event-children-only.json" and save the "id" as "myChildrenOnlyEventId"
    And I publish the event at "/events/%{myChildrenOnlyEventId}"
    And I am using a x-client-id header for client "test_client"
    And I wait for the event with url "/events/%{myChildrenOnlyEventId}" to be indexed
    And I am not using a x-client-id header
    And I am using the Search API v3 base URL
    And I am not authorized
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    When I send a GET request to "/events" with parameters:
      | childrenOnly | true                                                                           |
      | q            | id:(%{otherChildrenOnlyEventId} OR %{myChildrenOnlyEventId} OR %{basicEventId}) |
    And the JSON response should include:
    """
    %{myChildrenOnlyEventId}
    """
    And the JSON response should include:
    """
    %{otherChildrenOnlyEventId}
    """