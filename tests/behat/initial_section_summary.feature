@format @format_ucl @javascript
Feature: Initial section has custom layout
  In order to to quickly find important course information
  As a user
  I need to see a consistent layout in the initial section

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1        | ucl    | 0             | 5           |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: Initial section summary appears above main section content
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    # Ensure the button is scrolled into view to prevent help button from interrupting the click.
    And I change window size to "1366x968"
    And I click on "Edit" "link"
    And I set the field "Description" to "Welcome to Stamptown"
    And I press "Save changes"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And "Welcome to Stamptown" "text" should exist in the ".behat-ucl-section-description" "css_element"
    And "Welcome to Stamptown" "text" should not exist in the ".section-item .content" "css_element"
