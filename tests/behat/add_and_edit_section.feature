@format @format_ucl
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

  Scenario: Add and edit section
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I click on ".behat-btn-add-content" "css_element"
    Then I should see "Edit section settings"
    And I set the field "Section name" to "Welcome to Stamptown"
    And I press "Save changes"
    And "Welcome to Stamptown" "link" should appear after "Section 5" "link"
