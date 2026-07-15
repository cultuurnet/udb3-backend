Feature: Test completeness score for places

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
    And I create an organizer from "organizers/organizer.json" and save the "organizerId" as "organizerId"

  Scenario: A place with all fields filled in scores a completeness of 100
    When I create a place from "places/completeness/place-complete.json" and save the "url" as "placeUrl"
    And I get the place at "%{placeUrl}"
    Then the JSON response should not have "faqs"
    And the JSON response should have "organizer"
    And the JSON response should have "videos"
    And the JSON response at "bookingAvailability/capacity" should be 200
    And the JSON response at "completeness" should be 100
