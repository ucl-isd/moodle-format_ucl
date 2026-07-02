@format @format_ucl
Feature: Summative assessments widget visibility
  In order to avoid showing incorrect assessment information
  As a student
  I should only see the summative assessments widget when summatives are configured

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course" exists:
      | fullname         | Course 1 |
      | shortname        | C1       |
      | format           | ucl      |
      | coursedisplay    | 0        |
      | numsections      | 3        |
      | initsections     | 1        |
      | enablecompletion | 1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | course | idnumber | name    | section | duedate      |
      | assign   | C1     | assign1  | Essay 1 | 2       | ##tomorrow## |

  Scenario: Summative assessments widget is not shown when no summatives are configured
    When I am on the "C1" "Course" page logged in as "student1"
    Then ".behat-ucl-assessments-widget" "css_element" should not exist

  Scenario: Summative assessments widget is shown when a summative is configured
    Given the activity with idnumber "assign1" in course "C1" is marked as summative
    When I am on the "C1" "Course" page logged in as "student1"
    Then ".behat-ucl-assessments-widget" "css_element" should exist
    And I should see "Summative assessments"
    And I should see "Essay 1"
    And ".behat-ucl-assessment-row" "css_element" should exist

  Scenario: Summative assessments widget is not shown when mapped activities have no due date
    Given the following "activities" exist:
      | activity | course | idnumber | name             | section |
      | assign   | C1     | assign2  | Undated essay    | 2       |
    And the activity with idnumber "assign2" in course "C1" is marked as summative
    When I am on the "C1" "Course" page logged in as "student1"
    Then ".behat-ucl-assessments-widget" "css_element" should not exist
