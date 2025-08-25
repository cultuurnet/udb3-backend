Feature: Test calendar summary on places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/legacy/create-periodic-place.json" and save the "url" as "placeUrl"

  Scenario: Get the calendar summary of a place
    Given I am not authorized
    When I send a GET request to "%{placeUrl}/calendar-summary"
    Then the response status should be "200"
    And the content type should be "text/plain"
    And the body should be "Van zaterdag 1 januari 2022 tot en met donderdag 1 januari 2032"

  Scenario: Get the small text calendar summary of a place
    Given I am not authorized
    When I send a GET request to "%{placeUrl}/calendar-summary?size=sm&style=text"
    Then the response status should be "200"
    And the content type should be "text/plain"
    And the body should be "Tot do 1 jan 2032"

  Scenario: Get the calendar summary of a place with legacy endpoint
    Given I am not authorized
    When I send a GET request to "%{placeUrl}/calsum"
    Then the response status should be "200"
    And the content type should be "text/plain"
    And the body should be "Van zaterdag 1 januari 2022 tot en met donderdag 1 januari 2032"
