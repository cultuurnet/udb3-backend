Feature: Test place videos property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Add a video to a place and then delete it
    When I set the JSON request payload to:
    """
    {
      "url": "https://www.youtube.com/watch?v=sddser23",
      "copyrightHolder": "I am the owner",
      "language": "nl"
    }
    """
    And I send a POST request to "%{placeUrl}/videos"
    Then the response status should be "200"
    And I keep the value of the JSON response at "videoId" as "videoId"
    When I get the place at "%{placeUrl}"
    And the JSON response at "videos" should be:
    """
    [
      {
        "id": "%{videoId}",
        "url": "https://www.youtube.com/watch?v=sddser23",
        "embedUrl": "https://www.youtube.com/embed/sddser23",
        "language": "nl",
        "copyrightHolder": "I am the owner"
      }
    ]
    """
    When I send a DELETE request to "%{placeUrl}/videos/%{videoId}"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response should not have "videos"

  Scenario: Add a video to a place and then update it
    Given I set the JSON request payload to:
    """
    {
      "url": "https://www.youtube.com/watch?v=sddser23",
      "copyrightHolder": "I am the owner",
      "language": "nl"
    }
    """
    And I send a POST request to "%{placeUrl}/videos"
    Then the response status should be "200"
    And I keep the value of the JSON response at "videoId" as "videoId"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "videos" should be:
    """
    [
      {
        "id": "%{videoId}",
        "url": "https://www.youtube.com/watch?v=sddser23",
        "embedUrl": "https://www.youtube.com/embed/sddser23",
        "language": "nl",
        "copyrightHolder": "I am the owner"
      }
    ]
    """
    When I set the JSON request payload to:
    """
    [
      {
        "id": "%{videoId}",
        "url": "https://www.youtube.com/watch?v=123"
      }
    ]
    """
    And I send a PATCH request to "%{placeUrl}/videos/"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "videos" should be:
    """
    [
      {
        "id": "%{videoId}",
        "url": "https://www.youtube.com/watch?v=123",
        "embedUrl": "https://www.youtube.com/embed/123",
        "language": "nl",
        "copyrightHolder": "I am the owner"
      }
    ]
    """
    When I set the JSON request payload to:
    """
    [
      {
        "id": "%{videoId}",
        "copyrightHolder": "publiq",
        "language": "fr"
      }
    ]
    """
    And I send a PATCH request to "%{placeUrl}/videos/"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "videos" should be:
    """
    [
      {
        "id": "%{videoId}",
        "url": "https://www.youtube.com/watch?v=123",
        "embedUrl": "https://www.youtube.com/embed/123",
        "language": "fr",
        "copyrightHolder": "publiq"
      }
    ]
    """

  Scenario: Add a youtube short to a place and then delete it
    When I set the JSON request payload to:
    """
    {
      "url": "https://www.youtube.com/shorts/pVMldM3PF-o",
      "copyrightHolder": "I am the owner",
      "language": "nl"
    }
    """
    And I send a POST request to "%{placeUrl}/videos"
    Then the response status should be "200"
    And I keep the value of the JSON response at "videoId" as "videoId"
    When I get the place at "%{placeUrl}"
    And the JSON response at "videos" should be:
    """
    [
      {
        "id": "%{videoId}",
        "url": "https://www.youtube.com/shorts/pVMldM3PF-o",
        "embedUrl": "https://www.youtube.com/shorts/pVMldM3PF-o",
        "language": "nl",
        "copyrightHolder": "I am the owner"
      }
    ]
    """
    When I send a DELETE request to "%{placeUrl}/videos/%{videoId}"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response should not have "videos"

  Scenario: Add a vimeo video to a place and then delete it
    When I set the JSON request payload to:
    """
    {
      "url": "https://vimeo.com/847310238",
      "copyrightHolder": "I am the owner",
      "language": "nl"
    }
    """
    And I send a POST request to "%{placeUrl}/videos"
    Then the response status should be "200"
    And I keep the value of the JSON response at "videoId" as "videoId"
    When I get the place at "%{placeUrl}"
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
    When I send a DELETE request to "%{placeUrl}/videos/%{videoId}"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response should not have "videos"