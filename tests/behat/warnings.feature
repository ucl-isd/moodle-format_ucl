@format @format_ucl
Feature: Appropriate Tips are shown to user
  In order to build a baseline course
  As a user
  I need to see tips about my course structure

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | startdate     |
      | Course 1 | C1        | ucl    | 0             | 1           | ##yesterday## |

  Scenario: Course image tip is shown for courses without images
    When I am on the "C1" "Course" page logged in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    Then ".behat-ucl-tips" "css_element" should exist
    Then ".behat-nocourseimg" "css_element" should exist

  Scenario: Unnamed section tip is shown for sections without a name
    When I am on the "C1" "Course" page logged in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    Then ".behat-unnamedsections" "css_element" should exist
    And ".behat-namecount" "css_element" should exist

  @javascript @_file_upload
  Scenario: Course image tip is not shown for courses with images
    Given I am on the "C1" "Course" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I upload "course/format/ucl/tests/fixtures/stamptown.jpg" file to "Course image" filemanager
    And I press "Save and display"
    # "Add a course image"
    Then ".behat-nocourseimg" "css_element" should not exist

  @javascript
  Scenario: Link to guidance is shown with the editor tips
    Given the following "activities" exist:
      | activity | name                 | intro                       | course | section | idnumber |
      | assign   | Test assignment name | Test assignment description | C1     | 1       | assign1  |
    And I log in as "admin"
    And I navigate to "Plugins > Course formats > UCL Format" in site administration
    And I set the following fields to these values:
      | Number of sections to suggest as Tip | 1 |
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    And I am on the "Course 1 > New section" "course > section" page
    And I set the field "Edit section name" in the ".ucl-section-name" "css_element" to "Stamptown"
    And I am on "Course 1" course homepage
    # Only the add a course image warning should exist
    Then ".behat-nocourseimg" "css_element" should exist
    And ".behat-linktoguidance" "css_element" should exist

    When I click on ".behat-add-section" "css_element"
    And I set the field "Section name" to "Welcome to Stamptown"
    And I click on "Add" "button" in the "Add a new section" "dialogue"
    And I am on "Course 1" course homepage
    Then ".behat-unnamedsections" "css_element" should not exist
    And ".behat-toomanysections" "css_element" should exist
    And ".behat-toofewmods" "css_element" should exist
    And ".behat-linktoguidance" "css_element" should exist
