<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for local_enhancedroleswitch plugin.
 *
 * @package    local_enhancedroleswitch
 * @category   test
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_enhancedroleswitch;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/group/lib.php');

/**
 * Test cases for enhanced role switch functionality.
 *
 * @package    local_enhancedroleswitch
 * @category   test
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class enhanced_roleswitch_test extends \advanced_testcase {

    /**
     * Setup before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test temporary group membership during role switch.
     *
     * @covers \local_enhancedroleswitch\hook_listener\role_switch::after_role_switched
     */
    public function test_temporary_group_membership(): void {
        global $DB, $SESSION, $USER;

        // Create test data.
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Test Group']);

        // Enroll teacher.
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        // Set current user as teacher.
        $this->setUser($teacher);

        $context = \context_course::instance($course->id);

        // Get student role.
        $studentroles = get_archetype_roles('student');
        $studentrole = reset($studentroles);

        // Verify teacher is not in group initially.
        $this->assertFalse(groups_is_member($group->id, $teacher->id));

        // Simulate switching to student role with group.
        $SESSION->enhancedroleswitch_groupid = $group->id;
        role_switch($studentrole->id, $context);

        // Verify teacher was added to group.
        $this->assertTrue(groups_is_member($group->id, $teacher->id));

        // Verify temporary membership was recorded.
        $tempmembership = $DB->get_record('local_enhancedroleswitch_temp', [
            'userid' => $teacher->id,
            'groupid' => $group->id,
            'courseid' => $course->id
        ]);
        $this->assertNotFalse($tempmembership);

        // Switch back to normal role.
        role_switch(0, $context);

        // Verify teacher was removed from group.
        $this->assertFalse(groups_is_member($group->id, $teacher->id));

        // Verify temporary membership record was deleted.
        $tempmembership = $DB->get_record('local_enhancedroleswitch_temp', [
            'userid' => $teacher->id,
            'groupid' => $group->id
        ]);
        $this->assertFalse($tempmembership);
    }

    /**
     * Test that permanent group memberships are not removed.
     *
     * @covers \local_enhancedroleswitch\hook_listener\role_switch::after_role_switched
     */
    public function test_permanent_group_membership_preserved(): void {
        global $DB, $SESSION;

        // Create test data.
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Permanent Group']);

        // Enroll teacher.
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        // Add teacher to group permanently.
        groups_add_member($group->id, $teacher->id);

        // Set current user as teacher.
        $this->setUser($teacher);

        $context = \context_course::instance($course->id);

        // Get student role.
        $studentroles = get_archetype_roles('student');
        $studentrole = reset($studentroles);

        // Verify teacher is in group.
        $this->assertTrue(groups_is_member($group->id, $teacher->id));

        // Simulate switching to student role with same group.
        $SESSION->enhancedroleswitch_groupid = $group->id;
        role_switch($studentrole->id, $context);

        // Verify teacher is still in group.
        $this->assertTrue(groups_is_member($group->id, $teacher->id));

        // Verify NO temporary membership was recorded (because already a member).
        $tempmembership = $DB->get_record('local_enhancedroleswitch_temp', [
            'userid' => $teacher->id,
            'groupid' => $group->id
        ]);
        $this->assertFalse($tempmembership);

        // Switch back to normal role.
        role_switch(0, $context);

        // Verify teacher is STILL in group (permanent membership preserved).
        $this->assertTrue(groups_is_member($group->id, $teacher->id));
    }

    /**
     * Test getting course groups function.
     *
     * @covers ::local_enhancedroleswitch_get_course_groups
     */
    public function test_get_course_groups(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/enhancedroleswitch/lib.php');

        // Create test data.
        $course = $this->getDataGenerator()->create_course();
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Group 1']);
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Group 2']);

        // Get groups.
        $groups = local_enhancedroleswitch_get_course_groups($course->id);

        // Verify we got both groups.
        $this->assertCount(2, $groups);
        $this->assertArrayHasKey($group1->id, $groups);
        $this->assertArrayHasKey($group2->id, $groups);
    }
}
