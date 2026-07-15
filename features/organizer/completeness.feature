Feature: Test completeness score for organizers

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

  Scenario: An organizer with all fields filled in scores a completeness of 100
    When I create an organizer from "organizers/completeness/organizer-complete.json" and save the "url" as "organizerUrl"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "completeness" should be 100
