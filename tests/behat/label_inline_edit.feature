@format @format_ucl @javascript
Feature: Edit label content inline
  In order to update label content quickly
  As a course editor
  I need to edit a label without leaving the course page

  Background:
    Given the following "course" exists:
      | fullname      | Course 1 |
      | shortname     | C1       |
      | format        | ucl      |
      | coursedisplay | 0        |
      | numsections   | 3        |
      | initsections  | 1        |
    And the following "activities" exist:
      | activity | name    | intro                 | course | section | idnumber |
      | label    | Label 1 | Original label content | C1     | 1       | label1   |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  Scenario: Save and cancel inline label edits
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Section 1" "link" in the "#toc" "css_element"
    Then "Original label content" "text" should exist in the ".activity-altcontent" "css_element"
    And "Edit label content" "link" should exist
    When I click on "Edit label content" "link"
    Then ".activity-altcontent" "css_element" should not be visible
    And "[data-region='label-form']" "css_element" should be visible
    And I set the field "Text" to "Updated label content"
    And I click on "Save" "button" in the "[data-region='label-form']" "css_element"
    Then "Updated label content" "text" should exist in the ".activity-altcontent" "css_element"
    When I click on "Edit label content" "link"
    And I set the field "Text" to "Discarded label content"
    And I click on "Cancel" "button" in the "[data-region='label-form']" "css_element"
    Then "Updated label content" "text" should exist in the ".activity-altcontent" "css_element"
    And "Discarded label content" "text" should not exist
    When I click on "Edit label content" "link"
    And I set the field "Text" to ""
    And I click on "Save" "button" in the "[data-region='label-form']" "css_element"
    Then ".activity-altcontent" "css_element" should not exist
    When I click on "Edit label content" "link"
    And I set the field "Text" to "Restored label content"
    And I click on "Save" "button" in the "[data-region='label-form']" "css_element"
    Then "Restored label content" "text" should exist in the ".activity-altcontent" "css_element"
    When I click on "Edit label content" "link"
    And I set the field "Text" to "Discarded replacement"
    And I click on "Cancel" "button" in the "[data-region='label-form']" "css_element"
    Then ".activity-altcontent" "css_element" should be visible
    And "Restored label content" "text" should exist in the ".activity-altcontent" "css_element"
    When I reload the page
    Then "Restored label content" "text" should exist in the ".activity-altcontent" "css_element"
    And "Discarded replacement" "text" should not exist

  Scenario: The inline editor remains available after hiding and showing a label
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Hide" "link" in the "Label 1" activity
    Then "Edit label content" "link" should exist in the "Label 1" activity
    When I click on "Show" "link" in the "Label 1" activity
    Then "Edit label content" "link" should exist in the "Label 1" activity

  Scenario: Adding a label from a section returns to that section
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Section 1" "link" in the "#toc" "css_element"
    And I click on "Add text and media" "link"
    And I set the field "Text" to "Added from section"
    And I click on "Save and return to course" "button"
    Then the url should match "/course/section\.php\?id=\d+#module-\d+$"
    And "Added from section" "text" should exist in the ".activity-altcontent" "css_element"
