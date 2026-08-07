@format @format_ucl @javascript
Feature: Adding a new section lands on the editing page
  In order to add a new section
  As a user
  I need to edit a new section when one is added

  Background:
    Given the following "course" exists:
      | fullname      | Course 1 |
      | shortname     | C1       |
      | format        | ucl      |
      | coursedisplay | 0        |
      | numsections   | 5        |
      | initsections  | 1        |
    And the following "users" exist:
      | username | firstname | lastname | email                | maildisplay |
      | teacher1 | Teacher   | 1        | teacher1@example.com | 1           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  Scenario: Add and edit section
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I click on ".behat-add-section" "css_element"
    Then "Add a new section" "dialogue" should be visible
    And I set the field "Section name" to "Welcome to Stamptown"
    And I click on "Add" "button" in the "Add a new section" "dialogue"
    And "Welcome to Stamptown" "link" should appear after "Section 5" "link"
