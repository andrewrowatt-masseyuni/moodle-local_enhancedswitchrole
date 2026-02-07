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
 * Switch the temporary group membership, initiating a student role switch if needed.
 *
 * When accessed without a groupid parameter, displays a group selection UI.
 * When accessed with a groupid parameter, switches to a student role (if not
 * already switched), sets the temporary group membership, and redirects back.
 *
 * @package    local_enhancedswitchrole
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/group/lib.php');

$id        = required_param('id', PARAM_INT);
$groupid   = optional_param('groupid', -1, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

if ($groupid >= 0) {
    require_sesskey();
}

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_login($course);

$context = context_course::instance($course->id);

// When role-switched, capabilities are evaluated against the switched role,
// so require_capability would fail. Being role-switched proves the user
// originally had the capability.
if (!is_role_switched($course->id)) {
    require_capability('moodle/role:switchroles', $context);
}

if ($groupid >= 0) {
    // Action mode: switch the group (and role if needed).

    if (!is_role_switched($course->id)) {
        // Not yet switched — initiate a student role switch.
        $studentroles = get_archetype_roles('student');
        $switchableroles = get_switchable_roles($context);

        // Find the first student role that is switchable.
        $studentroleid = 0;
        foreach ($studentroles as $role) {
            if (isset($switchableroles[$role->id])) {
                $studentroleid = $role->id;
                break;
            }
        }

        if ($studentroleid) {
            role_switch($studentroleid, $context);
        } else {
            redirect(new moodle_url($returnurl));
        }
    } else {
        // Already switched — remove existing temporary memberships.
        \local_enhancedswitchrole\util::remove_temporary_memberships($course->id, $USER->id);
    }

    if ($groupid > 0) {
        \local_enhancedswitchrole\util::add_temporary_membership($course->id, $USER->id, $groupid);
    }

    redirect(new moodle_url($returnurl));
} else {
    // Display mode: show group selection UI.
    $PAGE->set_url('/local/enhancedswitchrole/switchgroup.php', ['id' => $id]);
    $PAGE->set_title(get_string('switchgroupinstudent', 'local_enhancedswitchrole'));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_pagelayout('incourse');

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('switchgroupinstudent', 'local_enhancedswitchrole'));

    \local_enhancedswitchrole\util::render_groups($id, $returnurl);

    $url = new moodle_url($returnurl);
    echo $OUTPUT->container($OUTPUT->action_link($url, get_string('cancel')), 'mx-3 mb-1');

    echo $OUTPUT->footer();
}
