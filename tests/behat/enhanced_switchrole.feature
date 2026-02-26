@local @local_enhancedswitchrole
Feature: Enhanced role switching with group restrictions
  In order to see what students in specific groups see
  As a teacher
  I need to be able to switch role to student in a specific group

  Background:
    Given I log in as "admin"
    And the following "categories" exist:
      | fullname          | shortname          | idnumber  |
      | category1         | category1          | category1 |

    And the following "courses" exist:
      | fullname          | shortname          | category  |
      | course1           | C1                 | category1 |
      | course1_distance  | C1_distance        | 0         |

    And the following "groups" exist:
      | name   | course | idnumber |
      | group1 | C1     | G1       |
      | group2 | C1     | G2       |

    And I navigate to "Plugins > Enrolments > Manage enrol plugins" in site administration
    And I click on "Enable" "link" in the "Course meta link" "table_row"
    And I add "Course meta link" enrolment method in "course1" with:
      | Link course  | course1_distance      |
      | Add to group | group1                |

    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | managersystem   | Manager   | System      | manager1@example.com |
      | managercategory | Manager   | Category    | manager2@example.com |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

    And the following "role assigns" exist:
      | user            | role    | contextlevel | reference |
      | managersystem   | manager | System       |           |
      | managercategory | manager | Category     | category1 |

    And the following "activities" exist:
      | activity | name                      | intro                    | course | idnumber | section |
      | label    | visible to group 1 only   | visible to group1 only   | C1     | label1   | 1       |
      | label    | visible to group 2 only   | visible to group2 only   | C1     | label2   | 1       |

    And I change the window size to "large"

    And I navigate to "Plugins > Local plugins > Enhanced Switch Role" in site administration
    And I set the field "Enable enhanced switch role" to "1"
    And I press "Save changes"

    # Setup the course
    Given I log in as "teacher1"
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

  @javascript
  Scenario: Teacher can switch role to student in specific group and see group-restricted content
    Given I log in as "teacher1"

    And I am on "course1" course homepage
    # Verify both labels are visible to teacher
    Then I should see "visible to group1 only"
    And I should see "visible to group2 only"

    # Switch role to student in group1 via the roles page dropdown
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to..." "link"
    And I click on "Student in specific group..." "button"
    And I should see "Cohort groups"
    And I should see "Course groups"
    And I should see "(C1_distance)"

    # Indirectly confirming the the course meta link group is under Cohort groups
    And I should not see "None"

    And I click on "group1 (C1_distance)" "link"

    # Verify role switch worked - should see Student role and group1 content only
    Then I should see "Student"
    And I should see "in group 'group1'" in the ".usermenu .meta.role" "css_element"
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

    # Test user menu direct group switching
    And I am on "course1" course homepage

    # Verify user menu shows cohort group direct-action item and full selection link
    When I click on "#user-menu-toggle" "css_element"
    Then I should see "Switch role to student in group 'group1'"
    And I should see "Switch role to student in group..."

    # Click directly on cohort group to switch role + group in one step
    When I click on "Switch role to student in group 'group1'" "link"

    # Verify role switch worked - should see Student role and group1 content only
    Then I should see "Student"
    And I should see "in group 'group1'" in the ".usermenu .meta.role" "css_element"
    And I should see "visible to group1 only"
    And I should not see "visible to group2 only"

    # Verify checkmark on current group in user menu
    When I click on "#user-menu-toggle" "css_element"
    Then I should see "✓"

    # Switch to group2 via full group selection page form
    When I click on "Switch role to student in group..." "link"
    And I set the field "Group" to "group2"
    And I press "Switch role"

    # Verify group2 content is now visible
    Then I should see "in group 'group2'" in the ".usermenu .meta.role" "css_element"
    And I should see "visible to group2 only"
    And I should not see "visible to group1 only"

    # Switch back to group1 directly via user menu
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to student in group 'group1'" "link"
    Then I should see "group1" in the ".usermenu .meta.role" "css_element"
    And I should see "visible to group1 only"
    And I should not see "visible to group2 only"

    # Return to normal role
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Return to my normal role" "link"

    # Verify both labels are visible again and teacher is not in any group
    Then I should see "visible to group1 only"
    And I should see "visible to group2 only"
    And I click on "Participants" "link"
    And I should not see "group1"
    And I should not see "group2"

  @javascript
  Scenario: Admin can switch role to student in specific group and see group-restricted content
    Given I log in as "admin"

    And I am on "course1" course homepage
    # Verify both labels are visible to teacher
    Then I should see "visible to group1 only"
    And I should see "visible to group2 only"

    # Switch role to student in group1 via the roles page dropdown
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to..." "link"
    And I click on "Student in specific group..." "button"
    And I should see "Cohort groups"
    And I should see "Course groups"
    And I should see "(C1_distance)"

    # Indirectly confirming the the course meta link group is under Cohort groups
    And I should not see "None"

    And I click on "group1 (C1_distance)" "link"

    # Verify role switch worked - should see Student role and group1 content only
    Then I should see "Student"
    And I should see "in group 'group1'" in the ".usermenu .meta.role" "css_element"
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

    # Test user menu direct group switching
    And I am on "course1" course homepage

    # Verify user menu shows cohort group direct-action item and full selection link
    When I click on "#user-menu-toggle" "css_element"
    Then I should see "Switch role to student in group 'group1'"
    And I should see "Switch role to student in group..."

    # Click directly on cohort group to switch role + group in one step
    When I click on "Switch role to student in group 'group1'" "link"

    # Verify role switch worked - should see Student role and group1 content only
    Then I should see "Student"
    And I should see "in group 'group1'" in the ".usermenu .meta.role" "css_element"
    And I should see "visible to group1 only"
    And I should not see "visible to group2 only"

    # Verify checkmark on current group in user menu
    When I click on "#user-menu-toggle" "css_element"
    Then I should see "✓"

    # Switch to group2 via full group selection page form
    When I click on "Switch role to student in group..." "link"
    And I set the field "Group" to "group2"
    And I press "Switch role"

    # Verify group2 content is now visible
    Then I should see "in group 'group2'" in the ".usermenu .meta.role" "css_element"
    And I should see "visible to group2 only"
    And I should not see "visible to group1 only"

    # Switch back to group1 directly via user menu
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to student in group 'group1'" "link"
    Then I should see "group1" in the ".usermenu .meta.role" "css_element"
    And I should see "visible to group1 only"
    And I should not see "visible to group2 only"

    # Return to normal role
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Return to my normal role" "link"

    # Verify both labels are visible again and teacher is not in any group
    Then I should see "visible to group1 only"
    And I should see "visible to group2 only"
    And I click on "Participants" "link"
    And I should not see "group1"
    And I should not see "group2"

  @javascript
  Scenario: A system-wide Manager can switch role to student in specific group and see group-restricted content
    Given I log in as "managersystem"

    And I am on "course1" course homepage
    # Verify both labels are visible to teacher
    Then I should see "visible to group1 only"
    And I should see "visible to group2 only"

    # Switch role to student in group1 via the roles page dropdown
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to..." "link"
    And I click on "Student in specific group..." "button"
    And I should see "Cohort groups"
    And I should see "Course groups"
    And I should see "(C1_distance)"

    # Indirectly confirming the the course meta link group is under Cohort groups
    And I should not see "None"

    And I click on "group1 (C1_distance)" "link"

    # Verify role switch worked - should see Student role and group1 content only
    Then I should see "Student"
    And I should see "in group 'group1'" in the ".usermenu .meta.role" "css_element"
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

    # Test user menu direct group switching
    And I am on "course1" course homepage

    # Verify user menu shows cohort group direct-action item and full selection link
    When I click on "#user-menu-toggle" "css_element"
    Then I should see "Switch role to student in group 'group1'"
    And I should see "Switch role to student in group..."

    # Click directly on cohort group to switch role + group in one step
    When I click on "Switch role to student in group 'group1'" "link"

    # Verify role switch worked - should see Student role and group1 content only
    Then I should see "Student"
    And I should see "in group 'group1'" in the ".usermenu .meta.role" "css_element"
    And I should see "visible to group1 only"
    And I should not see "visible to group2 only"

    # Verify checkmark on current group in user menu
    When I click on "#user-menu-toggle" "css_element"
    Then I should see "✓"

    # Switch to group2 via full group selection page form
    When I click on "Switch role to student in group..." "link"
    And I set the field "Group" to "group2"
    And I press "Switch role"

    # Verify group2 content is now visible
    Then I should see "in group 'group2'" in the ".usermenu .meta.role" "css_element"
    And I should see "visible to group2 only"
    And I should not see "visible to group1 only"

    # Switch back to group1 directly via user menu
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to student in group 'group1'" "link"
    Then I should see "group1" in the ".usermenu .meta.role" "css_element"
    And I should see "visible to group1 only"
    And I should not see "visible to group2 only"

    # Return to normal role
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Return to my normal role" "link"

    # Verify both labels are visible again and teacher is not in any group
    Then I should see "visible to group1 only"
    And I should see "visible to group2 only"
    And I click on "Participants" "link"
    And I should not see "group1"
    And I should not see "group2"

  @javascript
  Scenario: A category-level Manager can switch role to student in specific group and see group-restricted content
    Given I log in as "managercategory"

    And I am on "course1" course homepage
    # Verify both labels are visible to teacher
    Then I should see "visible to group1 only"
    And I should see "visible to group2 only"

    # Switch role to student in group1 via the roles page dropdown
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to..." "link"
    And I click on "Student in specific group..." "button"
    And I should see "Cohort groups"
    And I should see "Course groups"
    And I should see "(C1_distance)"

    # Indirectly confirming the the course meta link group is under Cohort groups
    And I should not see "None"

    And I click on "group1 (C1_distance)" "link"

    # Verify role switch worked - should see Student role and group1 content only
    Then I should see "Student"
    And I should see "in group 'group1'" in the ".usermenu .meta.role" "css_element"
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

    # Test user menu direct group switching
    And I am on "course1" course homepage

    # Verify user menu shows cohort group direct-action item and full selection link
    When I click on "#user-menu-toggle" "css_element"
    Then I should see "Switch role to student in group 'group1'"
    And I should see "Switch role to student in group..."

    # Click directly on cohort group to switch role + group in one step
    When I click on "Switch role to student in group 'group1'" "link"

    # Verify role switch worked - should see Student role and group1 content only
    Then I should see "Student"
    And I should see "in group 'group1'" in the ".usermenu .meta.role" "css_element"
    And I should see "visible to group1 only"
    And I should not see "visible to group2 only"

    # Verify checkmark on current group in user menu
    When I click on "#user-menu-toggle" "css_element"
    Then I should see "✓"

    # Switch to group2 via full group selection page form
    When I click on "Switch role to student in group..." "link"
    And I set the field "Group" to "group2"
    And I press "Switch role"

    # Verify group2 content is now visible
    Then I should see "in group 'group2'" in the ".usermenu .meta.role" "css_element"
    And I should see "visible to group2 only"
    And I should not see "visible to group1 only"

    # Switch back to group1 directly via user menu
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Switch role to student in group 'group1'" "link"
    Then I should see "group1" in the ".usermenu .meta.role" "css_element"
    And I should see "visible to group1 only"
    And I should not see "visible to group2 only"

    # Return to normal role
    When I click on "#user-menu-toggle" "css_element"
    And I click on "Return to my normal role" "link"

    # Verify both labels are visible again and teacher is not in any group
    Then I should see "visible to group1 only"
    And I should see "visible to group2 only"
    And I click on "Participants" "link"
    And I should not see "group1"
    And I should not see "group2"
