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
      | activity | course | idnumber | name               | section | completion |
      | page     | C1     | page1    | Activity sample 1  | 2       | 1          |
    And I change window size to "large"

  Scenario: TOC progress is not output when course completion is disabled
    Given the following "course" exists:
      | fullname         | Course 2 |
      | shortname        | C2       |
      | format           | ucl      |
      | coursedisplay    | 0        |
      | numsections      | 3        |
      | initsections     | 1        |
      | enablecompletion | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C2     | editingteacher |
      | student1 | C2     | student        |
    And the following "activities" exist:
      | activity | course | idnumber | name               | section | completion |
      | page     | C2     | pagec2   | Activity sample C2 | 2       | 1          |
    And I am on the "C2" "Course" page logged in as "student1"
    Then "#toc .pie[data-id]" "css_element" should not exist
    And "#toc .progress-indicator .sr-only" "css_element" should not exist

  Scenario: TOC progress is hidden when editing mode is on
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    Then "#toc .pie[data-id]" "css_element" should not exist

  Scenario: TOC progress is not output for a section with no completable activities
    Given the following "activities" exist:
      | activity | course | idnumber | name                        | section | completion |
      | page     | C1     | page2    | Activity without completion | 3       | 0          |
    And I am on the "C1" "Course" page logged in as "student1"
    Then "[data-section='3'] .pie[data-id]" "css_element" should not exist in the "#toc" "css_element"

  Scenario: TOC progress updates after manual completion
    Given I am on the "C1" "Course" page logged in as "student1"
    And I click on "Section 2" "link" in the "#toc" "css_element"
    And "#toc [data-section='2'] .pie[data-id]" "css_element" should exist
    And I wait until "Mark as done" "button" exists
    And the "data-percentage" attribute of "#toc [data-section='2'] .pie[data-id]" "css_element" should contain "0"
    When I press "Mark as done"
    And I wait until "Done" "button" exists
    Then the "data-percentage" attribute of "#toc [data-section='2'] .pie[data-id]" "css_element" should contain "100"
    When I reload the page
    Then the "data-percentage" attribute of "#toc [data-section='2'] .pie[data-id]" "css_element" should contain "100"
