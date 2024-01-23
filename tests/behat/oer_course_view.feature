@local @local_oer
Feature: The OER plugin is linked and reachable from inside a course
  In order to work with the OER plugin
  As a teacher
  I need to click on the link in the course menu to get to the view

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: A teacher can see the OER link in course menu.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then I should see "OER"
    And I log out

  Scenario: When the OER link is clicked, the teacher gets to the OER main page.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "OER" "link"
    Then I am on the "Course 1" "local_oer > main page" page
    And I log out

  Scenario: A student does not see the OER link in the course menu.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "OER"
