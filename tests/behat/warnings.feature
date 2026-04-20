@format @format_ucl
Feature: Appropriate Tips are shown to user
  In order to build a baseline course
  As a user
  I need to see tips about my course structure

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | startdate     |
      | Course 1 | C1        | ucl    | 0             | 5           | ##yesterday## |
      | Course 2 | C2        | ucl    | 0             | 17          | ##yesterday## |

  Scenario: Tips do not appear on section pages
    When I am on the "C1" "Course" page logged in as "admin"
    And I click on "New section 2" "link" in the "#toc" "css_element"
    Then ".behat-ucl-tips" "css_element" should not exist

  Scenario: Course image tip is shown for courses without images
    When I am on the "C1" "Course" page logged in as "admin"
    Then ".behat-ucl-tips" "css_element" should exist
    Then ".behat-nocourseimg" "css_element" should exist

  @javascript @_file_upload
  Scenario: Course image tip is not shown for courses with images
    Given I am on the "C1" "Course" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I upload "course/format/ucl/tests/fixtures/stamptown.jpg" file to "Course image" filemanager
    And I press "Save and display"
    # "No course image"
    Then ".behat-nocourseimg" "css_element" should not exist

  @javascript
  Scenario: Section name tip is shown for unnamed sections
    Given I log in as "admin"
    And I am on the "Course 1 > New section 1" "course > section" page
    And I turn editing mode on
    When I set the field "Edit section name" in the "[data-region='ucl-section-name']" "css_element" to "Stamptown"
    And I am on "Course 1" course homepage
    # "4 unnamed sections"
    Then ".behat-unnamedsections" "css_element" should exist
    And I should see "4" in the ".behat-namecount" "css_element"

  Scenario: Number of sections Tip is not shown when suggested max is not exceeded
    When I am on the "C1" "Course" page logged in as "admin"
    Then ".behat-toomanysections" "css_element" should not exist

  Scenario: Number of sections Tip is shown when suggested max is exceeded
    When I am on the "C2" "Course" page logged in as "admin"
    # "Courses should have a maximum of 16 sections"
    Then ".behat-toomanysections" "css_element" should exist
    And I should see "16" in the ".behat-sectioncount" "css_element"

  Scenario: Admin can edit setting max number of sections
    Given I log in as "admin"
    And I navigate to "Plugins > Course formats > UCL Format" in site administration
    And I set the following fields to these values:
      | Number of sections to suggest as Tip | 4 |
    And I press "Save changes"
    When I am on the "C1" "Course" page logged in as "admin"
    # "Courses should have a maximum of 4 sections"
    Then ".behat-toomanysections" "css_element" should exist
    And I should see "4" in the ".behat-sectioncount" "css_element"

  Scenario: Section content tip is shown for sections with less than 2 activities/resources
    Given the following "activities" exist:
      | activity | name                 | intro                       | course | section | idnumber  |
      | assign   | Test assignment name | Test assignment description | C1     | 1       | assign1   |
      | book     | Test book name       | Test book description       | C1     | 2       | book1     |
      | choice   | Test choice name     | Test choice description     | C1     | 3       | choice1   |
      | data     | Test database name   | Test database description   | C1     | 3       | data1     |
      | feedback | Test feedback name   | Test feedback description   | C1     | 4       | feedback1 |
      | folder   | Test folder name     | Test folder description     | C1     | 5       | folder1   |
      | glossary | Test glossary name   | Test glossary description   | C1     | 5       | glossary1 |
    When I am on the "C1" "Course" page logged in as "admin"
    # "3 sections have one or less activities/resources"
    Then ".behat-toofewmods" "css_element" should exist
    And I should see "3" in the ".behat-modcount" "css_element"
