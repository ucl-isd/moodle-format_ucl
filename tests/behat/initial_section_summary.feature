@format @format_ucl
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
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    And the course "C1" has the initial section summary "Welcome to Stamptown"

  Scenario: Initial section summary appears above main section content
    When I am on the "C1" course page logged in as "student1"
    Then "Welcome to Stamptown" "text" should exist in the ".behat-ucl-section-description" "css_element"
    And "Welcome to Stamptown" "text" should not exist in the ".section-item .content" "css_element"
