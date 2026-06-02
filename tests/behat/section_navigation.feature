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

  @javascript
  Scenario: Move section action updates section navigation
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    When I click on "Section 2" "link" in the "#toc" "css_element"
    Then I should see "Section 1" in the ".behat-previous-section" "css_element"
    And I should see "Section 3" in the ".behat-next-section" "css_element"
    And I open ucl section "Section 2" edit menu
    And I click on "Move" "link" in the ".section-actions" "css_element"
    And I click on "Section 3" "link" in the "Move section" "dialogue"
    And I should see "Section 3" in the ".behat-previous-section" "css_element"
    And I should see "Section 4" in the ".behat-next-section" "css_element"
