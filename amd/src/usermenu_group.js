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
 * Replaces the role display in the user menu with the role and group name.
 *
 * @module    local_enhancedswitchrole/usermenu_group
 * @copyright 2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

/**
 * Initialise the user menu group name display.
 *
 * Finds the role metadata span in the user menu and replaces its text
 * with a localised string containing both the role and group name.
 *
 * @param {string} groupName The name of the temporary group to display.
 */
export const init = async(groupName) => {
    const roleSpan = document.querySelector('.usermenu .usertext .meta.role');
    if (!roleSpan || !groupName) {
        return;
    }
    const roleName = roleSpan.textContent;
    const text = await getString('switchedroleandgroup', 'local_enhancedswitchrole', {
        role: roleName,
        group: groupName,
    });
    roleSpan.textContent = text;
};
