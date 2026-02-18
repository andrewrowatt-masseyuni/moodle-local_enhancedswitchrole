@local @local_enhancedswitchrole
Feature: Student role selection on switchgroup page
  In order to choose which student role to switch to
  As a teacher
  I need to see a role dropdown when multiple student roles exist

  Background:
    Given I log in as "admin"
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "groups" exist:
      | name   | course | idnumber |
      | Group1 | C1     | G1       |
      | Group2 | C1     | G2       |
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
  Scenario: Role dropdown appears only when multiple student roles exist
    # With only one student role, the dropdown should not appear.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to student in group..." "link"
    Then I should not see "Student role"

    # Create a second student-archetype role and allow editing teachers to switch to it.
    Given I log in as "admin"
    And I navigate to "Users > Permissions > Define roles" in site administration
    And I press "Add a new role"
    And I set the field "Use role or archetype" to "Student"
    And I press "Continue"
    And I set the field "Custom full name" to "Student with hidden course access"
    And I set the field "Short name" to "studenthidden"
    And I press "Create this role"
    And I click on "Allow role switches" "link"
    And I click on "Allow users with role Teacher to switch roles to the role Student with hidden course access" "checkbox"
    And I press "Save changes"

    # With two student roles, the dropdown should appear with correct defaults.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to student in group..." "link"
    Then I should see "Student role"
    And the field "Student role" matches value "Student"
    And the "Student role" select box should contain "Student with hidden course access"

    # Switch to group using the default "Student" role.
    When I click on "Group1" "button"
    Then I should see "Student" in the ".usermenu .meta.role" "css_element"

    # Return to normal role and switch using "Student with hidden course access".
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Return to my normal role" "link"
    And I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to student in group..." "link"
    And I set the field "Student role" to "Student with hidden course access"
    And I click on "Group2" "button"
    Then I should see "Student with hidden course access" in the ".usermenu .meta.role" "css_element"
