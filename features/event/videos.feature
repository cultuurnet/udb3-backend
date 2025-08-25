Feature: Test event videos property
 # All different videos formats (youtube, youtube shorts, embeds, ...) are tested in the place/videos.features, so we only test one version here.
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"

  Scenario: Add a video to an event and then delete it
    When I set the JSON request payload to:
    """
    {
      "url": "https://vimeo.com/847310238",
      "copyrightHolder": "I am the owner",
      "language": "nl"
    }
    """
    And I send a POST request to "%{eventUrl}/videos"
    Then the response status should be "200"
    And I keep the value of the JSON response at "videoId" as "videoId"
    When I get the place at "%{eventUrl}"
    And the JSON response at "videos" should be:
    """
    [
      {
        "id": "%{videoId}",
        "url": "https://vimeo.com/847310238",
        "embedUrl": "https://player.vimeo.com/video/847310238",
        "language": "nl",
        "copyrightHolder": "I am the owner"
      }
    ]
    """
    When I send a DELETE request to "%{eventUrl}/videos/%{videoId}"
    Then the response status should be "204"
    When I get the place at "%{eventUrl}"
    Then the JSON response should not have "videos"
