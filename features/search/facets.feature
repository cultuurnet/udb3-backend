@sapi3
Feature: Test the Search API v3 facets

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I create a place from "places/place.json" and save the "placeId" as "uuid_place"
    And I keep the value of the JSON response at "url" as "placeUrl"
    And I create an event from "/events/event-with-eventtype-lessenreeks.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    And I am not authorized

  Scenario: Search for type facets
    Given I send a GET request to "/events" with parameters:
      | limit                 | 1 |
      | disableDefaultFilters | true |
      | q                     | id:%{eventId} |
      | facets[]              | types         |
    Then the JSON response at "facet" should be:
    """
    {
       "types":{
         "0.3.1.0.0":{
           "name":{
             "nl":"Lessenreeks",
             "fr":"S\u00e9rie de cours",
             "de":"Unterrichtsreihe",
             "en":"Course series"
           },
           "count":1
        }
      }
    }
    """

