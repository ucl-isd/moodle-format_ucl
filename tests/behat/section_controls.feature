@format @format_ucl @javascript
Feature: Changes made using section controls are reflected in the table of contents
  In order to navigate my course
  As a user
  I need the table of contents to update when a section does

  Background:
    Given the following "course" exists:
      | fullname      | Course 1 |
      | shortname     | C1       |
      | format        | ucl      |
      | coursedisplay | 0        |
      | numsections   | 5        |
      | initsections  | 1        |

  Scenario: Section edit menu is available on the single section page
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    When I click on "Section 2" "link" in the "#toc" "css_element"
    Then ".section-actions" "css_element" should exist
