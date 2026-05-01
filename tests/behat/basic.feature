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
  Scenario: Filter renders the skeleton placeholder for the token
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    Then "[data-region='filter-embeddiscussion'][data-thread-name='Course 1 Demo']" "css_element" should exist

  @javascript
  Scenario: Teacher sees the discussion settings panel
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    Then I should see "Discussion settings"
    And "[data-region='filter-embeddiscussion']" "css_element" should exist
