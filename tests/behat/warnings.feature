@format @format_ucl
Feature: Appropriate Tips are shown to user
  In order to build a baseline course
  As a user
  I need to see tips about my course structure

  Scenario: Course image tip is shown for courses without images
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | startdate     |
      | Course 1 | C1        | ucl    | 0             | 1           | ##yesterday## |
    When I am on the "C1" "Course" page logged in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    Then ".behat-ucl-tips" "css_element" should exist
    And ".behat-nocourseimg" "css_element" should exist

  Scenario: Unnamed section tip is shown for sections without a name
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | startdate     |
      | Course 1 | C1        | ucl    | 0             | 1           | ##yesterday## |
    When I am on the "C1" "Course" page logged in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    Then ".behat-unnamedsections" "css_element" should exist
    And ".behat-namecount" "css_element" should exist

  Scenario: Too many sections tip is shown when the course exceeds the recommended maximum
    Given the following config values are set as admin:
      | config                 | value | plugin     |
      | recommendedmaxsections | 2     | format_ucl |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | startdate     |
      | Course 1 | C1        | ucl    | 0             | 3           | ##yesterday## |
    When I am on the "C1" "Course" page logged in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    Then ".behat-toomanysections" "css_element" should exist

  Scenario: Too few activities tip and guidance link are shown when several sections have at most one activity
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | startdate     |
      | Course 1 | C1        | ucl    | 0             | 2           | ##yesterday## |
    When I am on the "C1" "Course" page logged in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    Then ".behat-toofewmods" "css_element" should exist
    And ".behat-linktoguidance" "css_element" should exist
