@sapi3
Feature: Test the Search API v3 boosting

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create a minimal place and save the "id" as "boostedPlace"
    And I publish the place at "/places/%{boostedPlace}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "boostedEvent"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "nonBoostedeventId"
    And I create a random labelname of 10 characters
    And I send a PUT request to "/events/%{boostedEvent}/labels/%{labelname}"
    And I send a PUT request to "/places/%{boostedPlace}/labels/%{labelname}"
    And I wait 2 seconds

  Scenario: I can positively boost search results
    When I am using the Search API v3 base URL
    And I send a GET request to "/offers" with parameters:
      | q           | id:(%{uuid_place} OR %{boostedPlace} OR %{boostedEvent} OR %{nonBoostedeventId}) AND ((labels:%{labelname}^10) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                                                         |
      | limit       | 2                                                                                                                                            |
    Then the JSON response at "totalItems" should be 4
    And show me the unparsed response
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
    %{nonBoostedeventId}
    """
    And the JSON response should not include:
    """
    %{uuid_place}
    """
    When I send a GET request to "/places" with parameters:
      | q           | id:(%{uuid_place} OR %{boostedPlace} OR %{boostedEvent} OR %{nonBoostedeventId}) AND ((labels:%{labelname}^10) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                               |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/place/%{boostedPlace}",
        "@type": "Place"
      },
      {
        "@id": "http://io.uitdatabank.local:80/place/%{uuid_place}",
        "@type": "Place"

      }
    ]
    """
    When I send a GET request to "/events" with parameters:
      | q           | id:(%{uuid_place} OR %{boostedPlace} OR %{boostedEvent} OR %{nonBoostedeventId}) AND ((labels:%{labelname}^10) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                               |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/event/%{boostedEvent}",
        "@type": "Event"
      },
      {
        "@id": "http://io.uitdatabank.local:80/event/%{nonBoostedeventId}",
        "@type": "Event"

      }
    ]
    """

  Scenario: I can negatively boost search results
    When I am using the Search API v3 base URL
    And I send a GET request to "/offers" with parameters:
      | q           | id:(%{uuid_place} OR %{boostedPlace} OR %{boostedEvent} OR %{nonBoostedeventId}) AND ((labels:%{labelname}^0.1) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                                                         |
      | limit       | 2                                                                                                                                            |
    Then the JSON response at "totalItems" should be 4
    And show me the unparsed response
    And the JSON response should include:
    """
    %{nonBoostedeventId}
    """
    And the JSON response should include:
    """
    %{uuid_place}
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
      | q           | id:(%{uuid_place} OR %{boostedPlace} OR %{boostedEvent} OR %{nonBoostedeventId}) AND ((labels:%{labelname}^0.1) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                               |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/place/%{uuid_place}",
        "@type": "Place"

      },
      {
        "@id": "http://io.uitdatabank.local:80/place/%{boostedPlace}",
        "@type": "Place"
      }
    ]
    """
    When I send a GET request to "/events" with parameters:
      | q           | id:(%{uuid_place} OR %{boostedPlace} OR %{boostedEvent} OR %{nonBoostedeventId}) AND ((labels:%{labelname}^0.1) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                               |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/event/%{nonBoostedeventId}",
        "@type": "Event"

      },
      {
        "@id": "http://io.uitdatabank.local:80/event/%{boostedEvent}",
        "@type": "Event"
      }
    ]
    """
