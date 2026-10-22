@format @format_ucl @javascript
Feature: A prompt appears if a section has no description
  In order to add a section description
  As a user
  I need to see a prompt when there is no current description

  Scenario: Add and edit a section description and retain it when hiding the section
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | initsections|startdate     |
      | Course 1 | C1        | ucl    | 0             | 5           | 1           |##yesterday## |
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Section 2" "link" in the "#toc" "css_element"
    Then TinyMCE should be preloaded
    Then "Add section description" "link" should exist
    And "summarytext" "css_element" should not exist
    And I click on "Add section description" "link"
    And I set the field "Description" to "Welcome to Stamptown"
    And I click on "Save" "button" in the "[data-region='summary-form']" "css_element"
    And "Edit section description" "link" should exist
    And "Welcome to Stamptown" "text" should exist in the ".summarytext" "css_element"
    And I click on "Edit section description" "link"
    And I set the field "Description" to "Discarded description"
    And I click on "Cancel" "button" in the "[data-region='summary-form']" "css_element"
    And "Welcome to Stamptown" "text" should exist in the ".summarytext" "css_element"
    And "Discarded description" "text" should not exist
    And I open ucl section "Section 2" edit menu
    And I click on "Hide" "link" in the ".section-actions" "css_element"
    And "Edit section description" "link" should exist
    And "Welcome to Stamptown" "text" should exist in the ".summarytext" "css_element"
    And I open ucl section "Section 2" edit menu
    And I click on "Show" "link" in the ".section-actions" "css_element"
    And "Edit section description" "link" should exist
    And "Welcome to Stamptown" "text" should exist in the ".summarytext" "css_element"
