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

  Scenario: When I do not have the boa scope I can only find children only events created by myself
    When I am authorized with an OAuth client access token for "test_client"
    And I create an event from "events/audience-type/event-audience-type-children-only.json" and save the "id" as "myChildrenOnlyEventId"
    And I publish the event at "/events/%{myChildrenOnlyEventId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    And I am using a x-client-id header for client "test_client"
    And I send a GET request to "/events" with parameters:
      | audienceType | childrenOnly                                                 |
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
    And I send a GET request to "/events" with parameters:
      | q            | id:(%{otherChildrenOnlyEventId} OR %{myChildrenOnlyEventId}) |
    Then the JSON response at "totalItems" should be 0
    And the JSON response should not include:
    """
    %{myChildrenOnlyEventId}
    """
    And the JSON response should not include:
    """
    %{otherChildrenOnlyEventId}
    """

  Scenario: disabling audience types should return events for everyone, members & my own childrenOnlyEvents
    When I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create an event from "events/audience-type/event-audience-type-members.json" and save the "id" as "membersEventId"
    And I publish the event at "/events/%{membersEventId}"
    And I create an event from "events/audience-type/event-audience-type-education.json" and save the "id" as "educationEventId"
    And I publish the event at "/events/%{educationEventId}"
    And I create a minimal permanent event and save the "id" as "everyoneEventId"
    And I publish the event at "/events/%{everyoneEventId}"
    And I am not authorized
    And I am not using an UiTID v1 API key
    And I am authorized with an OAuth client access token for "test_client"
    And I create an event from "events/audience-type/event-audience-type-children-only.json" and save the "id" as "myChildrenOnlyEventId"
    And I publish the event at "/events/%{myChildrenOnlyEventId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    And I am using a x-client-id header for client "test_client"
    And I send a GET request to "/events" with parameters:
      | audienceType | *                                                                                                                              |
      | q            | id:(%{otherChildrenOnlyEventId} OR %{myChildrenOnlyEventId} OR %{membersEventId} OR %{educationEventId} OR %{everyoneEventId}) |
    Then the JSON response at "totalItems" should be 4
    And the JSON response should include:
    """
    %{myChildrenOnlyEventId}
    """
    And the JSON response should include:
    """
    %{membersEventId}
    """
    And the JSON response should include:
    """
    %{educationEventId}
    """
    And the JSON response should include:
    """
    %{everyoneEventId}
    """
    And the JSON response should not include:
    """
    %{otherChildrenOnlyEventId}
    """

  # Todo: this test cannot work till PK-476 is done, so in the meantime it is marked as external
  @external
  Scenario: When I have the boa scope I can search for all children only events
    When I am authorized with an OAuth client access token for "boa_client"
    And I create an event from "events/audience-type/event-audience-type-children-only.json" and save the "id" as "myChildrenOnlyEventId"
    And I publish the event at "/events/%{myChildrenOnlyEventId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    And I am using a x-client-id header for client "boa_client"
    And I send a GET request to "/events" with parameters:
      | audienceType | childrenOnly                                                 |
      | q            | id:(%{otherChildrenOnlyEventId} OR %{myChildrenOnlyEventId}) |
    Then the JSON response at "totalItems" should be 2
    And the JSON response should include:
    """
    %{myChildrenOnlyEventId}
    """
    And the JSON response should include:
    """
    %{otherChildrenOnlyEventId}
    """
