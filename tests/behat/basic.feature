@filter @filter_embeddiscussion
Feature: Embedded discussion filter
  In order to host conversations alongside content
  As a teacher
  I should be able to embed discussion threads inside labels and pages

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | student1 | Student   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the "embeddiscussion" filter is "on"
    And the following "activities" exist:
      | activity | course | name      | intro                                  | idnumber |
      | label    | C1     | Discuss A | {embeddeddiscussion:Course 1 Demo}     | l1       |

  @javascript
  Scenario: Plugin appears in the additional plugins list
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    And I follow "Additional plugins"
    Then I should see "Embedded discussion"
    And I should see "filter_embeddiscussion"

  @javascript
  Scenario: Filter renders the placeholder and the JS hydrates the discussion thread
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    Then "[data-region='filter-embeddiscussion'][data-threadid]" "css_element" should exist
    And "[data-region='embeddisc-root'][data-anonymous='0'][data-locked='0']" "css_element" should exist
