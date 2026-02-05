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

use core\exception\moodle_exception;
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

    public static function render_roles($id, $roles, $returnurl):void {
        global $OUTPUT;

        // Template data array.
        $data = [
            'url' => new moodle_url('/local/enhancedswitchrole/switchrole.php'),
            'cohort_groups' => [],
            'groups' => [],

        ];

        // Get course groups.
        $coursegroups = groups_get_all_groups($id);
        $cohortgroupids = self::get_cohort_group_ids($id);

        // Build groups array with URLs.
        foreach ($coursegroups as $group) {
            if (in_array($group->id, $cohortgroupids)) {
                $data['cohort_groups'][] = [
                    'groupname' => format_string($group->name),
                    'groupid' => $group->id,
                ];
            } else {              
                $data['groups'][] = [
                    'groupname' => format_string($group->name),
                    'groupid' => $group->id,
                ];
            }
        }

        // Get student role IDs for enhanced switching.
        $studentroles = get_archetype_roles('student');
        $studentroleids = array_keys($studentroles);

        foreach ($roles as $key => $role) {
            $rolebutton = [
                'id' => $id,
                'switchrole' => $key,
                'returnurl' => $returnurl,
                'role' => htmlspecialchars_decode($role, ENT_COMPAT),
                'sesskey' => sesskey()
            ];

            // Show group dropdown for student roles if groups exist.
            if ($key > 0 && in_array($key, $studentroleids)) {
                $rolebutton += ['hasgroups' => true];
            }

            $data['roles'][] = $rolebutton;
       }

       echo $OUTPUT->render_from_template('local_enhancedswitchrole/roles', $data);
    }

    public static function role_switch($roleid, $context) {
        global $USER;

        if ($roleid == 0) {
            self::remove_temporary_memberships($context->instanceid, $USER->id);
            role_switch(0, $context);
        } else {
            // Handle temporary group membership if a group is specified.   
            $groupid = optional_param('groupid', 0, PARAM_INT);

            self::add_temporary_membership($context->instanceid, $USER->id, $groupid);
            role_switch($roleid, $context);
        }
    }

    private static function get_cohort_group_ids($courseid) {
        global $DB;

        $ids = $DB->get_field_sql('SELECT customint2 FROM {enrol} WHERE enrol= ? and courseid = ? AND customint2 != 0', ['meta', $courseid]);

        return (array) $ids;
    }

}
