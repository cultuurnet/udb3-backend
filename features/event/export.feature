Feature: Test the UDB3 events export API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I set the JSON request payload from "places/place.json"
    And I send a POST request to "/places/"
    And the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-referenced-location.json"
    And I send a POST request to "/events/"
    And the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent_export"

  Scenario: Export events to OOXML - basic
    Given I set the JSON request payload from "exports/event-export-ooxml-basic.json"
    When I send a POST request to "/events/export/ooxml"
    Then the response status should be "200"
    And I keep the value of the JSON response at "commandId" as "id_ooxml-basic"
    And I wait for the command with id "%{id_ooxml-basic}" to complete

  Scenario: Export events to OOXML - full
    Given I set the JSON request payload from "exports/event-export-ooxml-full.json"
    When I send a POST request to "/events/export/ooxml"
    Then the response status should be "200"
    And I keep the value of the JSON response at "commandId" as "id_ooxml-full"
    And I wait for the command with id "%{id_ooxml-full}" to complete

  Scenario: Export events to PDF - tipsrapport
    Given I set the JSON request payload from "exports/event-export-pdf-tips.json"
    When I send a POST request to "/events/export/pdf"
    Then the response status should be "200"
    And I keep the value of the JSON response at "commandId" as "id_pdf-tips"
    And I wait for the command with id "%{id_pdf-tips}" to complete

  Scenario: Export events to PDF - mapview
    Given I set the JSON request payload from "exports/event-export-pdf-map.json"
    When I send a POST request to "/events/export/pdf"
    Then the response status should be "200"
    And I keep the value of the JSON response at "commandId" as "id_pdf-map"
    And I wait for the command with id "%{id_pdf-map}" to complete
