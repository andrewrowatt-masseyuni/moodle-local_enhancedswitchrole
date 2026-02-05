# Enhanced Switch Role

A Moodle plugin that replaces the core role switching functionality to support switching to a student role within a specific group.

## Description

When teachers or administrators use Moodle's "Switch role to..." feature, they can only view the course as a generic student. This plugin enhances that functionality by allowing them to also select a specific group to be temporarily added to during the role switch.

This is particularly useful for:

-   Testing group-restricted activities and resources
-   Verifying that group-based restrictions work correctly
-   Previewing the student experience within a specific group context

When the user switches back to their normal role, any temporary group memberships are automatically removed.

## Features

-   Automatically intercepts core role switch requests via Moodle hooks
-   Allows selecting a specific group when switching to a student role
-   Separates cohort-synced groups from regular course groups in the selection interface
-   Temporary group memberships are tracked and automatically cleaned up
-   Fully backwards compatible with the standard role switching workflow

## Requirements

-   Moodle 4.3 or later (requires hook system support)

## Installation

1.  Download or clone this plugin into `/local/enhancedswitchrole`
2.  Visit Site Administration \> Notifications to complete the installation
3.  No additional configuration required - the plugin works automatically

## Usage

1.  Navigate to any course where you have the `moodle/role:switchroles` capability
2.  Use the standard "Switch role to..." menu option
3.  When switching to a student role, you will see an option to select a specific group
4.  Select "None" to switch without group membership, or select a group to be temporarily added
5.  Use "Return to my normal role" to switch back and remove any temporary group memberships

## Privacy

This plugin stores temporary group membership records to track which memberships need to be removed when switching back. These records contain:

-   User ID
-   Group ID
-   Course ID
-   Timestamp of when the temporary membership was created

Records are automatically deleted when the user switches back to their normal role.

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.

## Author

Andrew Rowatt [A.J.Rowatt@massey.ac.nz](mailto:A.J.Rowatt@massey.ac.nz)
