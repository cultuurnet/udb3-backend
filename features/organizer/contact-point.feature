Feature: Test organizer contactPoint property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal organizer and save the "url" as "organizerUrl"

  Scenario: Update organizer all contact point information via contact-point endpoint
    When I set the JSON request payload to:
    """
    {
      "url": [
        "https://www.publiq.be",
        "https://www.cultuurnet.be"
      ],
      "email": [
        "info@publiq.be",
        "info@cultuurnet.be"
      ],
      "phone": [
        "016 10 20 30",
        "016 11 22 33",
        "016 99 99 99"
      ]
    }
    """
    And I send a PUT request to "%{organizerUrl}/contact-point"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "contactPoint/url" should be:
    """
    ["https://www.publiq.be", "https://www.cultuurnet.be"]
    """
    And the JSON response at "contactPoint/email" should be:
    """
    ["info@publiq.be", "info@cultuurnet.be"]
    """
    And the JSON response at "contactPoint/phone" should be:
    """
    ["016 10 20 30", "016 11 22 33", "016 99 99 99"]
    """

  Scenario: Update organizer partial contact point information via contact-point endpoint
    When I set the JSON request payload to:
    """
    {
      "url": [
        "https://www.publiq.be",
        "https://www.cultuurnet.be"
      ]
    }
    """
    And I send a PUT request to "%{organizerUrl}/contact-point"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "contactPoint/url" should be:
    """
    ["https://www.publiq.be", "https://www.cultuurnet.be"]
    """
    And the JSON response at "contactPoint/email" should be:
    """
    []
    """
    And the JSON response at "contactPoint/phone" should be:
    """
    []
    """

  Scenario: Update organizer contact point in legacy format via contact-point endpoint
    When I set the JSON request payload to:
    """
    [{"type":"phone","value":"0234567890"},{"type":"email","value":"info@test.be"},{"type":"url","value":"http://www.testurl.be"}]
    """
    And I send a PUT request to "%{organizerUrl}/contact-point"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "contactPoint/phone" should be:
    """
    [ "0234567890" ]
    """
    And the JSON response at "contactPoint/email" should be:
    """
    [ "info@test.be" ]
    """
    And the JSON response at "contactPoint/url" should be:
    """
    [ "http://www.testurl.be" ]
    """

  Scenario: Clear organizer contact point information via contact-point endpoint
    When I set the JSON request payload to:
    """
    {
      "url": [],
      "email": [],
      "phone": []
    }
    """
    And I send a PUT request to "%{organizerUrl}/contact-point"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response should not have contactPoint
