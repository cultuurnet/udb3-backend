Feature: Test completeness score for events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I set the form data properties to:
      | description     | logo |
      | copyrightHolder | me   |
      | language        | nl   |
    And I upload "file" from path "images/udb.jpg" to "/images/"
    And the response status should be "201"
    And I keep the value of the JSON response at "imageId" as "imageId"
    And I create a place from "places/place.json" and save the "id" as "placeId"
    And I create an organizer from "organizers/organizer.json" and save the "organizerId" as "organizerId"

  Scenario: An event with all fields filled in scores a completeness of 100
    When I create an event from "events/completeness/event-everybody-complete.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response should not have "childrenOnly"
    And the JSON response at "completeness" should be 100

  Scenario: A childrenOnly event with all fields filled in scores a completeness of 100
    When I create an event from "events/completeness/event-children-only-complete.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "childrenOnly" should be true
    And the JSON response at "completeness" should be 100
