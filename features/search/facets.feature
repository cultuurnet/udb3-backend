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

  Scenario: Search for region facets
    Given I send a GET request to "/events" with parameters:
      | limit                 | 1 |
      | disableDefaultFilters | true |
      | q                     | id:%{eventId} |
      | facets[]              | regions       |
    Then the JSON response at "facet" should be:
    """
    {
      "regions":{
        "nis-20001":{
          "name":{
            "nl":"Provincie Vlaams-Brabant",
            "fr":"Province du Brabant flamand",
            "de":"Provinz Fl\u00e4misch-Brabant"
          },
          "count":1,
          "children":{
            "reg-hageland":{
              "name":{
                "nl":"Hageland"
              },
              "count":1,
              "children":{
                "nis-24134":{
                  "name":{
                    "nl":"Scherpenheuvel-Zichem"
                  },
                  "count":1,
                  "children":{
                    "nis-24134B":{
                      "name":{
                        "nl":"Zichem"
                      },
                      "count":1
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    """

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

  Scenario: Search for theme facets
    Given I send a GET request to "/events" with parameters:
      | limit                 | 1 |
      | disableDefaultFilters | true |
      | q                     | id:%{eventId} |
      | facets[]              | themes        |
    Then the JSON response at "facet" should be:
    """
    {
       "themes":{
         "1.51.12.0.0":{
           "name":{
             "nl":"Omnisport en andere",
             "fr":"Omnisports et autres",
             "de":"Omnisport und andere",
             "en":"Other sports"
           },
           "count":1
        }
      }
    }
    """
