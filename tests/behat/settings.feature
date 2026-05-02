@filter @filter_embeddiscussion
Feature: Anonymous and locked thread settings
  In order to control the tone of an embedded discussion
  As a teacher
  I should be able to enable anonymous mode and lock a thread

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
      | activity | course | name      | intro                                | idnumber |
      | label    | C1     | Discuss A | {embeddeddiscussion:Settings demo}   | l1       |
    And the following "filter_embeddiscussion > threads" exist:
      | name          | course | activity |
      | Settings demo | C1     | l1       |
    And the following "filter_embeddiscussion > posts" exist:
      | thread        | user     | content                |
      | Settings demo | student1 | Hello from a student   |

    And I change the window size to "large"

  @javascript
  Scenario: Teacher enables anonymous mode and student sees the anonymity notice
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And the embedded discussion is loaded
    When I click on "Discussion settings" "button"
    And I click on "Anonymous posts" "checkbox"
    And I wait until the page is ready
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And the embedded discussion is loaded
    Then I should see "Your posts will be anonymous to other students"

  @javascript
  Scenario: Locking the thread shows the lock alert and disables the composer for students
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And the embedded discussion is loaded
    When I click on "Discussion settings" "button"
    And I click on "Lock thread" "checkbox"
    And I wait until the page is ready
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And the embedded discussion is loaded
    Then I should see "This discussion is locked. New posts and edits are disabled."
    And "[data-action='open-composer'][disabled]" "css_element" should exist
