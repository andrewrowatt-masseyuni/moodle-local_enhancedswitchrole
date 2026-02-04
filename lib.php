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
 * Library functions for local_enhancedroleswitch plugin.
 *
 * @package    local_enhancedswitchrole
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/group/lib.php');

/**
 * Get groups available in a course for enhanced role switching.
 *
 * @param int $courseid Course ID
 * @return array Array of groups
 */
function local_enhancedswitchrole_get_course_groups($courseid) {
    return groups_get_all_groups($courseid);
}

/**
 * Check if enhanced role switch UI should be shown.
 *
 * @param context $context Context to check
 * @return bool True if should show enhanced UI
 */
function local_enhancedswitchrole_should_show($context) {
    global $CFG;
    
    // Only show in course contexts.
    if ($context->contextlevel !== CONTEXT_COURSE) {
        return false;
    }
    
    // User must have capability to switch roles.
    if (!has_capability('moodle/role:switchroles', $context)) {
        return false;
    }
    
    return true;
}
