Feature: Test the UDB3 events API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Create an event
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/event-with-unavailable-sub-events.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "bookingAvailability" should be:
        """
        {"type":"Unavailable"}
        """
    And the JSON response at "status" should be:
        """
        {"type":"Unavailable"}
        """
    And the JSON response at "subEvent/0" should be:
        """
        {
          "id": 0,
          "status": {
            "type": "Unavailable"
          },
          "bookingAvailability": {
            "type": "Unavailable"
          },
          "startDate": "2018-05-05T18:00:00+01:00",
          "endDate": "2018-05-05T21:00:00+01:00",
          "@type": "Event"
        }
        """
    And the JSON response at "subEvent/1" should be:
        """
        {
          "id": 1,
          "status": {
            "type": "Unavailable"
          },
          "bookingAvailability": {
            "type": "Unavailable"
          },
          "startDate": "2018-05-06T18:00:00+01:00",
          "endDate": "2018-05-06T21:00:00+01:00",
          "@type": "Event"
        }
        """
    And the JSON response at "completeness" should be 65

  @bugfix # https://jira.uitdatabank.be/browse/III-4669
  Scenario: Create an event with all fields and then remove them by sending null or empty lists in the JSON
    Given I set the form data properties to:
      | description     | logo |
      | copyrightHolder | me   |
      | language        | nl   |
    And I upload "file" from path "images/UDB.jpg" to "/images/"
    And the response status should be "201"
    And I keep the value of the JSON response at "imageId" as "image_id"
    And I create a place from "places/place.json" and save the "placeId" as "uuid_place"
    And I keep the value of the JSON response at "url" as "placeUrl"
    And I create an event from "events/event-with-all-fields.json" and save the "url" as "eventUrl"
    When I update the event at "%{eventUrl}" from "events/event-minimal-permanent-with-null-or-empty-values.json"
    And I get the event at "%{eventUrl}"
    Then the JSON response should not have "labels"
    And the JSON response should not have "hiddenLabels"
    And the JSON response should not have "videos"
    And the JSON response should not have "mediaObject"
    And the JSON response should not have "image"
    And the JSON response at "typicalAgeRange" should be "-"
    And the JSON response at "contactPoint" should be:
    """
    {
      "phone": [],
      "email": [],
      "url": []
    }
    """
    And the JSON response should not have "bookingInfo"
    # Note that description and priceInfo cannot be removed via complete overwrite once set currently
    And the JSON response should have "description"
    And the JSON response should have "priceInfo"
    And the JSON response at "completeness" should be 72

  Scenario: Create an event with a workflow status ready for validation
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/event-with-workflow-status-ready-for-validation.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"

  Scenario: Create an event with a workflow status approved
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/event-with-workflow-status-approved.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "workflowStatus" should be "DRAFT"

  Scenario: Create an event with a workflow status DELETED
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/event-with-workflow-status-deleted.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "workflowStatus" should be "DELETED"

  Scenario: Create an event with a contact point with missing fields
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/event-with-contact-point-missing-fields.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "contactPoint" should be:
    """
    {
      "phone": [],
      "email": ["info@publiq.be"],
      "url": []
    }
    """
    And the JSON response at "completeness" should be 68

  @bugfix # https://jira.uitdatabank.be/browse/III-4672
  Scenario: Create an event with single calendar type but missing subEvent
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/event-with-single-calendar-but-missing-sub-event.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "calendarType" should be "single"
    And the JSON response at "startDate" should be "2021-05-17T08:00:00+00:00"
    And the JSON response at "endDate" should be "2021-05-18T22:00:00+00:00"
    And the JSON response at "subEvent/0" should be:
    """
    {
      "id": 0,
      "status": {
        "type": "Available"
      },
      "bookingAvailability": {
        "type": "Available"
      },
      "startDate": "2021-05-17T08:00:00+00:00",
      "endDate": "2021-05-18T22:00:00+00:00",
      "@type": "Event"
    }
    """
    And the JSON response at "completeness" should be 65

  Scenario: Create an event with multiple calendar but only one sub event that gets converted to single calendar
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/event-with-multiple-calendar-but-only-one-sub-event.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "calendarType" should be "single"
    And the JSON response at "startDate" should be "2021-05-17T08:00:00+00:00"
    And the JSON response at "endDate" should be "2021-05-18T22:00:00+00:00"
    And the JSON response at "subEvent/0" should be:
    """
    {
      "id": 0,
      "status": {
        "type": "Available"
      },
      "bookingAvailability": {
        "type": "Available"
      },
      "startDate": "2021-05-17T08:00:00+00:00",
      "endDate": "2021-05-18T22:00:00+00:00",
      "@type": "Event"
    }
    """

  Scenario: Create an event with single calendar but multiple sub events that gets converted to multiple calendar
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/event-with-single-calendar-but-multiple-sub-events.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "calendarType" should be "multiple"
    And the JSON response at "startDate" should be "2021-05-17T08:00:00+00:00"
    And the JSON response at "endDate" should be "2021-05-25T22:00:00+00:00"
    And the JSON response at "subEvent/0" should be:
    """
    {
      "id": 0,
      "status": {
        "type": "Available"
      },
      "bookingAvailability": {
        "type": "Available"
      },
      "startDate": "2021-05-17T08:00:00+00:00",
      "endDate": "2021-05-18T22:00:00+00:00",
      "@type": "Event"
    }
    """
    And the JSON response at "subEvent/1" should be:
    """
    {
      "id": 1,
      "status": {
        "type": "Available"
      },
      "bookingAvailability": {
        "type": "Available"
      },
      "startDate": "2021-05-24T08:00:00+00:00",
      "endDate": "2021-05-25T22:00:00+00:00",
      "@type": "Event"
    }
    """

  Scenario: Create an event through legacy imports path
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/event-with-unavailable-sub-events.json"
    When I send a POST request to "/imports/events/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "bookingAvailability" should be:
        """
        {"type":"Unavailable"}
        """
    And the JSON response at "status" should be:
        """
        {"type":"Unavailable"}
        """
    And the JSON response at "subEvent/0" should be:
        """
        {
          "id": 0,
          "status": {
            "type": "Unavailable"
          },
          "bookingAvailability": {
            "type": "Unavailable"
          },
          "startDate": "2018-05-05T18:00:00+01:00",
          "endDate": "2018-05-05T21:00:00+01:00",
          "@type": "Event"
        }
        """
    And the JSON response at "subEvent/1" should be:
        """
        {
          "id": 1,
          "status": {
            "type": "Unavailable"
          },
          "bookingAvailability": {
            "type": "Unavailable"
          },
          "startDate": "2018-05-06T18:00:00+01:00",
          "endDate": "2018-05-06T21:00:00+01:00",
          "@type": "Event"
        }
        """

  Scenario: Create an event with videos through legacy imports path
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/event-with-videos.json"
    When I send a POST request to "/imports/events/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "videos/0" should be:
        """
          {
            "id": "5c549a24-bb97-4f83-8ea5-21a6d56aff72",
            "url": "https://vimeo.com/98765432",
            "embedUrl": "https://player.vimeo.com/video/98765432",
            "language": "nl",
            "copyrightHolder": "Copyright afgehandeld door Vimeo"
          }
        """
    And the JSON response at "videos/1" should be:
        """
          {
            "id": "91c75325-3830-4000-b580-5778b2de4548",
            "url": "https://www.youtube.com/watch?v=cEItmb_a20D",
            "embedUrl": "https://www.youtube.com/embed/cEItmb_a20D",
            "language": "nl",
            "copyrightHolder": "publiq"
          }
        """
    And I keep the value of the JSON response at "videos/1/id" as "video_id"

    Given I set the JSON request payload from "events/event-with-updated-video.json"
    When I send a PUT request to "/imports/events/%{eventId}"
    Then the response status should be "200"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "videos/0" should be:
        """
          {
            "id": "5c549a24-bb97-4f83-8ea5-21a6d56aff72",
            "url": "https://www.youtube.com/watch?v=cEItmb_123",
            "embedUrl": "https://www.youtube.com/embed/cEItmb_123",
            "language": "fr",
            "copyrightHolder": "madewithlove"
          }
        """
    And the JSON response at "videos" should have 1 entry
    And the JSON response at "completeness" should be 67

  Scenario: Create an event with organizer through legacy imports path
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "placeId"

    Given I create a random name of 10 characters
    When I set the JSON request payload from "organizers/organizer-minimal.json"
    And I send a POST request to "/organizers/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "organizerId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    Given I set the JSON request payload from "events/event-with-organizer.json"
    When I send a POST request to "/imports/events/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "organizer/@id" should be "%{baseUrl}/organizers/%{organizerId}"
    And the JSON response at "organizer/mainLanguage" should be "nl"
    And the JSON response at "organizer/name/nl" should be "%{name}"

  Scenario: Create event in legacy format with embedded location
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I send a GET request to "/places/%{uuid_place}"
    And I keep the value of the JSON response at "address/nl/streetAddress" as "street_place"
    And I keep the value of the JSON response at "address/nl/addressLocality" as "city_place"
    And I keep the value of the JSON response at "address/nl/postalCode" as "zip_place"
    And I keep the value of the JSON response at "address/nl/addressCountry" as "country_place"
    And I keep the value of the JSON response at "name/nl" as "name_place"
    And I set the JSON request payload from "events/legacy/event-with-embedded-location.json"

    Given I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "eventId"

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "bookingAvailability" should be:
       """
         {
           "type": "Available"
         }
       """
    And the JSON response at "location/bookingAvailability" should be:
       """
         {
           "type": "Available"
         }
       """
    And the JSON response at "subEvent/0/bookingAvailability" should be:
       """
         {
           "type": "Available"
         }
       """
    And the JSON response at "subEvent/1/bookingAvailability" should be:
       """
         {
           "type": "Available"
         }
       """

  Scenario: Create event in legacy JSON format with location id
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/legacy/event-with-referenced-location.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "eventId"

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "location/name/nl" should be "Cafe Den Hemel"

  @bugfix # https://jira.uitdatabank.be/browse/III-4641
  Scenario: Create event in legacy JSON format with location id as a string
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/legacy/event-with-referenced-location-as-string.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "eventId"

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "location/name/nl" should be "Cafe Den Hemel"

  @bugfix # https://jira.uitdatabank.be/browse/III-4644
  Scenario: Create event in legacy JSON format and ignore address
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/legacy/event-with-address.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "eventId"

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "location/name/nl" should be "Cafe Den Hemel"

  Scenario: Create event in legacy JSON format with location id and copy it single calendar
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-referenced-location.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "eventId" as "uuid_event"
    And the response body should be valid JSON

    Given I set the JSON request payload to:
        """
        {
          "calendarType": "single",
          "subEvent": [
            {
              "startDate": "2020-06-05T18:00:00+02:00",
              "endDate": "2020-06-05T21:00:00+02:00"
            }
          ]
        }
        """
    When I send a POST request to "/events/%{uuid_event}/copies"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "new_uuid_event"

    When I send a GET request to "/events/%{new_uuid_event}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "calendarType" should be "single"
    And the JSON response at "subEvent" should be:
        """
        [
            {
              "id": 0,
              "@type": "Event",
              "startDate": "2020-06-05T18:00:00+02:00",
              "endDate": "2020-06-05T21:00:00+02:00",
              "status": {
                "type": "Available"
              },
              "bookingAvailability": {
                "type": "Available"
              }
            }
        ]
        """

  Scenario: Create event in legacy JSON format with location id and copy it with deprecated single calendar
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/legacy/event-with-referenced-location.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "eventId" as "uuid_event"
    And the response body should be valid JSON

    Given I set the JSON request payload to:
        """
        {
          "calendarType": "single",
          "timeSpans": [
            {
              "start": "2020-06-05T18:00:00+02:00",
              "end": "2020-06-05T21:00:00+02:00"
            }
          ]
        }
        """
    When I send a POST request to "/events/%{uuid_event}/copies"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "new_uuid_event"

    When I send a GET request to "/events/%{new_uuid_event}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "calendarType" should be "single"
    And the JSON response at "subEvent" should be:
        """
        [
            {
              "id": 0,
              "@type": "Event",
              "startDate": "2020-06-05T18:00:00+02:00",
              "endDate": "2020-06-05T21:00:00+02:00",
              "status": {
                "type": "Available"
              },
              "bookingAvailability": {
                "type": "Available"
              }
            }
        ]
        """

  @bugfix # https://jira.uitdatabank.be/browse/III-4670
  Scenario: Create event with missing calendar
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/legacy/event-missing-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"

    When I send a GET request to "/events/%{uuid_testevent}"
    And the JSON response at "calendarType" should be "permanent"

  @bugfix # https://jira.uitdatabank.be/browse/III-4670
  Scenario: Create event with missing calendar type but single timeSpan
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/legacy/event-missing-calendar-type-but-single-time-span.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"

    When I send a GET request to "/events/%{uuid_testevent}"
    And the JSON response at "calendarType" should be "single"
    And the JSON response at "subEvent" should be:
    """
    [
      {
        "id": 0,
        "@type": "Event",
        "bookingAvailability": {
          "type": "Available"
        },
        "startDate": "2018-05-05T18:00:00+00:00",
        "endDate": "2018-05-05T21:00:00+00:00",
        "status": {
          "type": "Available"
        }
      }
    ]
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4670
  Scenario: Create event with missing calendar type but multiple timeSpan
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/legacy/event-missing-calendar-type-but-multiple-time-spans.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"

    When I send a GET request to "/events/%{uuid_testevent}"
    And the JSON response at "calendarType" should be "multiple"
    And the JSON response at "subEvent" should be:
    """
    [
      {
        "id": 0,
        "@type": "Event",
        "bookingAvailability": {
          "type": "Available"
        },
        "startDate": "2018-05-05T18:00:00+00:00",
        "endDate": "2018-05-05T21:00:00+00:00",
        "status": {
          "type": "Available"
        }
      },
      {
        "id": 1,
        "@type": "Event",
        "bookingAvailability": {
          "type": "Available"
        },
        "startDate": "2018-05-12T18:00:00+00:00",
        "endDate": "2018-05-12T21:00:00+00:00",
        "status": {
          "type": "Available"
        }
      }
    ]
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4670
  Scenario: Create event with missing calendar type but start date and end date
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/legacy/event-missing-calendar-type-but-start-date-and-end-date.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"

    When I send a GET request to "/events/%{uuid_testevent}"
    And the JSON response at "calendarType" should be "periodic"
    And the JSON response at "startDate" should be "2018-05-05T18:00:00+00:00"
    And the JSON response at "endDate" should be "2018-05-05T21:00:00+00:00"

  Scenario: Try creating an event with non existing location
    Given I set the JSON request payload from "events/event-with-non-existing-place.json"
    When I send a POST request to "/events"
    Then the response status should be "400"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "The location with id \"34fa2edb-9e2f-4d20-a827-4cdf2f3e1e3e\" was not found.",
          "jsonPointer": "/location"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  @bugfix # https://jira.publiq.be/browse/III-4793
  Scenario: Try creating an event with missing body
    When I send a POST request to "/events/"
    Then the response status should be "400"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/body/missing",
      "title": "Body missing",
      "status": 400
    }
    """

  Scenario: Create an event with a non existing organizer
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "placeId"

    Given I set the JSON request payload from "events/event-with-non-existing-organizer.json"
    When I send a POST request to "/events/"
    Then the response status should be "400"
    And the response body should be valid JSON
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "The organizer with id \"bcbf3a32-0c55-4ece-bb91-66f653725d66\" was not found.",
          "jsonPointer": "/organizer"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: I should not be able to create an event with a very long title
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "placeUrl"

    Given I create a random name of 100 characters and keep it as "name"
    Given I set the JSON request payload from "events/event-minimal-permanent-with-variable-name.json"
    When I send a POST request to "/events/"
    Then the response status should be "400"
    And the response body should be valid JSON
    Then the JSON response should be:
    """
    {
        "type": "https://api.publiq.be/probs/body/invalid-data",
        "title": "Invalid body data",
        "status": 400,
        "schemaErrors": [
            {
                "jsonPointer": "/",
                "error": "Given title should not be longer than 90 characters."
            }
        ]
    }
    """