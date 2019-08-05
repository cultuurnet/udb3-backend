Feature: Import of keywords from UDB2 to UDB3.

  @issue-III-1667
  Scenario: import UDB2 keywords with different casing.
    Given an actor in UDB2
    And the actor has the following keywords:
    """
    <keywords>
      <keyword>2dotstwice</keyword>
      <keyword>2DOTStwice</keyword>
    </keywords>
    """
    When this actor is imported in UDB3
    Then only the label "2dotstwice" gets imported in UDB3
    And the json projection contains label property:
    """
    "labels": ["2dotstwice"]
    """

  @issue-III-1667
  Scenario: import UDB2 keywords with different casing and visibility.
    Given an actor in UDB2
    And the actor has the following keywords:
    """
    <keywords>
      <keyword visible="false">2dotstwice</keyword>
      <keyword>2DOTStwice</keyword>
    </keywords>
    """
    When this actor is imported in UDB3
    Then only the label "2dotstwice" gets imported in UDB3
    And the json projection contains labels:
    """
    "hiddenLabels": ["2dotstwice"]
    """
