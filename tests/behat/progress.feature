@format @format_ucl @javascript
Feature: TOC progress is shown on load and updates after manual completion
  In order to understand section progress in the UCL TOC
  As a student
  I need to see correct progress text on page load and after completion changes

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "course" exists:
      | fullname         | Course 1 |
      | shortname        | PROG1    |
      | format           | ucl      |
      | coursedisplay    | 0        |
      | numsections      | 3        |
      | initsections     | 1        |
      | enablecompletion | 1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | PROG1  | editingteacher |
      | student1 | PROG1  | student        |
      | student2 | PROG1  | student        |
    And the following "groups" exist:
      | course | idnumber | name     |
      | PROG1  | G1       | My group |
    And the following "group members" exist:
      | group | user     |
      | G1    | student1 |
    And the following "activities" exist:
      | activity | course | idnumber | name              | section | completion |
      | page     | PROG1  | page1    | Activity sample 1 | 2       | 1          |
      | page     | PROG1  | page2    | Activity sample 2 | 2       | 1          |
      | page     | PROG1  | page0    | Activity sample 0 | 0       | 1          |
    And I change window size to "large"

  Scenario: TOC progress is not output when course completion is disabled
    Given the following "course" exists:
      | fullname         | Course 2 |
      | shortname        | PROG2    |
      | format           | ucl      |
      | numsections      | 3        |
      | enablecompletion | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | PROG2  | student |
    And the following "activities" exist:
      | activity | course | idnumber | name               | section | completion |
      | page     | PROG2  | pagec2   | Activity sample C2 | 2       | 1          |
    And I am on the "PROG2" "Course" page logged in as "student1"
    Then "#toc .progress-indicator[data-id] .pie" "css_element" should not exist

  Scenario: TOC progress is hidden when editing mode is on
    Given I am on the "PROG1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    Then "#toc .progress-indicator[data-id] .pie" "css_element" should not exist

  Scenario: TOC progress is not output for a section with no completable activities
    Given the following "activities" exist:
      | activity | course | idnumber | name                        | section | completion |
      | page     | PROG1  | page2    | Activity without completion | 3       | 0          |
    And I am on the "PROG1" "Course" page logged in as "student1"
    Then "[data-section='3'] .progress-indicator[data-id] .pie" "css_element" should not exist in the "#toc" "css_element"

  Scenario: TOC progress updates after manual completion
    Given I am on the "PROG1" "Course" page logged in as "student1"
    And I click on "Section 2" "link" in the "#toc" "css_element"
    And "#toc [data-section='2'] .progress-indicator[data-id] .pie" "css_element" should exist
    And the "data-behat-percentage" attribute of "#toc [data-section='2'] .progress-indicator[data-id] .pie" "css_element" should contain "0"
    When I toggle the manual completion state of "Activity sample 2"
    Then the "data-behat-percentage" attribute of "#toc [data-section='2'] .progress-indicator[data-id] .pie" "css_element" should contain "50"
    And I reload the page
    And the "data-behat-percentage" attribute of "#toc [data-section='2'] .progress-indicator[data-id] .pie" "css_element" should contain "50"

  Scenario: TOC progress in section 0 updates after manual completion
    Given I am on the "PROG1" "Course" page logged in as "student1"
    And "#toc [data-section='0'] .progress-indicator[data-id] .pie" "css_element" should exist
    And the "data-behat-percentage" attribute of "#toc [data-section='0'] .progress-indicator[data-id] .pie" "css_element" should contain "0"
    When I toggle the manual completion state of "Activity sample 0"
    Then the "data-behat-percentage" attribute of "#toc [data-section='0'] .progress-indicator[data-id] .pie" "css_element" should contain "100"
    And I reload the page
    And the "data-behat-percentage" attribute of "#toc [data-section='0'] .progress-indicator[data-id] .pie" "css_element" should contain "100"
