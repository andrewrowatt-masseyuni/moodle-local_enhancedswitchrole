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

namespace local_enhancedroleswitch\hook_listener;

/**
 * Hook listener for role switch events.
 *
 * @package    local_enhancedroleswitch
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role_switch {

    /**
     * Handle after role switched event.
     *
     * @param \core\hook\access\after_role_switched $hook
     */
    public static function after_role_switched(\core\hook\access\after_role_switched $hook): void {
        global $DB, $USER, $SESSION, $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        $context = $hook->context;
        $roleid = $hook->roleid;

        // Only handle course contexts.
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $courseid = $context->instanceid;

        // Check if a group was specified in the session.
        if ($roleid > 0 && !empty($SESSION->enhancedroleswitch_groupid)) {
            $groupid = $SESSION->enhancedroleswitch_groupid;
            unset($SESSION->enhancedroleswitch_groupid);

            // Verify the group exists and belongs to this course.
            $group = $DB->get_record('groups', ['id' => $groupid, 'courseid' => $courseid]);
            if (!$group) {
                return;
            }

            // Check if user is already a member (don't want to remove a real membership later).
            $ismember = groups_is_member($groupid, $USER->id);
            
            if (!$ismember) {
                // Add user to the group temporarily.
                groups_add_member($groupid, $USER->id);

                // Record this as a temporary membership.
                $record = new \stdClass();
                $record->userid = $USER->id;
                $record->groupid = $groupid;
                $record->courseid = $courseid;
                $record->timecreated = time();
                $DB->insert_record('local_enhancedroleswitch_temp', $record);
            }
        } else if ($roleid == 0) {
            // Switching back to normal role - remove temporary group memberships.
            $tempmemberships = $DB->get_records('local_enhancedroleswitch_temp', [
                'userid' => $USER->id,
                'courseid' => $courseid
            ]);

            foreach ($tempmemberships as $membership) {
                // Remove from group.
                groups_remove_member($membership->groupid, $USER->id);
                // Delete the temporary membership record.
                $DB->delete_records('local_enhancedroleswitch_temp', ['id' => $membership->id]);
            }
        }
    }
}
