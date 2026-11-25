@format @format_ucl @javascript
Feature: Section edit menu actions are reflected in the course page
  In order to manage sections
  As a user
  I need the table of contents and section navigation to update when a section does

  Background:
    Given the following "course" exists:
      | fullname      | Course 1 |
      | shortname     | C1       |
      | format        | ucl      |
      | coursedisplay | 0        |
      | numsections   | 5        |
      | initsections  | 1        |
    And I change window size to "large"
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Section 2" "link" in the "#toc" "css_element"

  Scenario: Hide section action updates the table of contents
    When I open ucl section "Section 2" edit menu
    Then "[data-region='sectionvisibility']" "css_element" should not be visible
    And "#toc [data-section='2'] .flag-hidden" "css_element" should not be visible
    And I click on "Hide" "link" in the ".section-actions" "css_element"
    And "[data-region='sectionvisibility']" "css_element" should be visible
    And "#toc [data-section='2'] .flag-hidden" "css_element" should be visible

  Scenario: Duplicate section action updates table of contents
    When I set the field "Edit section name" to "Stamptown"
    And I open ucl section "Stamptown" edit menu
    And I click on "Duplicate" "link" in the ".section-actions" "css_element"
    Then "Stamptown (copy)" "link" should exist in the "#toc" "css_element"

  Scenario: Delete section action redirects to course page
    When I open ucl section "Section 2" edit menu
    And I click on "Delete" "link" in the ".section-actions" "css_element"
    And I click on "Delete" "button" in the "Delete section?" "dialogue"
    # Section 2 should be removed.
    Then I should not see "Section 2"
    # The user should be redirected to the course page.
    And I should see "Introduction" in the "page" "region"

  Scenario: Highlight section action shows the highlighted label
    When I open ucl section "Section 2" edit menu
    And I click on "Highlight" "link" in the ".section-actions" "css_element"
    Then I should see "Highlighted" in the "[data-sectionname='Section 2']" "css_element"
