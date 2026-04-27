@format @format_ucl
Feature: A prompt appears if a section has no description
  In order to add a section description
  As a user
  I need to see a prompt when there is no current description

  Scenario: Add section description
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | initsections|startdate     |
      | Course 1 | C1        | ucl    | 0             | 5           | 1           |##yesterday## |
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Section 2" "link" in the "#toc" "css_element"
    Then "Add section description" "link" should exist
    And "summarytext" "css_element" should not exist
    And I follow "Add section description"
    And I set the field "Description" to "Welcome to Stamptown"
    And I press "Save changes"
    And "Add section description" "link" should not exist
    And "Welcome to Stamptown" "text" should exist in the ".summarytext" "css_element"

