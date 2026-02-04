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

namespace local_enhancedswitchrole;

use moodle_url;

require_once($CFG->dirroot . '/group/lib.php');

/**
 * Class util
 *
 * @package    local_enhancedswitchrole
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util {
    /**
     * Get groups available in a course for enhanced role switching.
     *
     * @param int $courseid Course ID
     * @return array Array of groups
     */
    public static function get_course_groups($courseid) {
        return groups_get_all_groups($courseid);
    }

    public static function remove_temporary_memberships($courseid, $userid) {
        global $DB;

        $tempmemberships = $DB->get_records('local_enhancedswitchrole_temp', [
            'userid' => $userid,
            'courseid' => $courseid
        ]);

        foreach ($tempmemberships as $membership) {
            // Remove from group.
            groups_remove_member($membership->groupid, $userid);
            // Delete the temporary membership record.
            $DB->delete_records('local_enhancedswitchrole_temp', ['id' => $membership->id]);
        }
    }

    public static function add_temporary_membership($courseid, $userid, $groupid) {
        global $DB;

        if ($groupid > 0) {
            // Verify the group exists and belongs to this course.
            $group = $DB->get_record('groups', ['id' => $groupid, 'courseid' => $courseid]);
            if ($group) {
                // Check if user is already a member (don't want to remove a real membership later).
                $ismember = groups_is_member($groupid, $userid);
                
                if (!$ismember) {
                    // Add user to the group temporarily.
                    groups_add_member($groupid, $userid);

                    // Record this as a temporary membership.
                    $record = new \stdClass();
                    $record->userid = $userid;
                    $record->groupid = $groupid;
                    $record->courseid = $courseid;
                    $record->timecreated = time();
                    $DB->insert_record('local_enhancedswitchrole_temp', $record);
                }
            }
        }
    }

    public static function render_role_with_group_dropdown($rolebutton, $courseid, $role, $roleid, $returnurl):void {
        // Get course groups.
        $coursegroups = util::get_course_groups($courseid);

        $dropdownid = 'groupDropdown' . clean_param($roleid, PARAM_INT);
        echo '<div class="mx-3 mb-1" style="display: flex; gap: 0.25em;">';
        echo $rolebutton;
        echo '<div>';
        echo '<div class="dropdown">';
        echo '<button class="btn btn-secondary dropdown-toggle" type="button" id="' . s($dropdownid) . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        echo get_string('studentingroup', 'local_enhancedswitchrole', $role);
        echo '</button>';
        echo '<div class="dropdown-menu" aria-labelledby="' . s($dropdownid) . '">';
        foreach ($coursegroups as $group) {
            $groupurl = new moodle_url('/local/enhancedswitchrole/switchrole.php', [
                'id' => $courseid,
                'switchrole' => $roleid,
                'groupid' => $group->id,
                'returnurl' => $returnurl,
                'sesskey' => sesskey()
            ]);
            echo '<a class="dropdown-item" href="' . $groupurl->out(false) . '"><i class="fa-solid fa-user-group"></i> ' . format_string($group->name) . '</a>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}
