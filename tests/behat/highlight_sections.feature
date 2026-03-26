@format @format_ucl
Feature: UCL sections can be highlighted
  In order to mark sections
  As a teacher
  I need to highlight and unhighlight sections

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course" exists:
      | fullname      | Course 1 |
      | shortname     | C1       |
      | format        | ucl      |
      | coursedisplay | 0        |
      | numsections   | 5        |
      | initsections  | 1        |
    And the following "activities" exist:
      | activity | name                 | intro                       | course | idnumber | section |
      | assign   | Test assignment name | Test assignment description | C1     | assign1  | 0       |
      | book     | Test book name       | Test book description       | C1     | book1    | 1       |
      | lesson   | Test lesson name     | Test lesson description     | C1     | lesson1  | 4       |
      | choice   | Test choice name     | Test choice description     | C1     | choice1  | 5       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: Highlight a section
    Given I click on "Section 2" "link" in the "#toc" "css_element"
    And I open ucl section "Section 2" edit menu
    When I click on "Highlight" "link" in the "#ucl-section-actions" "css_element"
    Then I should see "Highlighted" in the "[data-sectionname='Section 2']" "css_element"

  @javascript
  Scenario: Highlight a section when another section is already highlighted
    Given I click on "Section 3" "link" in the "#toc" "css_element"
    And I open ucl section "Section 3" edit menu
    When I click on "Highlight" "link" in the "#ucl-section-actions" "css_element"
    Then I should see "Highlighted" in the "[data-sectionname='Section 3']" "css_element"
    And I click on "Section 2" "link" in the "#toc" "css_element"
    And I open ucl section "Section 2" edit menu
    And I click on "Highlight" "link" in the "#ucl-section-actions" "css_element"
    And I click on "Section 3" "link" in the "#toc" "css_element"
    And I should not see "Highlighted" in the "[data-sectionname='Section 3']" "css_element"

  @javascript
  Scenario: Unhighlight a section
    Given I click on "Section 3" "link" in the "#toc" "css_element"
    And I open ucl section "Section 3" edit menu
    When I click on "Highlight" "link" in the "#ucl-section-actions" "css_element"
    Then I should see "Highlighted" in the "[data-sectionname='Section 3']" "css_element"
    And I open ucl section "Section 3" edit menu
    And I click on "Unhighlight" "link" in the "#ucl-section-actions" "css_element"
    And I should not see "Highlighted" in the "[data-sectionname='Section 3']" "css_element"
