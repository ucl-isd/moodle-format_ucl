@format @format_ucl  @wip @TODO
Feature: Adding a new section lands on the editing page
  In order to add a new section
  As a user
  I need to edit a new section when one is added

  Scenario: Add and edit section
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | startdate     |
      | Course 1 | C1        | ucl    | 0             | 5           | ##yesterday## |
    When I am on the "C1" "Course" page logged in as "admin"
