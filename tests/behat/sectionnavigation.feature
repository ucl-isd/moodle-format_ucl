@format @format_ucl  @wip @TODO
Feature: Adding a new section lands on the editing page
  In order to add a new section
  As a user
  I need to edit a new section when one is added

  Scenario: Add and edit section
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | startdate     |
      | Course 1 | C1        | ucl    | 0             | 5           | ##yesterday## |
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Add new section" "link"
    Then I should see "Edit section settings"
    And I set the field "Section name" to "Welcome to Stamptown"
    And I press "Save changes"
    And "Welcome to Stamptown" "link" should appear after "New section 5" "link"
