@report @report_participationlog @sol @javascript
Feature: View my own participation log
  In order to view my own log data
  As a authenticated user
  I can access my logs

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname    | email                | idnumber | middlename | alternatename | firstnamephonetic | lastnamephonetic |
      | teacher1 | Teacher   | One         | teacher1@example.com | t1       |            | fred          |                   |                  |
      | student1 | Grainne   | Beauchamp   | student1@example.com | s1       | Ann        | Jill          | Gronya            | Beecham          |
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
    And the following config values are set as admin:
      | buffersize | 0 | logstore_standard |

  Scenario: Access the participation log from profile link
    Given I log in as "student1"
    When I follow "Profile" in the user menu
    Then I should see "Participation log"
    And I follow "Participation log"
    And "#id_displaychart" "css_element" should be visible
    And "#id_displaylogs" "css_element" should be visible
    And "#id_userid" "css_element" should not be visible

  Scenario: View logs
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student first submission |
    And I press "Save changes"
    And I visit "/report/participationlog/index.php"
    When I click on "#id_displaylogs" "css_element"
    And I wait until the page is ready
    Then I should see "Assignment: \"Test assignment name\" course_module viewed"
    And I press "Display timeline"
    And I wait until the page is ready
    Then I should see "Show chart data"
    When I follow "Show chart data"
    Then I should see "Participation timeline for Grainne Beauchamp"
