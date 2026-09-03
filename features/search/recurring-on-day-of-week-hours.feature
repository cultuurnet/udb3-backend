@sapi3
Feature: Test the recurringOnDayOfWeek search filter combined with hours

  # A parent looking for a regular Wednesday or Saturday afternoon activity needs the day of week and
  # the hours to hold on the same recurring slot. Combining recurringOnDayOfWeek with the existing
  # localTimeFrom and localTimeTo cannot express that, because localTimeRange is a union over every
  # day of week: a museum open Wednesday morning and Saturday afternoon would answer a Wednesday
  # afternoon search. The hours are therefore indexed per recurring day of week and queried with
  # recurringOnLocalTimeFrom and recurringOnLocalTimeTo.
  #
  # Only multiple, periodic and permanent calendars recur. A single calendar is checked once, to pin
  # down that a one-off event gets no recurring hours at all.
  #
  # Opening hours crossing midnight are absent on purpose. Entry API rejects an opening hour whose
  # closes is before its opens, so a permanent offer open 20:00 to 02:00 cannot be created to test
  # with. Sub-events of a multiple calendar do cross midnight and are covered below.

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  @testIsolation
  Scenario: Permanent event is matched on the day of week and the hours of its opening hours
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "10:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday"]
        },
        {
          "opens": "13:00",
          "closes": "18:00",
          "dayOfWeek": ["saturday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Wednesday is open from 10:00, so the afternoon falls inside the opening hours.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1300      |
      | recurringOnLocalTimeTo   | 1600      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Saturday is open from 13:00, so the same afternoon matches there too.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | saturday |
      | recurringOnLocalTimeFrom | 1300     |
      | recurringOnLocalTimeTo   | 1600     |
      | disableDefaultFilters    | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Saturday only opens at 13:00, so the morning does not match. This is the case the old
    # combination of recurringOnDayOfWeek and localTimeFrom got wrong: the union over all days of
    # week contains 10:00 to 17:00 from the Wednesday entry.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | saturday |
      | recurringOnLocalTimeFrom | 1000     |
      | recurringOnLocalTimeTo   | 1200     |
      | disableDefaultFilters    | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Wednesday closes at 17:00, so the evening does not match either.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1800      |
      | recurringOnLocalTimeTo   | 1900      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # The comma-separated syntax OR-combines the days of week, so the Wednesday morning is enough
    # to match even though Saturday morning is closed.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday,saturday |
      | recurringOnLocalTimeFrom | 1000               |
      | recurringOnLocalTimeTo   | 1200               |
      | disableDefaultFilters    | true               |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The hours are exposed per day of week in the q parameter, where AND logic can be expressed
    # explicitly instead of the OR-combining of the url parameters. Both days are open in the
    # afternoon, so requiring both matches.
    When I send a GET request to "/events" with parameters:
      | q                     | recurringOnLocalTimeRange.wednesday:[1400 TO 1600] AND recurringOnLocalTimeRange.saturday:[1400 TO 1600] |
      | disableDefaultFilters | true                                                                                                     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Saturday is closed in the morning, so requiring both days excludes the event.
    When I send a GET request to "/events" with parameters:
      | q                     | recurringOnLocalTimeRange.wednesday:[1000 TO 1200] AND recurringOnLocalTimeRange.saturday:[1000 TO 1200] |
      | disableDefaultFilters | true                                                                                                     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: One of the hours on its own leaves the other side of the range open
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "10:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday"]
        },
        {
          "opens": "13:00",
          "closes": "18:00",
          "dayOfWeek": ["saturday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Without an end, the search runs to the end of the day. Wednesday is open until 17:00, so it
    # still has hours from 16:00 on.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1600      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The open end does not soften the start. Wednesday closes at 17:00, so a search from 17:00 on
    # finds nothing, the same half open bound as with an end.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1700      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Without a start, the search runs from midnight. Wednesday opens at 10:00, so it has hours
    # before 11:00.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek   | wednesday |
      | recurringOnLocalTimeTo | 1100      |
      | disableDefaultFilters  | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # And nothing before 10:00.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek   | wednesday |
      | recurringOnLocalTimeTo | 1000      |
      | disableDefaultFilters  | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # An open side stays per day of week. Saturday only opens at 13:00, so it has no morning.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek   | saturday |
      | recurringOnLocalTimeTo | 1200     |
      | disableDefaultFilters  | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # The days of week stay OR-combined, so the Wednesday morning is enough.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek   | wednesday,saturday |
      | recurringOnLocalTimeTo | 1200               |
      | disableDefaultFilters  | true               |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Permanent event with two opening hour slots on the same day of week is not matched in the gap between them
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "10:00",
          "closes": "12:00",
          "dayOfWeek": ["wednesday"]
        },
        {
          "opens": "14:00",
          "closes": "18:00",
          "dayOfWeek": ["wednesday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Both slots are recurring, so both are searchable on their own.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1030      |
      | recurringOnLocalTimeTo   | 1130      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1500      |
      | recurringOnLocalTimeTo   | 1600      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The event is closed between 12:00 and 14:00. The two slots have to stay separate ranges,
    # because a single 10:00 to 18:00 range covering both would wrongly match this lunch break.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1230      |
      | recurringOnLocalTimeTo   | 1330      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # A window overlapping both slots matches, because the requested hours only have to overlap
    # one recurring slot, not be fully covered by it.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1100      |
      | recurringOnLocalTimeTo   | 1500      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: The recurring hours are half open, so an offer closing at 12:00 does not match a search from 12:00
    # Every other range field in the search indexes inclusive bounds. These hours are the exception:
    # an activity ending at 12:00 no longer occupies 12:00, and an inclusive upper bound would let it
    # answer a search starting at 12:00 on that one minute.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "10:00",
          "closes": "12:00",
          "dayOfWeek": ["wednesday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # The last hour before closing is still open.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1100      |
      | recurringOnLocalTimeTo   | 1200      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # From closing time on the event is over.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1200      |
      | recurringOnLocalTimeTo   | 1300      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # A window starting before the doors open and ending inside the slot overlaps it.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 0900      |
      | recurringOnLocalTimeTo   | 1100      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # A window ending before the doors open does not.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 0800      |
      | recurringOnLocalTimeTo   | 0930      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Permanent place is matched on the day of week and the hours of its opening hours
    When I create a minimal place with overrides and save the "url" as "placeUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "10:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday"]
        },
        {
          "opens": "13:00",
          "closes": "18:00",
          "dayOfWeek": ["saturday"]
        }
      ]
    }
    """
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1300      |
      | recurringOnLocalTimeTo   | 1600      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Saturday only opens at 13:00, so the morning does not match.
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek     | saturday |
      | recurringOnLocalTimeFrom | 1000     |
      | recurringOnLocalTimeTo   | 1200     |
      | disableDefaultFilters    | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q                     | recurringOnLocalTimeRange.saturday:[1400 TO 1600] |
      | disableDefaultFilters | true                                              |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: The recurring hours keep the exact minutes of the opening hours
    # A place open from 08:30 to 09:17 is indexed as 08:30 to 09:17. An earlier version tallied per
    # quarter of an hour, which rounded that outward to 08:30 to 09:30 and made the place answer a
    # 09:20 search it is closed for.
    When I create a minimal place with overrides and save the "url" as "placeUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "08:30",
          "closes": "09:17",
          "dayOfWeek": ["wednesday"]
        }
      ]
    }
    """
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 0900      |
      | recurringOnLocalTimeTo   | 0910      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The quarter of an hour after 09:17, which rounding would have swallowed.
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 0920      |
      | recurringOnLocalTimeTo   | 0930      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # And the quarter of an hour before 08:30.
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 0815      |
      | recurringOnLocalTimeTo   | 0829      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Permanent place without opening hours has no hours to be matched on
    Given I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Without the filter the place is returned, so it is indexed and searchable.
    When I send a GET request to "/places" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek     | monday |
      | recurringOnLocalTimeFrom | 1000   |
      | recurringOnLocalTimeTo   | 1200   |
      | disableDefaultFilters    | true   |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Childcare hours widen the recurring hours
    # A child is present for the childcare hours too, so the recurring hours run from the start of
    # the childcare until the end of it instead of over the opening hours alone.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "14:00",
          "closes": "17:00",
          "childcare": {"start": "12:00", "end": "18:00"},
          "dayOfWeek": ["wednesday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # The hours the event itself runs.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1500      |
      | recurringOnLocalTimeTo   | 1600      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The childcare before it opens.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1230      |
      | recurringOnLocalTimeTo   | 1330      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # And the childcare after it closes.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1730      |
      | recurringOnLocalTimeTo   | 1745      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Nothing before the childcare starts.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1100      |
      | recurringOnLocalTimeTo   | 1130      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # The widened hours combine with hasChildcare, which is what a parent looking for a Wednesday
    # afternoon with childcare actually sends.
    When I send a GET request to "/events" with parameters:
      | hasChildcare             | true      |
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1230      |
      | recurringOnLocalTimeTo   | 1330      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Childcare with only a start widens only the beginning of the recurring hours
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "14:00",
          "closes": "17:00",
          "childcare": {"start": "12:00"},
          "dayOfWeek": ["wednesday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # The childcare before the doors open is covered.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1230      |
      | recurringOnLocalTimeTo   | 1330      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # After closing there is no childcare to extend the hours with.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1730      |
      | recurringOnLocalTimeTo   | 1745      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Periodic event only indexes hours for a day of week that reaches the minimum number of occurrences
    # The period from 1 to 26 August 2026 contains four Wednesdays (5, 12, 19 and 26), which is
    # exactly the required minimum, and three Thursdays (6, 13 and 20), which is one short.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-08-01T00:00:00+02:00",
      "endDate": "2026-08-26T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "14:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday", "thursday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Without the filter the event is returned, so it is indexed and searchable.
    When I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Wednesday reaches the threshold, so its hours are indexed.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1500      |
      | recurringOnLocalTimeTo   | 1600      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Thursday stays one occurrence below the threshold, so it has no indexed hours at all.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | thursday |
      | recurringOnLocalTimeFrom | 1500     |
      | recurringOnLocalTimeTo   | 1600     |
      | disableDefaultFilters    | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # The event never opens in the morning.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 0900      |
      | recurringOnLocalTimeTo   | 1000      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Periodic place only indexes hours for a day of week that reaches the minimum number of occurrences
    # The period from 1 to 26 August 2026 contains four Wednesdays (5, 12, 19 and 26), which is
    # exactly the required minimum, and three Thursdays (6, 13 and 20), which is one short.
    When I create a minimal place with overrides and save the "url" as "placeUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-08-01T00:00:00+02:00",
      "endDate": "2026-08-26T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday", "thursday"]
        }
      ]
    }
    """
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Wednesday reaches the threshold, so its hours are indexed.
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1000      |
      | recurringOnLocalTimeTo   | 1200      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Thursday stays one occurrence below the threshold, so it has no indexed hours at all.
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek     | thursday |
      | recurringOnLocalTimeFrom | 1000     |
      | recurringOnLocalTimeTo   | 1200     |
      | disableDefaultFilters    | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # The place is never open in the evening.
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1800      |
      | recurringOnLocalTimeTo   | 1900      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Closed days are subtracted from the occurrences of an hours slot
    # The period from 1 August to 2 September 2026 contains five Wednesdays and five Saturdays.
    # The closed period from 5 to 12 August removes the Wednesdays of 5 and 12 August, leaving three,
    # which is below the required minimum of four. It also removes the Saturday of 8 August, leaving
    # four, which still reaches the minimum.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-08-01T00:00:00+02:00",
      "endDate": "2026-09-02T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday", "saturday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-08-05",
          "endDate": "2026-08-12"
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Without the filter the event is returned, so it is indexed and searchable.
    When I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Saturday keeps four occurrences, so its hours stay indexed.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | saturday |
      | recurringOnLocalTimeFrom | 1000     |
      | recurringOnLocalTimeTo   | 1200     |
      | disableDefaultFilters    | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Wednesday drops to three occurrences, so its hours are no longer indexed.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1000      |
      | recurringOnLocalTimeTo   | 1200      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Adjusted days replace the hours that are indexed
    # The period from 1 August to 9 September 2026 contains six Wednesdays (5, 12, 19 and 26 August,
    # 2 and 9 September), so Wednesday is a recurring day of week. The adjusted range covers the four
    # Wednesdays of August, which therefore open from 09:00 to 12:00. Only 2 and 9 September keep the
    # regular 14:00 to 18:00, which is two occurrences and below the minimum of four.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-08-01T00:00:00+02:00",
      "endDate": "2026-09-09T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "14:00",
          "closes": "18:00",
          "dayOfWeek": ["wednesday"]
        }
      ],
      "openingHoursAdjustedDays": [
        {
          "startDate": "2026-08-05",
          "endDate": "2026-08-26",
          "openingHours": [
            {
              "opens": "09:00",
              "closes": "12:00",
              "dayOfWeek": ["wednesday"]
            }
          ]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # All six Wednesdays are open, so Wednesday itself is still a recurring day of week.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The adjusted hours reach the minimum, so they are the indexed hours.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1000      |
      | recurringOnLocalTimeTo   | 1100      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The regular hours only survive on two Wednesdays, which is not a dependable fixture.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1500      |
      | recurringOnLocalTimeTo   | 1600      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: The recurring hours stay clock hours across the daylight saving switch
    # The period from 1 October to 5 November 2026 contains five Wednesdays (7, 14, 21 and 28 October
    # and 4 November) and the switch back to winter time on 25 October. The hours are read off the
    # local clock, so all five Wednesdays are 14:00 to 17:00 and the indexed range does not shift by
    # an hour halfway through the period.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-10-01T00:00:00+02:00",
      "endDate": "2026-11-05T23:59:59+01:00",
      "openingHours": [
        {
          "opens": "14:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1500      |
      | recurringOnLocalTimeTo   | 1600      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # An hour before opening, which is where a shift would show up.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1300      |
      | recurringOnLocalTimeTo   | 1330      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # And an hour after closing.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1700      |
      | recurringOnLocalTimeTo   | 1730      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Multiple calendar event is matched on the hours of the sub-events of that day of week
    # Four Wednesday mornings and four Saturday afternoons.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "startDate": "2026-08-01T14:00:00+02:00",
      "endDate": "2026-08-26T12:00:00+02:00",
      "subEvent": [
        {"startDate": "2026-08-05T10:00:00+02:00", "endDate": "2026-08-05T12:00:00+02:00"},
        {"startDate": "2026-08-12T10:00:00+02:00", "endDate": "2026-08-12T12:00:00+02:00"},
        {"startDate": "2026-08-19T10:00:00+02:00", "endDate": "2026-08-19T12:00:00+02:00"},
        {"startDate": "2026-08-26T10:00:00+02:00", "endDate": "2026-08-26T12:00:00+02:00"},
        {"startDate": "2026-08-01T14:00:00+02:00", "endDate": "2026-08-01T18:00:00+02:00"},
        {"startDate": "2026-08-08T14:00:00+02:00", "endDate": "2026-08-08T18:00:00+02:00"},
        {"startDate": "2026-08-15T14:00:00+02:00", "endDate": "2026-08-15T18:00:00+02:00"},
        {"startDate": "2026-08-22T14:00:00+02:00", "endDate": "2026-08-22T18:00:00+02:00"}
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Without the filter the event is returned, so it is indexed and searchable.
    When I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1030      |
      | recurringOnLocalTimeTo   | 1130      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | saturday |
      | recurringOnLocalTimeFrom | 1500     |
      | recurringOnLocalTimeTo   | 1600     |
      | disableDefaultFilters    | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The Saturday sub-events only run in the afternoon, so the Wednesday morning hours must not
    # leak into the Saturday match.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | saturday |
      | recurringOnLocalTimeFrom | 1030     |
      | recurringOnLocalTimeTo   | 1130     |
      | disableDefaultFilters    | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # And the other way around.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1500      |
      | recurringOnLocalTimeTo   | 1600      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Multiple calendar event with two sub-events on the same day of week is not matched in the gap between them
    # Four Wednesdays, each with a morning and an afternoon sub-event.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "startDate": "2026-08-05T10:00:00+02:00",
      "endDate": "2026-08-26T18:00:00+02:00",
      "subEvent": [
        {"startDate": "2026-08-05T10:00:00+02:00", "endDate": "2026-08-05T12:00:00+02:00"},
        {"startDate": "2026-08-05T14:00:00+02:00", "endDate": "2026-08-05T18:00:00+02:00"},
        {"startDate": "2026-08-12T10:00:00+02:00", "endDate": "2026-08-12T12:00:00+02:00"},
        {"startDate": "2026-08-12T14:00:00+02:00", "endDate": "2026-08-12T18:00:00+02:00"},
        {"startDate": "2026-08-19T10:00:00+02:00", "endDate": "2026-08-19T12:00:00+02:00"},
        {"startDate": "2026-08-19T14:00:00+02:00", "endDate": "2026-08-19T18:00:00+02:00"},
        {"startDate": "2026-08-26T10:00:00+02:00", "endDate": "2026-08-26T12:00:00+02:00"},
        {"startDate": "2026-08-26T14:00:00+02:00", "endDate": "2026-08-26T18:00:00+02:00"}
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Both slots recur on four Wednesdays, so both are searchable.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1030      |
      | recurringOnLocalTimeTo   | 1130      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1500      |
      | recurringOnLocalTimeTo   | 1600      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Nothing takes place between 12:00 and 14:00, so the two slots have to stay separate ranges.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1230      |
      | recurringOnLocalTimeTo   | 1330      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Multiple calendar event with disjoint hours has a recurring day of week but no recurring hours
    # Six Wednesdays, three in the morning and three in the evening. Wednesday occurs six times and
    # is a recurring day of week, but neither slot reaches the minimum of four on its own and they do
    # not overlap, so there is nothing dependable to index as hours.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "startDate": "2026-08-05T10:00:00+02:00",
      "endDate": "2026-09-09T20:00:00+02:00",
      "subEvent": [
        {"startDate": "2026-08-05T10:00:00+02:00", "endDate": "2026-08-05T12:00:00+02:00"},
        {"startDate": "2026-08-12T10:00:00+02:00", "endDate": "2026-08-12T12:00:00+02:00"},
        {"startDate": "2026-08-19T10:00:00+02:00", "endDate": "2026-08-19T12:00:00+02:00"},
        {"startDate": "2026-08-26T18:00:00+02:00", "endDate": "2026-08-26T20:00:00+02:00"},
        {"startDate": "2026-09-02T18:00:00+02:00", "endDate": "2026-09-02T20:00:00+02:00"},
        {"startDate": "2026-09-09T18:00:00+02:00", "endDate": "2026-09-09T20:00:00+02:00"}
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # The day of week filter on its own keeps working, so this stays a Wednesday event.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Neither the morning nor the evening is a recurring fixture.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1030      |
      | recurringOnLocalTimeTo   | 1130      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1830      |
      | recurringOnLocalTimeTo   | 1930      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Multiple calendar event whose hours shift keeps only the hours that reach the minimum
    # Six Wednesdays, three from 10:00 to 12:00 and three from 10:00 to 13:00. Only the hours up to
    # 12:00 happen on all six, so the last hour is dropped. The slots are deliberately not merged
    # across dates: merging them would give one 10:00 to 13:00 range occurring six times and let the
    # event answer a 12:15 search it only runs on half the time.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "startDate": "2026-08-05T10:00:00+02:00",
      "endDate": "2026-09-09T13:00:00+02:00",
      "subEvent": [
        {"startDate": "2026-08-05T10:00:00+02:00", "endDate": "2026-08-05T12:00:00+02:00"},
        {"startDate": "2026-08-12T10:00:00+02:00", "endDate": "2026-08-12T12:00:00+02:00"},
        {"startDate": "2026-08-19T10:00:00+02:00", "endDate": "2026-08-19T12:00:00+02:00"},
        {"startDate": "2026-08-26T10:00:00+02:00", "endDate": "2026-08-26T13:00:00+02:00"},
        {"startDate": "2026-09-02T10:00:00+02:00", "endDate": "2026-09-02T13:00:00+02:00"},
        {"startDate": "2026-09-09T10:00:00+02:00", "endDate": "2026-09-09T13:00:00+02:00"}
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # The hours all six Wednesdays share.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1030      |
      | recurringOnLocalTimeTo   | 1130      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The last hour only happens on three of them, which is below the minimum of four.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1215      |
      | recurringOnLocalTimeTo   | 1245      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # And nothing runs after 13:00 on any of them.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1400      |
      | recurringOnLocalTimeTo   | 1500      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: An hour only one occurrence starts earlier is not a recurring hour
    # Four Wednesdays, three from 11:00 to 12:00 and one from 10:00 to 12:00. The early hour happens
    # once, so the indexed hours start at 11:00. A search for 09:00 to 11:00 then finds nothing,
    # which is the case the design is built around: a swimming lesson of 10:00 to 11:00 must not
    # answer a search from 11:00 onwards, and both bounds have to be half open for that to hold.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "startDate": "2026-08-05T10:00:00+02:00",
      "endDate": "2026-08-26T12:00:00+02:00",
      "subEvent": [
        {"startDate": "2026-08-05T10:00:00+02:00", "endDate": "2026-08-05T12:00:00+02:00"},
        {"startDate": "2026-08-12T11:00:00+02:00", "endDate": "2026-08-12T12:00:00+02:00"},
        {"startDate": "2026-08-19T11:00:00+02:00", "endDate": "2026-08-19T12:00:00+02:00"},
        {"startDate": "2026-08-26T11:00:00+02:00", "endDate": "2026-08-26T12:00:00+02:00"}
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # The hour all four share.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1100      |
      | recurringOnLocalTimeTo   | 1200      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # A window ending exactly where the recurring hours start shares no minute with them.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 0900      |
      | recurringOnLocalTimeTo   | 1100      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # A window starting where they start does match, the lower bound stays inclusive.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1100      |
      | recurringOnLocalTimeTo   | 1600      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Multiple calendar sub-events crossing midnight are split over both days of week
    # Five Saturday nights running into Sunday. A sub-event spanning two days already counts both
    # days of week, and its hours split at midnight the same way.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "startDate": "2026-08-01T20:00:00+02:00",
      "endDate": "2026-08-30T02:00:00+02:00",
      "subEvent": [
        {"startDate": "2026-08-01T20:00:00+02:00", "endDate": "2026-08-02T02:00:00+02:00"},
        {"startDate": "2026-08-08T20:00:00+02:00", "endDate": "2026-08-09T02:00:00+02:00"},
        {"startDate": "2026-08-15T20:00:00+02:00", "endDate": "2026-08-16T02:00:00+02:00"},
        {"startDate": "2026-08-22T20:00:00+02:00", "endDate": "2026-08-23T02:00:00+02:00"},
        {"startDate": "2026-08-29T20:00:00+02:00", "endDate": "2026-08-30T02:00:00+02:00"}
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # The part before midnight stays on Saturday.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | saturday |
      | recurringOnLocalTimeFrom | 2100     |
      | recurringOnLocalTimeTo   | 2200     |
      | disableDefaultFilters    | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The part after midnight moves to Sunday.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | sunday |
      | recurringOnLocalTimeFrom | 0100   |
      | recurringOnLocalTimeTo   | 0130   |
      | disableDefaultFilters    | true   |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Saturday daytime is not covered.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | saturday |
      | recurringOnLocalTimeFrom | 1000     |
      | recurringOnLocalTimeTo   | 1200     |
      | disableDefaultFilters    | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Neither is Sunday daytime.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | sunday |
      | recurringOnLocalTimeFrom | 1000   |
      | recurringOnLocalTimeTo   | 1200   |
      | disableDefaultFilters    | true   |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: A multi-day sub-event makes the day in between recur around the clock
    # Four festival weekends, each running from Friday 20:00 to Sunday 22:00. Saturday is covered
    # from midnight to midnight on all four, so every hour of Saturday is a recurring hour.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "startDate": "2026-08-07T20:00:00+02:00",
      "endDate": "2026-08-30T22:00:00+02:00",
      "subEvent": [
        {"startDate": "2026-08-07T20:00:00+02:00", "endDate": "2026-08-09T22:00:00+02:00"},
        {"startDate": "2026-08-14T20:00:00+02:00", "endDate": "2026-08-16T22:00:00+02:00"},
        {"startDate": "2026-08-21T20:00:00+02:00", "endDate": "2026-08-23T22:00:00+02:00"},
        {"startDate": "2026-08-28T20:00:00+02:00", "endDate": "2026-08-30T22:00:00+02:00"}
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # The small hours of Saturday are as much a recurring hour as its afternoon.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | saturday |
      | recurringOnLocalTimeFrom | 0300     |
      | recurringOnLocalTimeTo   | 0400     |
      | disableDefaultFilters    | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | saturday |
      | recurringOnLocalTimeFrom | 1400     |
      | recurringOnLocalTimeTo   | 1500     |
      | disableDefaultFilters    | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Sunday morning is covered too, it continues the Saturday night.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | sunday |
      | recurringOnLocalTimeFrom | 0300   |
      | recurringOnLocalTimeTo   | 0400   |
      | disableDefaultFilters    | true   |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Friday only starts at 20:00.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | friday |
      | recurringOnLocalTimeFrom | 1400   |
      | recurringOnLocalTimeTo   | 1500   |
      | disableDefaultFilters    | true   |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # And Sunday is over at 22:00.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | sunday |
      | recurringOnLocalTimeFrom | 2300   |
      | recurringOnLocalTimeTo   | 2330   |
      | disableDefaultFilters    | true   |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Single calendar event has no recurring hours
    # A single calendar occurs once, so it can never reach the minimum of four occurrences. The hours
    # it does run are searchable with localTimeFrom and localTimeTo, which is the filter for a one
    # off event.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "single",
      "startDate": "2026-08-05T10:00:00+02:00",
      "endDate": "2026-08-05T12:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-08-05T10:00:00+02:00",
          "endDate": "2026-08-05T12:00:00+02:00"
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Without the filter the event is returned, so it is indexed and searchable.
    When I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # 5 August 2026 is a Wednesday, but one Wednesday morning is not a recurring Wednesday morning.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1030      |
      | recurringOnLocalTimeTo   | 1130      |
      | disableDefaultFilters    | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # The plain local time filter is the one that matches it.
    When I send a GET request to "/events" with parameters:
      | localTimeFrom         | 1030 |
      | localTimeTo           | 1130 |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Hours without a day of week are rejected
    # The hours are only meaningful per day of week, so they cannot be used on their own.
    When I am using the Search API v3 base URL
    And I send a GET request to "/events" with parameters:
      | recurringOnLocalTimeFrom | 1300 |
      | recurringOnLocalTimeTo   | 1600 |
      | disableDefaultFilters    | true |
    Then the response status should be "404"
    And the JSON response at "detail" should include "recurringOnDayOfWeek"
    # One of the hours on its own has no day of week to range over either.
    When I send a GET request to "/events" with parameters:
      | recurringOnLocalTimeFrom | 1300 |
      | disableDefaultFilters    | true |
    Then the response status should be "404"
    And the JSON response at "detail" should include "recurringOnDayOfWeek"

  @testIsolation
  Scenario: An inverted hours range is rejected
    When I am using the Search API v3 base URL
    And I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | wednesday |
      | recurringOnLocalTimeFrom | 1600      |
      | recurringOnLocalTimeTo   | 1300      |
      | disableDefaultFilters    | true      |
    Then the response status should be "404"
    And the JSON response at "detail" should include "recurringOnLocalTime"

  @testIsolation
  Scenario: An invalid day of week is rejected when hours are given
    When I am using the Search API v3 base URL
    And I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek     | someday |
      | recurringOnLocalTimeFrom | 1300    |
      | recurringOnLocalTimeTo   | 1600    |
      | disableDefaultFilters    | true    |
    Then the response status should be "404"
    And the JSON response at "detail" should include "someday"
