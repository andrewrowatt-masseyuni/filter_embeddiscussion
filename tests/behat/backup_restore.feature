@filter @filter_embeddiscussion
Feature: Backup and restore embedded discussion threads
  In order to migrate course content between courses or sites
  As an admin
  I should be able to back up and restore courses with embedded
  discussions, retaining thread settings (anonymous, locked),
  posts and votes.

  Background:
    Given the following config values are set as admin:
      | enableasyncbackup | 0 |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | student1 | Student   | One      |
      | student2 | Student   | Two      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the "embeddiscussion" filter is "on"
    And the following "activities" exist:
      | activity | course | name      | intro                            | idnumber |
      | label    | C1     | Discuss A | {embeddeddiscussion:Backup demo} | l1       |
    And the following "filter_embeddiscussion > threads" exist:
      | name        | course | activity | anonymous | locked |
      | Backup demo | C1     | l1       | 1         | 1      |
    And the following "filter_embeddiscussion > posts" exist:
      | thread      | user     | content                                |
      | Backup demo | student1 | Discussion seed text from student one  |
      | Backup demo | student2 | Reply text from the second student     |
    And the following "filter_embeddiscussion > votes" exist:
      | thread      | user     | postcontent                          | direction |
      | Backup demo | student1 | Reply text from the second student   | 1         |
    And I change the window size to "large"

  @javascript
  Scenario: Backup and restore with user data preserves settings, posts and votes
    Given I log in as "admin"
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | embeddisc_users.mbz |
    And I restore "embeddisc_users.mbz" backup into a new course using this options:
      | Schema | Course name       | Course Restored With Users |
      | Schema | Course short name | C1RWU                      |
    And I log out
    And I log in as "student2"
    And I am on "Course Restored With Users" course homepage
    And the embedded discussion is loaded
    Then I should see "This discussion is locked. New posts and edits are disabled."
    And I should see "Your posts will be anonymous to other students."
    And I should see "Discussion seed text from student one"
    And I should see "Reply text from the second student"
    And the "up" vote count on the embedded discussion post containing "Reply text from the second student" should be "1"

  @javascript
  Scenario: Backup and restore without user data preserves settings but excludes posts and votes
    Given I log in as "admin"
    When I backup "Course 1" course using this options:
      | Initial      | Include enrolled users | 0                     |
      | Confirmation | Filename               | embeddisc_nousers.mbz |
    And I restore "embeddisc_nousers.mbz" backup into a new course using this options:
      | Schema | Course name       | Course Restored No Users |
      | Schema | Course short name | C1RNU                    |
    # Enrolled users were not in the backup, so re-enrol a viewer in the restored course.
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1RNU  | student |
    And I log out
    And I log in as "student1"
    And I am on "Course Restored No Users" course homepage
    And the embedded discussion is loaded
    Then I should see "This discussion is locked. New posts and edits are disabled."
    And I should see "Your posts will be anonymous to other students."
    And I should not see "Discussion seed text from student one"
    And I should not see "Reply text from the second student"
