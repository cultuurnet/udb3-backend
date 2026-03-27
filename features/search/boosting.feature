@sapi3
Feature: Test the Search API v3 boosting

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: I can positively boost search results
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventIdToBeBoosted"
    And I create a random labelname of 10 characters
    And I send a PUT request to "/events/%{eventIdToBeBoosted}/labels/%{labelname}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventIdNotToBeBoosted"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | q           | id:(%{eventIdToBeBoosted} OR %{eventIdNotToBeBoosted}) AND ((labels:%{labelname}^10) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                               |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/event/%{eventIdToBeBoosted}",
        "@type": "Event"
      },
      {
        "@id": "http://io.uitdatabank.local:80/event/%{eventIdNotToBeBoosted}",
        "@type": "Event"

      }
    ]
    """
    When I send a GET request to "/events" with parameters:
      | q           | id:(%{eventIdToBeBoosted} OR %{eventIdNotToBeBoosted}) AND ((labels:%{labelname}^10) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                               |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/event/%{eventIdToBeBoosted}",
        "@type": "Event"
      },
      {
        "@id": "http://io.uitdatabank.local:80/event/%{eventIdNotToBeBoosted}",
        "@type": "Event"

      }
    ]
    """

  Scenario: I can negatively boost search results
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventIdToBeBoosted"
    And I create a random labelname of 10 characters
    And I send a PUT request to "/events/%{eventIdToBeBoosted}/labels/%{labelname}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventIdNotToBeBoosted"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | q           | id:(%{eventIdToBeBoosted} OR %{eventIdNotToBeBoosted}) AND ((labels:%{labelname}^0.1) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                               |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/event/%{eventIdNotToBeBoosted}",
        "@type": "Event"
      },
      {
        "@id": "http://io.uitdatabank.local:80/event/%{eventIdToBeBoosted}",
        "@type": "Event"
      }
    ]
    """
    When I send a GET request to "/events" with parameters:
      | q           | id:(%{eventIdToBeBoosted} OR %{eventIdNotToBeBoosted}) AND ((labels:%{labelname}^0.1) OR (NOT labels:%{labelname})) |
      | sort[score] | desc                                                                                                               |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/event/%{eventIdNotToBeBoosted}",
        "@type": "Event"
      },
      {
        "@id": "http://io.uitdatabank.local:80/event/%{eventIdToBeBoosted}",
        "@type": "Event"
      }
    ]
    """
