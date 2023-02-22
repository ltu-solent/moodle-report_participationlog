@report @report_participationlog @sol @javascript
Feature: View a user's participation log
  In order to view a user's log data
  As a manager
  I can access anyone's logs

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname    | email                | idnumber |
      | teacher1 | Teacher   | One         | teacher1@example.com | t1       |
      | student1 | Grainne   | Beauchamp   | student1@example.com | s1       |
      | manager1 | Manager   | Capogrosso  | manager1@example.com | m1       |
    And the following "course enrolments" exist:
      | user | course | role |
      | admin | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "activity" exists:
      | activity                            | assign                  |
      | course                              | C1                      |
      | idnumber                            | 0001                    |
      | name                                | Test assignment name    |
      | intro                               | Submit your online text |
      | section                             | 1                       |
      | assignsubmission_onlinetext_enabled | 1                       |
      | assignsubmission_file_enabled       | 0                       |
    And I log in as "admin"
    And I set the following system permissions of "Authenticated user" role:
      | capability | permission |
      | report/participationlog:viewownlog | Allow |
    And I set the following system permissions of "Manager" role:
      | capability | permission |
      | report/participationlog:view       | Allow |
    And the following "role assigns" exist:
      | user  | role | contextlevel | reference |
      | manager1 | manager | System |  |
    And the following config values are set as admin:
      | buffersize | 0 | logstore_standard |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student first submission |
    And I press "Save changes"
    And I log out

  Scenario: Access the participation log from profile link
    Given I log in as "manager1"
    When I follow "Profile" in the user menu
    Then I should see "Participation log"
    And I follow "Participation log"
    And "#id_displaychart" "css_element" should be visible
    And "#id_displaylogs" "css_element" should be visible
    And I should see "Select user"

  Scenario: View logs
    Given I log in as "manager1"
    And I visit "/report/participationlog/index.php"
    And I open the autocomplete suggestions list
    And I click on "Grainne Beauchamp - student1@example.com" item in the autocomplete list
    When I click on "#id_displaylogs" "css_element"
    And I wait until the page is ready
    Then I should see "Assignment: \"Test assignment name\" course_module viewed"
    And I press "Display timeline"
    And I wait until the page is ready
    Then I should see "Show chart data"
    When I follow "Show chart data"
    Then I should see "Participation timeline for Grainne Beauchamp"
