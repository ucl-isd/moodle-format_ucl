@format @format_ucl @javascript
Feature: Custom contacts are backed up and restored
  After I back up and restore a course
  As a user
  I need to see custom contacts restored

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1        | ucl    | 0             | 5           |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | enableasyncbackup | 0 |
    And the following config values are set as admin:
      | displaycontacts   | 1 | format_ucl |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Add custom contact" "link"
    And I set the following fields to these values:
      | Role        | Ring Master           |
      | Name        | Jack Tucker           |
      | Email       | zzucker@example.com |
      | Description | Clown king            |
    And I press "Save"

  Scenario: Backup and restore a course containing custom contacts
    When I am on the "Course 1" course page logged in as admin
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name       | Course 2 |
      | Schema | Course short name | C2       |
    And I am on the "Course 2" course page
    Then "Course contacts" "text" should exist
    And I should see "Ring Master"
    And I should see "Jack Tucker"
    And I should see "zzucker@example.com"
    And I should see "Clown king"
