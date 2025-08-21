@sapi3
Feature: Test the permissions in UDB3

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I send and accept "application/json"
    And I am authorized as JWT provider v2 user "invoerder_lgm"
    And I create a place from "places/molenhuis.json" and save the "id" as "uuid_place_molenhuis"

    And I am authorized as JWT provider v2 user "invoerder_dfm"
    And I set the JSON request payload from "places/citadel.json"
    And I send a POST request to "/imports/places/"
    And I keep the value of the JSON response at "id" as "uuid_place_citadel"
    And I set the JSON request payload from "events/rondleiding-citadel.json"
    And I send a POST request to "/imports/events/"
    And I keep the value of the JSON response at "id" as "uuid_event_rondleiding"
    And I wait for the event with url "/events/%{uuid_event_rondleiding}" to be indexed

  Scenario: update place WITH permission - owner
    Given I am authorized as JWT provider v2 user "invoerder_lgm"
     When I set the JSON request payload to:
        """
        { "description": "Het Molenhuis is de place to be in Diest - update invoerder_lgm" }
        """
      And I send a PUT request to "/places/%{uuid_place_molenhuis}/description/nl"
     Then the response status should be "204"
      And I send a GET request to "/places/%{uuid_place_molenhuis}"
     Then the response status should be "200"
      And the JSON response at "description/nl" should be:
        """
        "Het Molenhuis is de place to be in Diest - update invoerder_lgm"
        """

   Scenario: update place WITH permission - validator_diest
    Given I am authorized as JWT provider v1 user "validator_diest"
     When I set the JSON request payload to:
        """
        { "description": "Het Molenhuis is de place to be in Molenstede, maar verbleekt in het niets tegenover de Gildenzaal van Diest - update validator_diest" }
        """
      And I send a PUT request to "/places/%{uuid_place_molenhuis}/description/nl"
     Then the response status should be "204"
      And I send a GET request to "/places/%{uuid_place_molenhuis}"
     Then the response status should be "200"
      And the JSON response at "description/nl" should be:
        """
        "Het Molenhuis is de place to be in Molenstede, maar verbleekt in het niets tegenover de Gildenzaal van Diest - update validator_diest"
        """

  Scenario: update place WITH permission - validator_pvb
    Given I am authorized as JWT provider v1 user "validator_pvb"
     When I set the JSON request payload to:
        """
        { "description": "Het Molenhuis is de place to be in Molenstede, maar ga zeker eens kijken in het Provinciedomein de Halve Maan - update validator_pvb" }
        """
      And I send a PUT request to "/places/%{uuid_place_molenhuis}/description/nl"
     Then the response status should be "204"
      And I send a GET request to "/places/%{uuid_place_molenhuis}"
     Then the response status should be "200"
      And the JSON response at "description/nl" should be:
        """
        "Het Molenhuis is de place to be in Molenstede, maar ga zeker eens kijken in het Provinciedomein de Halve Maan - update validator_pvb"
        """

  Scenario: update place WITHOUT permission - invoerder_dfm
    Given I am authorized as JWT provider v2 user "invoerder_dfm"
      And I send a GET request to "/places/%{uuid_place_molenhuis}"
      And I keep the value of the JSON response at "name/nl" as "jsonld_name_nl_place_molenhuis"
     When I set the JSON request payload to:
        """
        { "name": "update invoerder_dfm" }
        """
      And I send a PUT request to "/places/%{uuid_place_molenhuis}/name/nl"
     Then the response status should be "403"
      And I send a GET request to "/places/%{uuid_place_molenhuis}"
     Then the response status should be "200"
      And the JSON response at "name/nl" should be "%{jsonld_name_nl_place_molenhuis}"

  Scenario: update place WITHOUT permission - invoerder_gbm
    Given I am authorized as JWT provider v1 user "invoerder_gbm"
      And I send a GET request to "/places/%{uuid_place_molenhuis}"
      And I keep the value of the JSON response at "name/nl" as "jsonld_name_nl_place_molenhuis"
     When I set the JSON request payload to:
        """
        { "name": "update invoerder_gbm" }
        """
      And I send a PUT request to "/places/%{uuid_place_molenhuis}/name/nl"
     Then the response status should be "403"
      And I send a GET request to "/places/%{uuid_place_molenhuis}"
     Then the response status should be "200"
    And the JSON response at "name/nl" should be "%{jsonld_name_nl_place_molenhuis}"

  Scenario: update place WITHOUT permission - validator_scherpenheuvel
    Given I am authorized as JWT provider v1 user "validator_scherpenheuvel"
      And I send a GET request to "/places/%{uuid_place_molenhuis}"
    And I keep the value of the JSON response at "name/nl" as "jsonld_name_nl_place_molenhuis"
     When I set the JSON request payload to:
        """
        { "name": "update validator_scherpenheuvel" }
        """
      And I send a PUT request to "/places/%{uuid_place_molenhuis}/name/nl"
     Then the response status should be "403"
      And I send a GET request to "/places/%{uuid_place_molenhuis}"
     Then the response status should be "200"
      And the JSON response at "name/nl" should be "%{jsonld_name_nl_place_molenhuis}"


  Scenario: update event WITH permission - owner
    Given I am authorized as JWT provider v2 user "invoerder_dfm"
    When I set the JSON request payload to:
      """
      { "description": "Rondleiding in de citadel te Diest onder deskundige leiding van Davidsfonds Molenstede - update invoerder_dfm" }
      """
      And I send a PUT request to "/events/%{uuid_event_rondleiding}/description/nl"
    Then the response status should be "204"
      And I send a GET request to "/events/%{uuid_event_rondleiding}"
    Then the response status should be "200"
      And the JSON response at "description/nl" should be:
      """
      "Rondleiding in de citadel te Diest onder deskundige leiding van Davidsfonds Molenstede - update invoerder_dfm"
      """

  Scenario: update event WITH permission - validator_diest
    Given I am authorized as JWT provider v1 user "validator_diest"
    When I set the JSON request payload to:
    """
    { "description": "Rondleiding in de citadel te Diest onder deskundige leiding van Davidsfonds Molenstede - update validator_diest" }
    """
      And I send a PUT request to "/events/%{uuid_event_rondleiding}/description/nl"
    Then the response status should be "204"
      And I send a GET request to "/events/%{uuid_event_rondleiding}"
    Then the response status should be "200"
      And the JSON response at "description/nl" should be:
      """
      "Rondleiding in de citadel te Diest onder deskundige leiding van Davidsfonds Molenstede - update validator_diest"
      """

  Scenario: update event WITH permission - validator_pvb
    Given I am authorized as JWT provider v1 user "validator_pvb"
    When I set the JSON request payload to:
    """
    { "description": "Rondleiding in de citadel te Diest - update validator_pvb" }
    """
      And I send a PUT request to "/events/%{uuid_event_rondleiding}/description/nl"
    Then the response status should be "204"
      And I send a GET request to "/events/%{uuid_event_rondleiding}"
    Then the response status should be "200"
      And the JSON response at "description/nl" should be:
        """
        "Rondleiding in de citadel te Diest - update validator_pvb"
        """

  Scenario: update event WITHOUT permission - invoerder_lgm
    Given I am authorized as JWT provider v2 user "invoerder_lgm"
      And I send a GET request to "/events/%{uuid_event_rondleiding}"
      And I keep the value of the JSON response at "name/nl" as "jsonld_name_nl_event_rondleiding"
    When I set the JSON request payload to:
      """
      { "name": "Rondleiding in de citadel te Diest - update invoerder_lgm" }
      """
      And I send a PUT request to "/events/%{uuid_event_rondleiding}/name/nl"
    Then the response status should be "403"
      And I send a GET request to "/events/%{uuid_event_rondleiding}"
    Then the response status should be "200"
    And the JSON response at "name/nl" should be "%{jsonld_name_nl_event_rondleiding}"

  Scenario: update event WITHOUT permission - invoerder_gbm
    Given I am authorized as JWT provider v1 user "invoerder_gbm"
      And I send a GET request to "/events/%{uuid_event_rondleiding}"
      And I keep the value of the JSON response at "name/nl" as "jsonld_name_nl_event_rondleiding"
    When I set the JSON request payload to:
      """
      { "name": "Rondleiding in de citadel te Diest - update invoerder_gbm" }
      """
      And I send a PUT request to "/events/%{uuid_event_rondleiding}/name/nl"
    Then the response status should be "403"
      And I send a GET request to "/events/%{uuid_event_rondleiding}"
    Then the response status should be "200"
    And the JSON response at "name/nl" should be "%{jsonld_name_nl_event_rondleiding}"

  Scenario: update event WITHOUT permission - validator_scherpenheuvel
    Given I am authorized as JWT provider v1 user "validator_scherpenheuvel"
      And I send a GET request to "/events/%{uuid_event_rondleiding}"
      And I keep the value of the JSON response at "name/nl" as "jsonld_name_nl_event_rondleiding"
    When I set the JSON request payload to:
      """
      { "name": "Rondleiding in de citadel te Diest - update validator_scherpenheuvel" }
      """
      And I send a PUT request to "/events/%{uuid_event_rondleiding}/name/nl"
    Then the response status should be "403"
      And I send a GET request to "/events/%{uuid_event_rondleiding}"
    Then the response status should be "200"
    And the JSON response at "name/nl" should be "%{jsonld_name_nl_event_rondleiding}"

  Scenario: add private label to event WITH permission - validator_diest
    Given I am authorized as JWT provider v1 user "validator_diest"
    When I send a PUT request to "/events/%{uuid_event_rondleiding}/labels/private-diest"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_event_rondleiding}"
    Then the JSON response at "labels" should be:
    """
    ["label1","label2","private-diest"]
    """

  Scenario: add private label to place WITH permission - validator_diest
    Given I am authorized as JWT provider v1 user "validator_diest"
    When I send a PUT request to "/places/%{uuid_place_citadel}/labels/private-diest"
    Then the response status should be "204"
   When I send a GET request to "/places/%{uuid_place_citadel}"
   Then the JSON response at "labels" should be:
   """
   ["private-diest"]
   """

  Scenario: add private label to event WITHOUT permission - validator_scherpenheuvel
    Given I am authorized as JWT provider v1 user "validator_scherpenheuvel"
    When I send a PUT request to "/events/%{uuid_event_rondleiding}/labels/private-diest"
    Then the response status should be "403"

  Scenario: add private label to place WITHOUT permission - validator_scherpenheuvel
    Given I am authorized as JWT provider v1 user "validator_scherpenheuvel"
    When I send a PUT request to "/places/%{uuid_place_molenhuis}/labels/private-diest"
    Then the response status should be "403"
