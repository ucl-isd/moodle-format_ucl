@format @format_ucl @javascript
Feature: Initial section has custom layout
  In order to to quickly find important course information
  As a user
  I need to see a consistent layout in the initial section

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1        | ucl    | 0             | 5           |
    And the following "users" exist:
      | username | firstname | lastname | email                | maildisplay |
      | teacher1 | Teacher   | 1        | teacher1@example.com | 1           |
      | teacher2 | Teacher   | 2        | teacher2@example.com | 1           |
      | teacher3 | Teacher   | 3        | teacher3@example.com | 1           |
      | teacher4 | Teacher   | 4        | teacher4@example.com | 1           |
      | teacher5 | Teacher   | 5        | teacher5@example.com | 0           |
      | student1 | Student   | 1        | student1@example.com | 1           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C1     | teacher        |
      | teacher2 | C1     | editingteacher |
      | teacher3 | C1     | teacher        |
      | teacher4 | C1     | teacher        |
      | teacher5 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following config values are set as admin:
      | displaycontacts | 1 | format_ucl |

  Scenario: Initial section summary appears above main section content
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Edit" "link"
    And I set the field "Description" to "Welcome to Stamptown"
    And I press "Save changes"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And "Welcome to Stamptown" "text" should exist in the ".behat-ucl-section-description" "css_element"
    And "Welcome to Stamptown" "text" should not exist in the ".section-item .content" "css_element"

  Scenario: Course contacts appear in initial section when editing is on
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    Then "Contacts" "text" should exist
    And "Teacher 1" "link" should exist
    And "Teacher 2" "link" should appear after "Teacher 1" "link"
    And "Teacher 5" "link" should appear after "Teacher 2" "link"
    And "Teacher 3" "link" should not exist
    And "Teacher 4" "link" should not exist

  Scenario: Course contacts do not appear in initial section when editing is off
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Contacts" "text" should not exist in the "#ucl-course-content" "css_element"
    And "Teacher 1" "link" should not exist in the "#ucl-course-content" "css_element"
    And "Teacher 2" "link" should not exist
    And "Teacher 5" "link" should not exist
    And "Teacher 3" "link" should not exist
    And "Teacher 4" "link" should not exist

  Scenario: Course contacts appear in initial section when editing is off and contact is checked
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Show Teacher 1 to students" "checkbox"
    And I click on "Show Teacher 5 to students" "checkbox"
    And I turn editing mode off
    Then "Teacher 1" "link" should exist in the "#ucl-course-content" "css_element"
    And "teacher1@example.com" "link" should exist in the "#ucl-course-content" "css_element"
    And "Teacher 2" "link" should not exist
    And "Teacher 5" "link" should exist
    And "teacher5@example.com" "link" should not exist
    And "Teacher 3" "link" should not exist
    And "Teacher 4" "link" should not exist
    And I reload the page
    And "Teacher 1" "link" should exist

  Scenario: User can show/hide contacts
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    Then "Show Teacher 1 to students" "checkbox" should exist

  Scenario: User with 'moodle/user:editownprofile' can edit own profile description
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    Then "Add description for contact Teacher 1" "link" should exist
    And "Add description for contact Teacher 2" "link" should not exist
    And "Add description for contact Teacher 5" "link" should not exist

  Scenario: User with 'moodle/user:editprofile' can edit other contacts profile description
    Given the following "roles" exist:
      | name | shortname   | description | archetype |
      | Head | headteacher | headteacher |           |
    And the following "role assigns" exist:
      | user     | role        | contextlevel | reference |
      | teacher1 | headteacher | User         | teacher5  |
    And the following "permission overrides" exist:
      | capability              | permission | role        | contextlevel | reference |
      | moodle/user:editprofile | Allow      | headteacher | User         | teacher5  |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    Then "Add description for contact Teacher 1" "link" should exist
    And "Add description for contact Teacher 2" "link" should not exist
    And "Add description for contact Teacher 5" "link" should exist

  Scenario: User can add custom contacts
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Add custom contact" "link"
    And I set the following fields to these values:
      | Role        | Ring Master         |
      | Name        | Jack Tucker         |
      | Email       | zzucker@example.com |
      | Description | Clown king          |
    And I press "Save"
    Then I should see "Changes saved"
    And I switch editing mode off
    And "Course contacts" "text" should exist
    And I should see "Ring Master"
    And I should see "Jack Tucker"
    And I should see "zzucker@example.com"
    And I should see "Clown king"

  Scenario: User can edit custom contacts
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Add custom contact" "link"
    And I set the following fields to these values:
      | Role        | Ring Master         |
      | Name        | Jack Tucker         |
      | Email       | zzucker@example.com |
      | Description | Clown king          |
    And I press "Save"
    And I click on "Edit custom contact Jack Tucker" "link"
    And I set the following fields to these values:
      | Role  | Director             |
      | Name  | Jonny Woolley        |
      | Email | jwoolley@example.com |
    And I press "Save"
    And I switch editing mode off
    Then I should not see "Ring Master"
    And I should see "Director"
    And I should see "Jonny Woolley"
    And I should see "jwoolley@example.com"
    And I should see "Clown king"

  Scenario: User can delete custom contacts
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Add custom contact" "link"
    And I set the following fields to these values:
      | Role        | Ring Master         |
      | Name        | Jack Tucker         |
      | Email       | zzucker@example.com |
      | Description | Clown king          |
    And I press "Save"
    And I click on "Edit custom contact Jack Tucker" "link"
    And I press "Delete"
    And I click on "Delete" "button" in the "Custom contact" "dialogue"
    And I should not see "Ring Master"
    And I should not see "Jack Tucker"
    And I should not see "zzucker@example.com"
    And I should not see "Clown king"
    And I switch editing mode off
    And "#format-ucl-contacts-widget" "css_element" should not exist
