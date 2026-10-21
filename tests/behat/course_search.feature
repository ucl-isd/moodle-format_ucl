@format @format_ucl @javascript
Feature: Search for an activity by name
  In order to find course content quickly
  As a student
  I need to search activity names and open a result

  Background:
    Given the following "course" exists:
      | fullname      | Course Search |
      | shortname     | CS            |
      | format        | ucl           |
      | coursedisplay | 0             |
      | numsections   | 3             |
      | initsections  | 1             |
    And the following "users" exist:
      | username | firstname | lastname | email              |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | CS     | student |
    And the following "activities" exist:
      | activity | name                 | intro                 | course | section | idnumber |
      | page     | Café - Week 3 Quiz   | Searchable activity   | CS     | 2       | search1  |
      | page     | Biology overview     | Another activity      | CS     | 1       | search2  |
      | label    | Café - Week 3 label  | Not searchable        | CS     | 1       | label1   |

  Scenario: Search names and open the matching activity
    When I am on the "CS" "Course" page logged in as "student1"
    And I set the field "Search this course" to "week 3 cafe"
    Then "#ucl-course-search-results [data-search-name='Café - Week 3 Quiz']" "css_element" should be visible
    And "#ucl-course-search-results [data-search-name='Biology overview']" "css_element" should not be visible
    And "#ucl-course-search-results [data-search-name='Café - Week 3 label']" "css_element" should not exist
    When I click on "#ucl-course-search-results [data-search-name='Café - Week 3 Quiz'] a" "css_element"
    Then "Café - Week 3 Quiz" "text" should exist in the ".activityname" "css_element"
    And "[data-region='ucl-course-search']" "css_element" should not exist
