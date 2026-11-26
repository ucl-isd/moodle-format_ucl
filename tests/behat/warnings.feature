@format @format_ucl
Feature: Appropriate Tips are shown to user
  In order to build a baseline course
  As a user
  I need to see tips about my course structure

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | startdate     |
      | Course 1 | C1        | ucl    | 0             | 1           | ##yesterday## |
      | Course 2 | C2        | ucl    | 0             | 17          | ##yesterday## |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | teacher2 | Teacher   | 2        | teacher2@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C2     | editingteacher |
      | teacher2 | C2     | editingteacher |
      | student1 | C2     | student        |

  Scenario: Tips do not appear on section pages
    When I am on the "C1" "Course" page logged in as "admin"
    And I turn editing mode on
    Then ".behat-ucl-tips" "css_element" should exist
    When I am on the "Course 1 > New section" "course > section" page
    Then ".behat-ucl-tips" "css_element" should not exist

  Scenario: Tips are shown only while editing
    When I am on the "C1" "Course" page logged in as "admin"
    And I turn editing mode on
    Then ".behat-ucl-tips" "css_element" should exist
    When I turn editing mode off
    Then ".behat-ucl-tips" "css_element" should not exist

  Scenario: Course image tip is shown for courses without images
    When I am on the "C1" "Course" page logged in as "admin"
    And I turn editing mode on
    Then ".behat-ucl-tips" "css_element" should exist
    And ".behat-nocourseimg" "css_element" should exist

  Scenario: The tips panel links to the configured guidance
    Given the following config values are set as admin:
      | config         | value                        | plugin     |
      | linktoguidance | https://example.com/guidance | format_ucl |
    When I am on the "C1" "Course" page logged in as "admin"
    And I turn editing mode on
    Then ".behat-linktoguidance a[href='https://example.com/guidance']" "css_element" should exist

  Scenario: Course image tip is not shown for courses with images
    Given the course "C1" has an image
    When I am on the "C1" "Course" page logged in as "admin"
    And I turn editing mode on
    Then ".behat-ucl-tips" "css_element" should exist
    And ".behat-nocourseimg" "css_element" should not exist

  Scenario: The configured maximum controls the section count tip
    Given the following config values are set as admin:
      | config                 | value | plugin     |
      | recommendedmaxsections | 20    | format_ucl |
    When I am on the "C2" "Course" page logged in as "admin"
    And I turn editing mode on
    Then ".behat-ucl-tips" "css_element" should exist
    And ".behat-toomanysections" "css_element" should not exist

  Scenario: The activity tip appears when more than one section has fewer than two activities
    Given the following "courses" exist:
      | fullname   | shortname | format | numsections |
      | One short  | SHORT1    | ucl    | 2           |
      | Two short  | SHORT2    | ucl    | 3           |
    And the following "activities" exist:
      | activity | name   | course | section |
      | page     | Page 1 | SHORT1 | 1       |
      | page     | Page 2 | SHORT1 | 1       |
      | page     | Page 1 | SHORT2 | 1       |
      | page     | Page 2 | SHORT2 | 1       |
      | page     | Page 3 | SHORT2 | 2       |
    When I am on the "SHORT1" "Course" page logged in as "admin"
    And I turn editing mode on
    Then ".behat-ucl-tips" "css_element" should exist
    And ".behat-toofewmods" "css_element" should not exist
    When I am on the "SHORT2" "Course" page
    Then ".behat-toofewmods" "css_element" should exist
    And I should see "2" in the ".behat-modcount" "css_element"

  Scenario: The section count tip appears above the configured maximum
    Given the following config values are set as admin:
      | config                 | value | plugin     |
      | recommendedmaxsections | 1     | format_ucl |
    And the following "courses" exist:
      | fullname | shortname | format | numsections |
      | Course 3 | C3        | ucl    | 2           |
    When I am on the "C1" "Course" page logged in as "admin"
    And I turn editing mode on
    Then ".behat-ucl-tips" "css_element" should exist
    And ".behat-toomanysections" "css_element" should not exist
    When I am on the "C3" "Course" page
    Then ".behat-toomanysections" "css_element" should exist
    And ".behat-linktoguidance" "css_element" should exist

  Scenario: Teachers with permission to update the course see tips
    When I am on the "C2" course page logged in as "teacher1"
    And I turn editing mode on
    Then ".behat-nocourseimg" "css_element" should exist
    And ".behat-unnamedsections" "css_element" should exist
    And ".behat-toomanysections" "css_element" should exist
    And ".behat-toofewmods" "css_element" should exist
    And ".behat-linktoguidance" "css_element" should exist

  Scenario: Users without permission to update the course do not see tips while editing
    Given the following "permission overrides" exist:
      | capability           | permission | role           | contextlevel | reference |
      | moodle/course:update | Prevent    | editingteacher | Course       | C2        |
    When I am on the "C2" course page logged in as "teacher2"
    And I turn editing mode on
    Then ".behat-ucl-tips" "css_element" should not exist

  Scenario: Students do not see tips
    When I am on the "C2" course page logged in as "student1"
    Then ".behat-ucl-tips" "css_element" should not exist
