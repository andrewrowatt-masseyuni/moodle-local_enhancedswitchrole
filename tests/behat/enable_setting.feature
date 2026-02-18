@local @local_enhancedswitchrole
Feature: Enable or disable enhanced switch role
  In order to control the enhanced switch role feature
  As an admin
  I need to be able to enable or disable it via admin settings

  Background:
    Given the following "courses" exist:
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

  @javascript
  Scenario: Disabling the setting hides the enhanced switch role user menu items
    Given I log in as "admin"
    And I navigate to "Plugins > Local plugins > Enhanced Switch Role" in site administration
    And I set the field "Enable enhanced switch role" to ""
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I click on "#user-menu-toggle" "css_element"
    Then I should not see "Switch role to student in group..."
