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
 * The purpose of this file is to allow the user to switch roles and be redirected
 * back to the page that they were on.
 *
 * This is an enhanced version that supports switching to a student role within a specific group.
 * This functionality is also supported in {@link /course/view.php} in order to comply
 * with backwards compatibility.
 * The reason that we created this file was so that user didn't get redirected back
 * to the course view page only to be redirected again.
 *
 * @since Moodle 2.0
 * @package local_enhancedswitchrole
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot . '/group/lib.php');

$id         = required_param('id', PARAM_INT);
$switchrole = optional_param('switchrole', -1, PARAM_INT);
$returnurl  = optional_param('returnurl', '', PARAM_LOCALURL);
$groupid    = optional_param('groupid', 0, PARAM_INT);

$PAGE->set_url('/local/enhancedswitchrole/switchrole.php', array('id'=>$id, 'switchrole'=>$switchrole));

if ($switchrole >= 0) {
    require_sesskey();
}

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    redirect(new moodle_url('/'));
}

$context = context_course::instance($course->id);

// Remove any switched roles before checking login.
if ($switchrole == 0) {
    role_switch(0, $context);

    // Switching back to normal role - remove temporary group memberships.
    $tempmemberships = $DB->get_records('local_enhancedswitchrole_temp', [
        'userid' => $USER->id,
        'courseid' => $course->id
    ]);

    foreach ($tempmemberships as $membership) {
        // Remove from group.
        groups_remove_member($membership->groupid, $USER->id);
        // Delete the temporary membership record.
        $DB->delete_records('local_enhancedswitchrole_temp', ['id' => $membership->id]);
    }

}
require_login($course);

// Switchrole - sanity check in cost-order...
if ($switchrole > 0 && has_capability('moodle/role:switchroles', $context)) {
    // Is this role assignable in this context?
    // inquiring minds want to know...
    $aroles = get_switchable_roles($context);
    if (is_array($aroles) && isset($aroles[$switchrole])) {
        // Store group ID in session if provided (for group membership handling).
        // Note: sesskey is already validated above at line 42-44.
        if ($groupid > 0) {
            // Verify the group exists and belongs to this course.
            $group = $DB->get_record('groups', ['id' => $groupid, 'courseid' => $course->id]);
            if ($group) {
                // Check if user is already a member (don't want to remove a real membership later).
                $ismember = groups_is_member($groupid, $USER->id);
                
                if (!$ismember) {
                    // Add user to the group temporarily.
                    groups_add_member($groupid, $USER->id);

                    // Record this as a temporary membership.
                    $record = new \stdClass();
                    $record->userid = $USER->id;
                    $record->groupid = $groupid;
                    $record->courseid = $course->id;
                    $record->timecreated = time();
                    $DB->insert_record('local_enhancedswitchrole_temp', $record);
                }
            }
        }
        role_switch($switchrole, $context);
    }
} else if ($switchrole < 0) {

    $PAGE->set_title(get_string('switchroleto'));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_pagelayout('incourse');

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('switchroleto'));

    // Overall criteria aggregation.
    $roles = array();
    $assumedrole = -1;
    if (is_role_switched($course->id)) {
        $roles[0] = get_string('switchrolereturn');
        $assumedrole = $USER->access['rsw'][$context->path];
    }
    $availableroles = get_switchable_roles($context, ROLENAME_BOTH);
    if (is_array($availableroles)) {
        foreach ($availableroles as $key => $role) {
            if ($assumedrole == (int)$key) {
                continue;
            }
            $roles[$key] = $role;
        }
    }
    echo $OUTPUT->box(markdown_to_html(get_string('switchroleto_help')));

    // Get student role IDs for enhanced switching.
    $studentroles = get_archetype_roles('student');
    $studentroleids = array_keys($studentroles);

    // Get course groups.
    require_once($CFG->dirroot . '/local/enhancedswitchrole/lib.php');
    $coursegroups = local_enhancedswitchrole_get_course_groups($course->id);

    foreach ($roles as $key => $role) {
        $url = new moodle_url('/local/enhancedswitchrole/switchrole.php', array('id' => $id, 'switchrole' => $key, 'returnurl' => $returnurl));
        // Button encodes special characters, apply htmlspecialchars_decode() to avoid double escaping.
        echo $OUTPUT->container($OUTPUT->single_button($url, htmlspecialchars_decode($role, ENT_COMPAT)), 'mx-3 mb-1');
        
        // Show group dropdown for student roles if groups exist.
        if (!empty($coursegroups) && $key > 0 && in_array($key, $studentroleids)) {
            $dropdownid = 'groupDropdown' . clean_param($key, PARAM_INT);
            echo '<div class="mx-3 mb-1">';
            echo '<div class="dropdown">';
            echo '<button class="btn btn-secondary dropdown-toggle" type="button" id="' . s($dropdownid) . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
            echo get_string('studentingroup', 'local_enhancedswitchrole');
            echo '</button>';
            echo '<div class="dropdown-menu" aria-labelledby="' . s($dropdownid) . '">';
            foreach ($coursegroups as $group) {
                $groupurl = new moodle_url('/local/enhancedswitchrole/switchrole.php', array(
                    'id' => $id,
                    'switchrole' => $key,
                    'groupid' => $group->id,
                    'returnurl' => $returnurl,
                    'sesskey' => sesskey()
                ));
                echo '<a class="dropdown-item" href="' . $groupurl->out(false) . '">' . format_string($group->name) . '</a>';
            }
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
    }

    $url = new moodle_url($returnurl);
    echo $OUTPUT->container($OUTPUT->action_link($url, get_string('cancel')), 'mx-3 mb-1');

    echo $OUTPUT->footer();
    exit;
}

redirect(new moodle_url($returnurl));
