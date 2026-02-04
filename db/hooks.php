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
 * Hook callbacks for local_enhancedroleswitch plugin.
 *
 * @package    local_enhancedroleswitch
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$callbacks = [
    [
        'hook' => \core\hook\access\after_role_switched::class,
        'callback' => \local_enhancedroleswitch\hook_listener\role_switch::class . '::after_role_switched',
        'priority' => 0,
    ],
    [
        'hook' => \core\hook\after_config::class,
        'callback' => \local_enhancedroleswitch\hook_listener\page_redirect::class . '::after_config',
        'priority' => 0,
    ],
];
