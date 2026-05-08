@format @format_ucl
Feature: Initial section has custom layout
  In order to to quickly find important course information
  As a user
  I need to see consistent layout

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | startdate     |
      | Course 1 | C1        | ucl    | 0             | 5           | ##yesterday## |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | teacher2 | Teacher   | 2        | teacher2@example.com |
      | teacher3 | Teacher   | 3        | teacher3@example.com |
      | teacher4 | Teacher   | 4        | teacher4@example.com |
      | teacher5 | Teacher   | 5        | teacher5@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C1     | teacher        |
      | teacher2 | C1     | editingteacher |
      | teacher3 | C1     | teacher        |
      | teacher4 | C1     | teacher        |
      | teacher5 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: Initial section summary appears above main section content
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Edit" "link"
    And I set the field "Description" to "Welcome to Stamptown"
    And I press "Save changes"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And "Welcome to Stamptown" "text" should exist in the ".behat-ucl-section-description" "css_element"
    And "Welcome to Stamptown" "text" should not exist in the ".section-item .content" "css_element"

  Scenario: Course contacts appear in initial section
    When I log in as "admin"
    And I am on "Course 1" course homepage
    Then "Contacts" "text" should exist
    And "Teacher 1" "link" should exist
    And "Teacher 2" "link" should appear after "Teacher 1" "link"
    And "Teacher 5" "link" should appear after "Teacher 2" "link"
    And "Teacher 3" "link" should not exist
    And "Teacher 4" "link" should not exist

  Scenario: User without permission format/ucl:editcoursecontacts cannot show/hide contacts

  Scenario: User with permission format/ucl:editcoursecontacts can show/hide contacts