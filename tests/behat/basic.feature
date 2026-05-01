@filter @filter_embeddiscussion
Feature: Basic tests for Embeddiscussion

  @javascript
  Scenario: Plugin filter_embeddiscussion appears in the list of installed additional plugins
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    And I follow "Additional plugins"
    Then I should see "Embeddiscussion"
    And I should see "filter_embeddiscussion"
