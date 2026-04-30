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
    And I click on "Add new section" "link"
    Then I should see "Edit section settings"
    And I set the field "Section name" to "Welcome to Stamptown"
    And I press "Save changes"
    And "Welcome to Stamptown" "link" should appear after "Section 5" "link"

  @javascript
  Scenario: Section controls appear in expected order
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Section 2" "link" in the "#toc" "css_element"
    When I open ucl section "Section 2" edit menu
    Then "Move" "link" should appear after "Edit section" "link"
    And "Hide" "link" should appear after "Move" "link"
    And "Highlight" "link" should appear after "Hide" "link"
    And "Duplicate" "link" should appear after "Highlight" "link"
    And "Delete" "link" should appear after "Duplicate" "link"

  @javascript
  Scenario: Hide section action updates table of contents
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    When I click on "Section 2" "link" in the "#toc" "css_element"
    Then "[data-region='sectionvisibility']" "css_element" should not be visible
    And "#toc [data-section='2'] .flag-hidden" "css_element" should not be visible
    And I open ucl section "Section 2" edit menu
    And I click on "Hide" "link" in the ".section-actions" "css_element"
    And "[data-region='sectionvisibility']" "css_element" should be visible
    And "#toc [data-section='2'] .flag-hidden" "css_element" should be visible

  @javascript
  Scenario: Move section action updates table of contents
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    When I click on "Section 2" "link" in the "#toc" "css_element"
    Then I should see "Section 1" in the ".behat-previous-section" "css_element"
    And I should see "Section 3" in the ".behat-next-section" "css_element"
    And I open ucl section "Section 2" edit menu
    And I click on "Move" "link" in the ".section-actions" "css_element"
    And I click on "Section 3" "link" in the "Move section" "dialogue"
    # TODO Fix slow JS - CTP-6261
    Given the site is running Moodle version 99.0 or higher
    # The following steps should not be executed. If they are, the test will fail.
    And I should see "Section 3" in the ".behat-previous-section" "css_element"
    And I should see "Section 4" in the ".behat-next-section" "css_element"

  @javascript
  Scenario: Duplicate section action

  @javascript
  Scenario: Delete section action
