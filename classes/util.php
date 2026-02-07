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

/**
 * Class util
 *
 * @package    local_enhancedswitchrole
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util {
    /**
     * Remove all temporary group memberships for a user in a course.
     *
     * Removes the user from any groups they were temporarily added to
     * and deletes the corresponding temporary membership records.
     *
     * @param int $courseid The ID of the course.
     * @param int $userid The ID of the user.
     * @return void
     */
    public static function remove_temporary_memberships($courseid, $userid) {
        global $DB;

        $tempmemberships = $DB->get_records('local_enhancedswitchrole_temp', [
            'userid' => $userid,
            'courseid' => $courseid,
        ]);

        foreach ($tempmemberships as $membership) {
            // Remove from group.
            groups_remove_member($membership->groupid, $userid);
            // Delete the temporary membership record.
            $DB->delete_records('local_enhancedswitchrole_temp', ['id' => $membership->id]);
        }
    }

    /**
     * Add a temporary group membership for a user in a course.
     *
     * Adds the user to the specified group if they are not already a member,
     * and records the membership as temporary so it can be cleaned up later.
     *
     * @param int $courseid The ID of the course.
     * @param int $userid The ID of the user.
     * @param int $groupid The ID of the group to add the user to.
     * @return void
     */
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

    /**
     * Render the enhanced switch role UI with available roles and groups.
     *
     * Outputs the roles template with role buttons and group dropdowns
     * for student roles when groups exist in the course.
     *
     * @param int $id The course ID.
     * @param array $roles An associative array of role ID => role name pairs.
     * @param string $returnurl The URL to return to after switching role.
     * @return void
     */
    public static function render_roles($id, $roles, $returnurl): void {
        global $OUTPUT;

        // Template data array.
        $data = [
            'url' => new moodle_url('/local/enhancedswitchrole/switchrole.php'),
            'cohort_groups' => [],
            'groups' => [],

        ];

        // Get course groups.
        $coursegroups = groups_get_all_groups($id);
        $cohortgroupids = self::get_cohort_groups($id);

        // Build groups array with URLs.
        foreach ($coursegroups as $group) {
            $key = array_search($group->id, array_column($cohortgroupids, 'groupid'));
            if ($key !== false) {
                $data['cohort_groups'][] = [
                    'groupname' => format_string($group->name),
                    'groupid' => $group->id,
                    'course' => $cohortgroupids[$group->id]->course,
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
                'sesskey' => sesskey(),
            ];

            // Show group dropdown for student roles if groups exist.
            if ($key > 0 && in_array($key, $studentroleids)) {
                $rolebutton += ['hasgroups' => true];
            }

            $data['roles'][] = $rolebutton;
        }

        echo $OUTPUT->render_from_template('local_enhancedswitchrole/roles', $data);
    }

    /**
     * Switch the current user's role in a course context.
     *
     * When switching to a role (roleid > 0), optionally adds the user to a
     * temporary group. When switching back (roleid == 0), removes any
     * temporary group memberships.
     *
     * @param int $roleid The role ID to switch to, or 0 to switch back.
     * @param \context $context The course context.
     * @return void
     */
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

    /**
     * Render the group selection UI for switching temporary group membership.
     *
     * Outputs the groups template with cohort groups and course groups
     * as selectable buttons.
     *
     * @param int $id The course ID.
     * @param string $returnurl The URL to return to after switching group.
     * @return void
     */
    public static function render_groups($id, $returnurl): void {
        global $DB, $OUTPUT, $USER;

        // Find the current temporary group membership.
        $currentgroupid = 0;
        $tempmembership = $DB->get_record('local_enhancedswitchrole_temp', [
            'userid' => $USER->id,
            'courseid' => $id,
        ]);
        if ($tempmembership) {
            $currentgroupid = (int) $tempmembership->groupid;
        }

        $data = [
            'url' => new moodle_url('/local/enhancedswitchrole/switchgroup.php'),
            'id' => $id,
            'returnurl' => $returnurl,
            'sesskey' => sesskey(),
            'cohort_groups' => [],
            'groups' => [],
            'noneiscurrent' => ($currentgroupid === 0),
        ];

        $coursegroups = groups_get_all_groups($id);
        $cohortgroupids = self::get_cohort_groups($id);

        foreach ($coursegroups as $group) {
            if (isset($cohortgroupids[$group->id])) {
                $data['cohort_groups'][] = [
                    'groupname' => format_string($group->name),
                    'groupid' => $group->id,
                    'course' => $cohortgroupids[$group->id]->course,
                    'iscurrent' => ($group->id == $currentgroupid),
                ];
            } else {
                $data['groups'][] = [
                    'groupname' => format_string($group->name),
                    'groupid' => $group->id,
                    'iscurrent' => ($group->id == $currentgroupid),
                ];
            }
        }

        echo $OUTPUT->render_from_template('local_enhancedswitchrole/groups', $data);
    }

    /**
     * Get the group and course associated with meta enrolment cohorts for a course.
     *
     * Returns an array of records keyed by group ID, each containing the
     * group ID and the short name of the linked course.
     *
     * @param int $courseid The ID of the course.
     * @return array An array of objects keyed by group ID with groupid and course properties.
     */
    public static function get_cohort_groups($courseid) {
        global $DB;

        $ids = $DB->get_records_sql(
            'SELECT
            e.customint2 as groupid,
            c.shortname as course
            FROM {enrol} e
            JOIN {course} c ON e.customint1 = c.id
            WHERE enrol= ? and courseid = ? AND customint2 != 0',
            ['meta', $courseid]
        );

        return $ids;
    }
}
