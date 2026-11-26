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
    And the following "user preferences" exist:
      | user  | preference               | value |
      | admin | behat_keep_drawer_closed | 1     |
      | admin | drawer-open-block        | 0     |

  Scenario: Section controls appear in expected order and hide updates the table of contents
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I am on the "Course 1 > Section 2" "course > section" page
    When I open ucl section "Section 2" edit menu
    Then "Move" "link" should appear after "Edit section" "link"
    And "Hide" "link" should appear after "Move" "link"
    And "Highlight" "link" should appear after "Hide" "link"
    And "Duplicate" "link" should appear after "Highlight" "link"
    And "Delete" "link" should appear after "Duplicate" "link"
    And "[data-region='sectionvisibility']" "css_element" should not be visible
    And "#toc [data-section='2'] .flag-hidden" "css_element" should not be visible
    And I click on "Hide" "link" in the ".section-actions" "css_element"
    And "[data-region='sectionvisibility']" "css_element" should be visible
    And "#toc [data-section='2'] .flag-hidden" "css_element" should be visible

  Scenario: Duplicate section action updates table of contents
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    When I am on the "Course 1 > Section 2" "course > section" page
    And I open ucl section "Section 2" edit menu
    And I click on "Duplicate" "link" in the ".section-actions" "css_element"
    Then "Section 2 (copy)" "link" should exist in the "#toc" "css_element"

  Scenario: Delete section action redirects to course page
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    When I am on the "Course 1 > Section 2" "course > section" page
    And I open ucl section "Section 2" edit menu
    And I click on "Delete" "link" in the ".section-actions" "css_element"
    And I click on "Delete" "button" in the "Delete section?" "dialogue"
    # Section 2 should be removed.
    Then I should not see "Section 2"
    # The user should be redirected to the course page.
    And I should see "Introduction" in the "page" "region"
