@local @local_enhancedswitchrole
Feature: Enhanced role switching with group restrictions
  In order to see what students in specific groups see
  As a teacher
  I need to be able to switch role to student in a specific group

  @javascript
  Scenario: Teacher can switch role to student in specific group and see group-restricted content
    Given the following "courses" exist:
      | fullname | shortname | category |
      | course1  | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "groups" exist:
      | name   | course | idnumber |
      | group1 | C1     | G1       |
      | group2 | C1     | G2       |
    And the following "activities" exist:
      | activity | name                      | intro                    | course | idnumber | section |
      | label    | visible to group 1 only   | visible to group1 only   | C1     | label1   | 1       |
      | label    | visible to group 2 only   | visible to group2 only   | C1     | label2   | 1       |
    And I change the window size to "large"
    And I log in as "admin"
    And I am on "course1" course homepage
    # Add group restriction to label1
    And I am on the "visible to group 1 only" "label activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Group" "button" in the "Add restriction..." "dialogue"
    And I set the field "Group" to "group1"
    And I click on "Displayed if student doesn't meet this condition • Click to hide" "link"
    And I press "Save and return to course"
    # Add group restriction to label2
    And I am on the "visible to group 2 only" "label activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Group" "button" in the "Add restriction..." "dialogue"
    And I set the field "Group" to "group2"
    And I click on "Displayed if student doesn't meet this condition • Click to hide" "link"
    And I press "Save and return to course"
    And I log out
    And I log in as "teacher1"
    And I am on "course1" course homepage
    # Verify both labels are visible to teacher
    Then I should see "visible to group1 only"
    And I should see "visible to group2 only"
    
    # Switch role to student in group1
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to..." "link"
    And I click on "Student in specific group..." "button"
    And I click on "group1" "link"
    
    # Verify role switch worked - should see Student role and group1 content only
    Then I should see "Student"
    And I should see "visible to group1 only"
    And I should not see "visible to group2 only"
    
    # Return to normal role
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Return to my normal role" "link"
    
    # Verify both labels are visible again
    Then I should see "visible to group1 only"
    And I should see "visible to group2 only"

    # Check that teacher1 is not in group1
    Then I click on "Participants" "link"
    And I should not see "group1"
    And I should not see "group2"
