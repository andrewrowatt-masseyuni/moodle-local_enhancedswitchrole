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

namespace local_enhancedswitchrole\hook_listener;

/**
 * Hook listener for extending the user menu with group switching.
 *
 * @package    local_enhancedswitchrole
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_menu {
    /**
     * Add a "Switch group in student role" link to the user menu.
     *
     * Shown when the user has switchable roles and the course has groups.
     *
     * @param \core_user\hook\extend_user_menu $hook The user menu hook.
     */
    public static function extend_user_menu(\core_user\hook\extend_user_menu $hook): void {
        global $DB, $PAGE, $USER;

        $course = $PAGE->course;
        if (!$course || $course->id == SITEID) {
            return;
        }

        // When role-switched, has_capability checks against the switched role
        // (e.g. student) which won't have switchroles. But being role-switched
        // proves the user originally had the capability.
        $context = \context_course::instance($course->id);
        if (!is_role_switched($course->id) && !has_capability('moodle/role:switchroles', $context)) {
            return;
        }

        $groups = groups_get_all_groups($course->id);
        if (empty($groups)) {
            return;
        }

        $returnurl = $PAGE->url->out_as_local_url(false);

        // Find the current temporary group membership.
        $currentgroupid = 0;
        $tempmembership = $DB->get_record('local_enhancedswitchrole_temp', [
            'userid' => $USER->id,
            'courseid' => $course->id,
        ]);
        if ($tempmembership) {
            $currentgroupid = (int) $tempmembership->groupid;
        }

        // Add a divider before our items.
        // Note: titleidentifier is required to prevent Snap theme's array_column()
        // from re-indexing and splicing the wrong menu item.
        $divider = new \stdClass();
        $divider->itemtype = 'divider';
        $divider->titleidentifier = 'divider,local_enhancedswitchrole';
        $hook->add_navitem($divider);

        // Add direct-action items for each cohort group.
        $cohortgroups = \local_enhancedswitchrole\util::get_cohort_groups($course->id);
        foreach ($cohortgroups as $cohortgroup) {
            if (!isset($groups[$cohortgroup->groupid])) {
                continue;
            }
            $group = $groups[$cohortgroup->groupid];

            $title = get_string('switchtostudentingroup', 'local_enhancedswitchrole', $group->name);
            if ($group->id == $currentgroupid) {
                $title .= ' ✓';
            }

            $item = new \stdClass();
            $item->itemtype = 'link';
            $item->url = new \moodle_url('/local/enhancedswitchrole/switchgroup.php', [
                'id' => $course->id,
                'groupid' => $group->id,
                'sesskey' => sesskey(),
                'returnurl' => $returnurl,
            ]);
            $item->title = $title;
            $item->titleidentifier = 'cohortgroup_' . $group->id . ',local_enhancedswitchrole';
            $item->pix = 'i/group';

            $hook->add_navitem($item);
        }

        // Add link to full group selection page (includes course groups and "None" option).
        $item = new \stdClass();
        $item->itemtype = 'link';
        $item->url = new \moodle_url('/local/enhancedswitchrole/switchgroup.php', [
            'id' => $course->id,
            'returnurl' => $returnurl,
        ]);
        $item->title = get_string('switchgroupinstudent', 'local_enhancedswitchrole');
        $item->titleidentifier = 'switchgroupinstudent,local_enhancedswitchrole';
        $item->pix = 'i/group';

        $hook->add_navitem($item);
    }
}
