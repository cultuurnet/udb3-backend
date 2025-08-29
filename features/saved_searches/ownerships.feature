Feature: Test the UDB3 ownerships saved searches API
  
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal organizer and save the "id" as "organizerId"
    And I am authorized as JWT provider user "invoerder_ownerships"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"

  Scenario: Requested ownerships should show not up in saved searches
    Given I am authorized as JWT provider user "invoerder_ownerships"
    When I send a GET request to "/saved-searches/v3"
    Then the response status should be "200"
    And the JSON response should not include:
    """
    {"name":"Aanbod %{name}","query":"organizer.id:%{organizerId}"}"
    """

  Scenario: Approved ownerships should show up in saved searches
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I approve the ownership with ownershipId "%{ownershipId}"
    And I am authorized as JWT provider user "invoerder_ownerships"
    When I send a GET request to "/saved-searches/v3"
    Then the response status should be "200"
    And show me the unparsed response
    And the JSON response at "/" should include:
    """
    {"name":"Aanbod %{name}","query":"organizer.id:%{organizerId}"}"
    """

  Scenario: Rejected ownerships should show not up in saved searches
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I reject the ownership with ownershipId "%{ownershipId}"
    Given I am authorized as JWT provider user "invoerder_ownerships"
    When I send a GET request to "/saved-searches/v3"
    Then the response status should be "200"
    And the JSON response should not include:
    """
    Aanbod %{name}
    """

  Scenario: Deleted ownerships should show up in saved searches
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I delete the ownership with ownershipId "%{ownershipId}"
    Given I am authorized as JWT provider user "invoerder_ownerships"
    When I send a GET request to "/saved-searches/v3"
    Then the response status should be "200"
    And the JSON response should not include:
    """
    Aanbod %{name}
    """
