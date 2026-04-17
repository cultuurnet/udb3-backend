@sapi3
Feature: Test the Search API v3 boosting

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create a minimal place and save the "id" as "boostedPlace"
    And I publish the place at "/places/%{boostedPlace}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "boostedEvent"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "nonBoostedevent"
    And I create a random labelname of 10 characters
    And I send a PUT request to "/events/%{boostedEvent}/labels/%{labelname}"
    And I send a PUT request to "/places/%{boostedPlace}/labels/%{labelname}"
    And I wait 2 seconds

  Scenario: I can positively boost search results
    When I am using the Search API v3 base URL
    And I send a GET request to "/offers" with parameters:
      | q           | id:(%{placeId} OR %{boostedPlace} OR %{boostedEvent} OR %{nonBoostedevent}) AND ((labels:%{labelname}^10) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                                                    |
      | limit       | 2                                                                                                                                       |
    Then the JSON response at "totalItems" should be 4
    And the JSON response should include:
    """
    %{boostedEvent}
    """
    And the JSON response should include:
    """
    %{boostedPlace}
    """
    And the JSON response should not include:
    """
    %{nonBoostedevent}
    """
    And the JSON response should not include:
    """
    %{placeId}
    """
    When I send a GET request to "/places" with parameters:
      | q           | id:(%{placeId} OR %{boostedPlace} OR %{boostedEvent} OR %{nonBoostedevent}) AND ((labels:%{labelname}^10) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                                                    |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/places/%{boostedPlace}",
        "@type": "Place"
      },
      {
        "@id": "http://io.uitdatabank.local:80/places/%{placeId}",
        "@type": "Place"

      }
    ]
    """
    When I send a GET request to "/events" with parameters:
      | q           | id:(%{placeId} OR %{boostedPlace} OR %{boostedEvent} OR %{nonBoostedevent}) AND ((labels:%{labelname}^10) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                                                    |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/events/%{boostedEvent}",
        "@type": "Event"
      },
      {
        "@id": "http://io.uitdatabank.local:80/events/%{nonBoostedevent}",
        "@type": "Event"

      }
    ]
    """

  Scenario: I can negatively boost search results
    When I am using the Search API v3 base URL
    And I send a GET request to "/offers" with parameters:
      | q           | id:(%{placeId} OR %{boostedPlace} OR %{boostedEvent} OR %{nonBoostedevent}) AND ((labels:%{labelname}^0.1) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                                                     |
      | limit       | 2                                                                                                                                        |
    Then the JSON response at "totalItems" should be 4
    And the JSON response should include:
    """
    %{nonBoostedevent}
    """
    And the JSON response should include:
    """
    %{placeId}
    """
    And the JSON response should not include:
    """
    %{boostedEvent}
    """
    And the JSON response should not include:
    """
    %{boostedPlace}
    """
    When I send a GET request to "/places" with parameters:
      | q           | id:(%{placeId} OR %{boostedPlace} OR %{boostedEvent} OR %{nonBoostedevent}) AND ((labels:%{labelname}^0.1) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                                                     |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/places/%{placeId}",
        "@type": "Place"

      },
      {
        "@id": "http://io.uitdatabank.local:80/places/%{boostedPlace}",
        "@type": "Place"
      }
    ]
    """
    When I send a GET request to "/events" with parameters:
      | q           | id:(%{placeId} OR %{boostedPlace} OR %{boostedEvent} OR %{nonBoostedevent}) AND ((labels:%{labelname}^0.1) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                                                     |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/events/%{nonBoostedevent}",
        "@type": "Event"

      },
      {
        "@id": "http://io.uitdatabank.local:80/events/%{boostedEvent}",
        "@type": "Event"
      }
    ]
    """
