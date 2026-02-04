# Enhanced Role Switch Plugin (local_enhancedroleswitch)

## Description

This Moodle local plugin extends the standard "Switch role to..." feature to allow teachers to switch to a student role **within a specific group**. This is useful for teachers who want to see how the course appears to students in different groups, especially when using group-based activities or restrictions.

## Features

- Allows teachers to switch to a student role in a specific group
- Temporarily adds the teacher to the selected group during the role switch
- Automatically removes the teacher from the group when they return to their normal role
- Preserves permanent group memberships (won't remove teachers who were already members)
- Integrates seamlessly with the existing role switch interface
- Uses Bootstrap 4.6 dropdown UI for group selection
- **No core code modifications required** - uses automatic redirection from core to plugin version

## Requirements

- Moodle 4.5 or later (Bootstrap 4.6)
- PHP 8.1 or later

## Installation

1. Copy the `enhancedroleswitch` folder to the `local/` directory in your Moodle installation
2. Visit the Site Administration > Notifications page to complete the installation
3. The plugin will create a database table `local_enhancedroleswitch_temp` to track temporary group memberships
4. **No core modifications needed** - the plugin automatically redirects users from `/course/switchrole.php` to `/local/enhancedroleswitch/switchrole.php`

## How It Works

### Automatic Redirection
When users access the standard `/course/switchrole.php`, the plugin automatically redirects them to `/local/enhancedroleswitch/switchrole.php` which includes the enhanced group selection features.

### Usage Flow

1. Navigate to a course where you have teacher permissions
2. Click on "Switch role to..." in the user menu or settings
3. You will see the regular role switching options
4. For Student roles, there will be an additional dropdown labeled "Student in group..."
5. Click the dropdown to see a list of all groups in the course
6. Select a group to switch to the student role within that group
7. The course will now be displayed as a student in that group would see it
8. To return to your normal role, click "Return to normal role" - you will be automatically removed from the temporary group

## Technical Implementation

### No Core Modifications
This plugin achieves its functionality without modifying core Moodle code:

1. **Automatic Redirection**: Uses the `after_config` hook to redirect from `/course/switchrole.php` to `/local/enhancedroleswitch/switchrole.php`
2. **Enhanced switchrole.php**: Contains a copy of the core switchrole.php with added group selection functionality  
3. **Hook Integration**: Uses `after_role_switched` hook to manage temporary group memberships

### Code Duplication Trade-off
The plugin contains a copy of `course/switchrole.php` to avoid core modifications. While this introduces code duplication, it's an acceptable trade-off to maintain core integrity and simplify upgrades.

## How It Works

1. When a teacher selects a group from the dropdown, the group ID is stored in the PHP session
2. The `after_role_switched` hook is triggered when role switching occurs
3. The plugin's hook listener:
   - Checks if a group was selected
   - Adds the user to the group if they weren't already a member
   - Records this as a temporary membership in the database
4. When the teacher returns to their normal role:
   - The plugin removes them from any temporary groups
   - Permanent group memberships are preserved

## Privacy

The plugin stores minimal data:
- User ID of the person who switched roles
- Group ID they were temporarily added to
- Course ID
- Timestamp of when the temporary membership was created

This data is automatically deleted when the user returns to their normal role.

## Testing

Run the plugin's unit tests:

```bash
php admin/tool/phpunit/cli/util.php --install
vendor/bin/phpunit local/enhancedroleswitch/tests/enhanced_roleswitch_test.php
```

## License

GPL v3 or later

## Author

Developed for Moodle
