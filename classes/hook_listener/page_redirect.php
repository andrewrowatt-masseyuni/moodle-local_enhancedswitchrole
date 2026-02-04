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
 * Hook listener for page redirects.
 *
 * @package    local_enhancedroleswitch
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_redirect {

    /**
     * Redirect from core switchrole.php to plugin version.
     *
     * @param \core\hook\after_config $hook
     */
    public static function after_config(\core\hook\after_config $hook): void {
        global $ME;
        
        // Check if we're on the core switchrole.php page.
        if (isset($ME) && $ME === '/course/switchrole.php') {
            // Build parameter array with proper sanitization.
            $params = [];
            if (isset($_GET['id'])) {
                $params['id'] = required_param('id', PARAM_INT);
            }
            if (isset($_GET['switchrole'])) {
                $params['switchrole'] = optional_param('switchrole', -1, PARAM_INT);
            }
            if (isset($_GET['returnurl'])) {
                $params['returnurl'] = optional_param('returnurl', '', PARAM_LOCALURL);
            }
            if (isset($_GET['groupid'])) {
                $params['groupid'] = optional_param('groupid', 0, PARAM_INT);
            }
            if (isset($_GET['sesskey'])) {
                $params['sesskey'] = optional_param('sesskey', '', PARAM_ALPHANUM);
            }
            
            // Redirect to the plugin version with sanitized parameters.
            $newurl = new \moodle_url('/local/enhancedroleswitch/switchrole.php', $params);
            redirect($newurl);
        }
    }
}
