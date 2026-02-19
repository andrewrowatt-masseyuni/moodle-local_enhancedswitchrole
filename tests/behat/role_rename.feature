@local @local_enhancedswitchrole
Feature: Role renaming reflected in switch role pages
  In order to see my customised role names when switching roles
  As a teacher who has renamed the Student role
  I need to see the renamed role on both the switchrole and switchgroup pages

  Background:
    Given I log in as "admin"
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "groups" exist:
      | name   | course | idnumber |
      | Group1 | C1     | G1       |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I navigate to "Plugins > Local plugins > Enhanced Switch Role" in site administration
    And I set the field "Enable enhanced switch role" to "1"
    And I press "Save changes"

  @javascript
  Scenario: Renamed Student role appears on switchrole and switchgroup pages
    # Rename Student role to Learner at the course level.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Participants" in current page administration
    And I click on "Enrolled users" "text"
    And I click on "Role renaming" "text"
    And I set the field "Your word for 'Student'" to "Learner"
    And I press "Save"

    # Verify switchrole page shows renamed role with both alias and original.
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to..." "link"
    Then I should see "Learner (Student)"
    And I should see "Learner (Student) in specific group..."

    # Verify switchgroup page shows renamed role with both alias and original.
    Given I am on "Course 1" course homepage
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to student in group..." "link"
    And the field "switchgroup-roleid" matches value "Learner (Student)"
