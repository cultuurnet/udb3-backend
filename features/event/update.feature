Feature: Test the UDB3 events API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

    Given I set the form data properties to:
      | description     | publiq logo |
      | copyrightHolder | publiq vzw  |
      | language        | nl          |
    When I upload "file" from path "images/publiq.png" to "/images/"
    And I keep the value of the JSON response at "imageId" as "image_id"

    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-referenced-location.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"

  Scenario: Update event target audience to education
    When I set the JSON request payload to:
        """
        { "audienceType": "education" }
        """
    When I send a PUT request to "/events/%{uuid_testevent}/audience"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "audience/audienceType" should be "education"

  Scenario: Update event target audience to everyone
    When I set the JSON request payload to:
          """
          { "audienceType": "everyone" }
          """
    When I send a PUT request to "/events/%{uuid_testevent}/audience"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "audience/audienceType" should be "everyone"

  Scenario: Update event price info
    And I set the JSON request payload to:
        """
        [
        {
          "category": "base",
          "name": {
            "nl": "Basistarief",
            "fr": "Tarif de base",
            "en": "Base tariff",
            "de": "Basisrate"
          },
          "price": 10,
          "priceCurrency": "EUR"
        }
        ]
        """
    When I send a PUT request to "/events/%{uuid_testevent}/priceInfo"
    Then the response status should be "204"

  Scenario: Update major info
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    Given I set the JSON request payload from "events/legacy/event-with-single-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"
    When I set the JSON request payload to:
          """
          {
            "name": "Updated title",
            "location": "%{uuid_place}",
            "type": {
              "id": "0.17.0.0.0",
              "label": "Route"
            },
            "calendar": {
              "type": "permanent"
            }
          }
          """
    And I send a PUT request to "/events/%{uuid_testevent}/majorInfo"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "name/nl" should be "Updated title"
    And the JSON response at "calendarType" should be "permanent"
    And the JSON response at "terms/0/id" should be "0.17.0.0.0"
    And the JSON response at "location/@id" should be "%{baseUrl}/place/%{uuid_place}"

  Scenario: update booking availability single calendar type
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-single-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "bookingAvailability" should be:
            """
            {"type":"Unavailable"}
            """
    And the JSON response at "location/bookingAvailability" should be:
            """
            {"type": "Available"}
            """
    And the JSON response at "subEvent/0/bookingAvailability" should be:
            """
            {"type": "Unavailable"}
            """
    Given I set the JSON request payload to:
          """
          {"type":"Available"}
          """
    And I send a PUT request to "/events/%{uuid_testevent}/bookingAvailability"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "bookingAvailability" should be:
          """
          {"type":"Available"}
          """
    And the JSON response at "location/bookingAvailability" should be:
          """
          {"type": "Available"}
          """
    And the JSON response at "subEvent/0/bookingAvailability" should be:
          """
          {"type": "Available"}
          """
    Given I set the JSON request payload to:
          """
          {"type":"Unavailable"}
          """
    And I send a PUT request to "/events/%{uuid_testevent}/bookingAvailability"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "bookingAvailability" should be:
          """
          {"type":"Unavailable"}
          """
    And the JSON response at "location/bookingAvailability" should be:
          """
          {"type": "Available"}
          """
    And the JSON response at "subEvent/0/bookingAvailability" should be:
          """
          {"type": "Unavailable"}
          """

  Scenario: update booking availability multiple calendar type
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-multiple-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "bookingAvailability" should be:
            """
            {"type":"Available"}
            """
    And the JSON response at "location/bookingAvailability" should be:
            """
            {"type": "Available"}
            """
    And the JSON response at "subEvent/0/bookingAvailability" should be:
            """
            {"type": "Available"}
            """
    And the JSON response at "subEvent/1/bookingAvailability" should be:
            """
            {"type": "Available"}
            """
    Given I set the JSON request payload to:
          """
          {"type":"Unavailable"}
          """
    And I send a PUT request to "/events/%{uuid_testevent}/bookingAvailability"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "bookingAvailability" should be:
          """
          {"type":"Unavailable"}
          """
    And the JSON response at "location/bookingAvailability" should be:
          """
          {"type": "Available"}
          """
    And the JSON response at "subEvent/0/bookingAvailability" should be:
          """
          {"type": "Unavailable"}
          """
    And the JSON response at "subEvent/1/bookingAvailability" should be:
          """
          {"type": "Unavailable"}
          """

  Scenario: update booking availability permanent calendar type
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-permanent-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"
    Given I set the JSON request payload to:
          """
          {"type":"Available"}
          """
    And I send a PUT request to "/events/%{uuid_testevent}/bookingAvailability"
    Then the response status should be "400"
    And the JSON response at "type" should be:
          """
          "https://api.publiq.be/probs/uitdatabank/calendar-type-not-supported"
          """
    And the JSON response at "title" should be:
          """
          "Calendar type not supported"
          """
    And the JSON response at "detail" should be:
          """
          "Updating booking availability on calendar type: \u0022PERMANENT\u0022 is not supported. Only single and multiple calendar types can be updated."
          """

  Scenario: update booking availability periodic calendar type
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-periodic-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"
    Given I set the JSON request payload to:
          """
          {"type":"Available"}
          """
    And I send a PUT request to "/events/%{uuid_testevent}/bookingAvailability"
    Then the response status should be "400"
    And the JSON response at "type" should be:
          """
          "https://api.publiq.be/probs/uitdatabank/calendar-type-not-supported"
          """
    And the JSON response at "title" should be:
          """
          "Calendar type not supported"
          """
    And the JSON response at "detail" should be:
          """
          "Updating booking availability on calendar type: \u0022PERIODIC\u0022 is not supported. Only single and multiple calendar types can be updated."
          """

  Scenario: update booking availability of one sub event to Available
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-multiple-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"
    Given I set the JSON request payload to:
          """
            [
              {
                "id": 1,
                "bookingAvailability": {
                  "type": "Unavailable"
                }
              }
            ]
          """
    And I send a PATCH request to "/events/%{uuid_testevent}/subEvents"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "bookingAvailability" should be:
          """
          {"type":"Available"}
          """
    And the JSON response at "subEvent/0/bookingAvailability" should be:
          """
          {"type": "Available"}
          """
    And the JSON response at "subEvent/1/bookingAvailability" should be:
          """
          {"type": "Unavailable"}
          """

  Scenario: Update sub event with end date before start date
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-multiple-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"
    Given I set the JSON request payload to:
          """
            [
              {
                "id": 0,
                "endDate": "2010-05-17T19:00:00+00:00"
              }
            ]
          """
    And I send a PATCH request to "/events/%{uuid_testevent}/subEvents"
    Then the response status should be "400"
    And the JSON response should be:
         """
         {
           "type": "https://api.publiq.be/probs/body/invalid-data",
           "title": "Invalid body data",
           "status": 400,
           "detail": "End date can not be earlier than start date."
         }
         """

  Scenario: add a video to an event and then delete it
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-permanent-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"
    Given I set the JSON request payload to:
          """
          {
            "url": "https://www.youtube.com/watch?v=sddser23",
            "copyrightHolder": "I am the owner",
            "language": "nl"
          }
          """
    And I send a POST request to "/events/%{uuid_testevent}/videos/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "videoId" as "uuid_video"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "videos" should be:
          """
          [{
            "id": "%{uuid_video}",
            "url": "https://www.youtube.com/watch?v=sddser23",
            "embedUrl": "https://www.youtube.com/embed/sddser23",
            "language": "nl",
            "copyrightHolder": "I am the owner"
          }]
          """
    Given I send a DELETE request to "/events/%{uuid_testevent}/videos/%{uuid_video}"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"

  Scenario: add a video to an event and then update it
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-permanent-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"
    Given I set the JSON request payload to:
          """
          {
            "url": "https://www.youtube.com/watch?v=sddser23",
            "copyrightHolder": "I am the owner",
            "language": "nl"
          }
          """
    And I send a POST request to "/events/%{uuid_testevent}/videos/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "videoId" as "uuid_video"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "videos" should be:
          """
          [{
            "id": "%{uuid_video}",
            "url": "https://www.youtube.com/watch?v=sddser23",
            "embedUrl": "https://www.youtube.com/embed/sddser23",
            "language": "nl",
            "copyrightHolder": "I am the owner"
          }]
          """
    Given I set the JSON request payload to:
          """
          [{
            "id": "%{uuid_video}",
            "url": "https://www.youtube.com/watch?v=123"
          }]
          """
    And I send a PATCH request to "/events/%{uuid_testevent}/videos/"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "videos" should be:
            """
            [{
              "id": "%{uuid_video}",
              "url": "https://www.youtube.com/watch?v=123",
              "embedUrl": "https://www.youtube.com/embed/123",
              "language": "nl",
              "copyrightHolder": "I am the owner"
            }]
            """
    Given I set the JSON request payload to:
          """
          [{
            "id": "%{uuid_video}",
            "copyrightHolder": "publiq",
            "language": "fr"
          }]
          """
    And I send a PATCH request to "/events/%{uuid_testevent}/videos/"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "videos" should be:
            """
            [{
              "id": "%{uuid_video}",
              "url": "https://www.youtube.com/watch?v=123",
              "embedUrl": "https://www.youtube.com/embed/123",
              "language": "fr",
              "copyrightHolder": "publiq"
            }]
            """

  Scenario: Update event type
    When I send a PUT request to "/events/%{uuid_testevent}/type/0.5.0.0.0"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "terms/1/id" should be "0.5.0.0.0"

  Scenario: Update event type with type that is available till start
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "availableTo" should be "2020-05-12T21:00:00+00:00"
    When I send a PUT request to "/events/%{uuid_testevent}/type/0.57.0.0.0"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the JSON response at "availableTo" should be "2020-05-05T18:00:00+00:00"
    And the JSON response at "terms/1/id" should be "0.57.0.0.0"

  Scenario: Update event type from type that is available till start
    When I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create an event from "events/event-with-eventtype-lessenreeks.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "availableTo" should be "2021-05-17T08:00:00+00:00"
    When I send a PUT request to "%{eventUrl}/type/0.50.4.0.0"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "availableTo" should be "2021-05-18T22:00:00+00:00"
    And the JSON response at "terms/1/id" should be "0.50.4.0.0"

  Scenario: Update event type with term id that is not an eventtype
    When I send a PUT request to "/events/%{uuid_testevent}/type/1.17.0.0.0"
    Then the response status should be "404"
    And the JSON response should be:
         """
         {
           "type": "https://api.publiq.be/probs/url/not-found",
           "title": "Not Found",
           "status": 404,
           "detail": "Category with id 1.17.0.0.0 not found in eventtype domain or not applicable for Event."
         }
         """

  Scenario: Update event type with term id that does not exist
    When I send a PUT request to "/events/%{uuid_testevent}/type/foo"
    Then the response status should be "404"
    And the JSON response should be:
         """
         {
           "type": "https://api.publiq.be/probs/url/not-found",
           "title": "Not Found",
           "status": 404,
           "detail": "Category with id foo not found in eventtype domain or not applicable for Event."
         }
         """

  Scenario: Update event type with term id that is not an eventtype for events
    When I send a PUT request to "/events/%{uuid_testevent}/type/3CuHvenJ+EGkcvhXLg9Ykg"
    Then the response status should be "404"
    And the JSON response should be:
         """
         {
           "type": "https://api.publiq.be/probs/url/not-found",
           "title": "Not Found",
           "status": 404,
           "detail": "Category with id 3CuHvenJ+EGkcvhXLg9Ykg not found in eventtype domain or not applicable for Event."
         }
         """

  Scenario: Update event theme
    When I send a PUT request to "/events/%{uuid_testevent}/theme/1.8.3.5.0"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "terms/1/id" should be "1.8.3.5.0"

  Scenario: Update event theme with term id that is not a theme
    When I send a PUT request to "/events/%{uuid_testevent}/theme/3CuHvenJ+EGkcvhXLg9Ykg"
    Then the response status should be "404"
    And the JSON response should be:
         """
         {
           "type": "https://api.publiq.be/probs/url/not-found",
           "title": "Not Found",
           "status": 404,
           "detail": "Category with id 3CuHvenJ+EGkcvhXLg9Ykg not found in theme domain."
         }
         """

  Scenario: Update event theme with term id that does not exist
    When I send a PUT request to "/events/%{uuid_testevent}/theme/foo"
    Then the response status should be "404"
    And the JSON response should be:
         """
         {
           "type": "https://api.publiq.be/probs/url/not-found",
           "title": "Not Found",
           "status": 404,
           "detail": "Category with id foo not found in theme domain."
         }
         """

  Scenario: Delete event theme
    When I send a DELETE request to "/events/%{uuid_testevent}/theme/"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response should not have "terms/1"

  Scenario: Update event facilities in legacy format
    Given I set the JSON request payload to:
      """
      {
        "facilities": [
          "3.13.1.0.0",
          "3.13.2.0.0"
        ]
      }
      """
    When I send a PUT request to "/events/%{uuid_testevent}/facilities"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "terms/0/id" should be "0.50.4.0.0"
    And the JSON response at "terms/1/id" should be "1.8.2.0.0"
    And the JSON response at "terms/2/id" should be "3.13.1.0.0"
    And the JSON response at "terms/3/id" should be "3.13.2.0.0"

  Scenario: Update event facilities
    When I set the JSON request payload to:
      """
      [
        "3.31.0.0.0",
        "3.32.0.0.0"
      ]
      """
    And I send a PUT request to "/events/%{uuid_testevent}/facilities"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "terms/0/id" should be "0.50.4.0.0"
    And the JSON response at "terms/1/id" should be "1.8.2.0.0"
    And the JSON response at "terms/2/id" should be "3.31.0.0.0"
    And the JSON response at "terms/3/id" should be "3.32.0.0.0"

  Scenario: Update event facilities with invalid facility id
    When I set the JSON request payload to:
      """
      [
        "3.33.0.0.0",
        "foobar"
      ]
      """
    And I send a PUT request to "/events/%{uuid_testevent}/facilities"
    Then the response status should be "400"
    And the JSON response should be:
      """
      {
        "type": "https://api.publiq.be/probs/body/invalid-data",
        "title": "Invalid body data",
        "status": 400,
        "detail": "Category with id foobar not found in facility domain or not applicable for Event."
      }
      """
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "terms/0/id" should be "0.50.4.0.0"
    And the JSON response at "terms/1/id" should be "1.8.2.0.0"

  Scenario: Update event facilities without permissions
    Given I am authorized as JWT provider v1 user "validator_diest"
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-permanent-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"
    When I set the JSON request payload to:
      """
      [
        "3.33.0.0.0"
      ]
      """
    And I send a PUT request to "/events/%{uuid_testevent}/facilities"
    Then the response status should be "403"
    And the JSON response should be:
      """
      {
        "type": "https://api.publiq.be/probs/auth/forbidden",
        "title": "Forbidden",
        "status": 403,
        "detail": "User 50cc85fa-f278-44c5-a16b-b9db50ee93f6 has no permission \"Voorzieningen bewerken\" on resource %{uuid_testevent}"
      }
      """

  Scenario: Update all fields of an event with complete overwrite
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/legacy/event-with-permanent-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    Given I set the JSON request payload from "events/event-with-all-fields.json"
    When I send a PUT request to "/events/%{eventId}"
    Then the response status should be "200"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "/events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name" should be:
    """
    {
      "nl": "Event met alle velden",
      "en": "Event with all fields"
    }
    """
    And the JSON response at "terms" should be:
    """
    [
      {
        "id": "0.50.4.0.0",
        "label": "Concert",
        "domain": "eventtype"
      },
      {
        "id": "1.8.3.1.0",
        "label": "Pop en rock",
        "domain": "theme"
      }
    ]
    """
    And the JSON response at "calendarType" should be "multiple"
    And the JSON response at "startDate" should be "2021-05-17T08:00:00+00:00"
    And the JSON response at "endDate" should be "2021-05-18T22:00:00+00:00"
    And the JSON response at "status" should be:
    """
    {
      "type": "Available",
      "reason": {
        "en": "English reason",
        "nl": "Nederlandse reden"
      }
    }
    """
    And the JSON response at "subEvent" should be:
    """
    [
      {
        "id": 0,
        "@type": "Event",
        "startDate": "2021-05-17T08:00:00+00:00",
        "endDate": "2021-05-17T22:00:00+00:00",
        "status": {
          "type": "Unavailable",
          "reason": {
            "nl": "Nederlandse reden onbeschikbaar",
            "en": "English reason unavailable"
          }
        },
        "bookingAvailability": {
          "type": "Unavailable"
        }
      },
      {
        "id": 1,
        "@type": "Event",
        "startDate": "2021-05-18T08:00:00+00:00",
        "endDate": "2021-05-18T22:00:00+00:00",
        "status": {
          "type": "Available",
          "reason": {
            "nl": "Nederlandse reden",
            "en": "English reason"
          }
        },
        "bookingAvailability": {
          "type": "Available"
        }
      }
    ]
    """
    And the JSON response at "typicalAgeRange" should be "6-12"
    And the JSON response at "description" should be:
    """
    {
      "nl": "Nederlandse beschrijving",
      "en": "English description"
    }
    """
    And the JSON response at "priceInfo" should be:
    """
    [
      {
        "category": "base",
        "price": 10.5,
        "priceCurrency": "EUR",
        "name": {
          "nl": "Basistarief",
          "fr": "Tarif de base",
          "en": "Base tariff",
          "de": "Basisrate"
        }
      }
    ]
    """
    And the JSON response at "contactPoint" should be:
    """
    {
      "phone": [
        "string"
      ],
      "email": [
        "info@example.com"
      ],
      "url": [
        "https://www.example.com"
      ]
    }
    """
    And the JSON response at "bookingInfo" should be:
    """
    {
      "phone": "string",
      "email": "info@example.com",
      "url": "https://www.example.com",
      "urlLabel": {
        "nl": "Nederlandse beschrijving",
        "en": "English description"
      },
      "availabilityStarts": "2021-05-17T22:00:00+00:00",
      "availabilityEnds": "2021-05-17T22:00:00+00:00"
    }
    """
    And the JSON response at "mediaObject" should be:
    """
    [{
      "@id": "%{baseUrl}/images/%{image_id}",
      "@type": "schema:ImageObject",
      "id": "%{image_id}",
      "contentUrl": "https://images.uitdatabank.dev/%{image_id}.png",
      "thumbnailUrl": "https://images.uitdatabank.dev/%{image_id}.png",
      "copyrightHolder": "publiq vzw",
      "description": "A nice logo",
      "inLanguage": "en"
    }]
    """
    And the JSON response at "image" should be "https://images.uitdatabank.dev/%{image_id}.png"
    And the JSON response at "videos" should be:
    """
    [{
      "id": "b504cf44-9ab8-4641-9934-38d1cc67242c",
      "url": "https://www.youtube.com/watch?v=cEItmb_a20D",
      "embedUrl": "https://www.youtube.com/embed/cEItmb_a20D",
      "language": "nl",
      "copyrightHolder": "publiq"
    }]
    """
    And the JSON response at "labels" should be:
    """
    ["public-visible"]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    ["public-invisible"]
    """
