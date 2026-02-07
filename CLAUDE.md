# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Structure

This is a **Moodle 4.05 installation** with a local plugin under active development at `local/enhancedswitchrole/`. The root is the full Moodle codebase; plugin work should be confined to `local/enhancedswitchrole/`.

## Plugin: local_enhancedswitchrole

Enhances Moodle's "Switch role to..." feature by letting teachers/admins also select a specific group when switching to a student role, enabling preview of group-restricted content.

### How it works

1. **Hook interception** (`db/hooks.php` → `classes/hook_listener/page_redirect.php`): Listens for `\core\hook\after_config` and redirects requests to `/course/switchrole.php` to the plugin's own `switchrole.php`.
2. **Role switch page** (`switchrole.php`): Replaces core switchrole.php. Handles the role switch via `util::role_switch()` and renders a group-aware role selection UI via `util::render_roles()`.
3. **Utility class** (`classes/util.php`): Core logic — manages temporary group memberships (add/remove via `groups_add_member`/`groups_remove_member`), renders the Mustache template, and distinguishes cohort-synced groups from course groups via a custom SQL query on the `enrol` table.
4. **Database** (`db/install.xml`): Single table `local_enhancedswitchrole_temp` tracking temporary group memberships (userid, groupid, courseid, timecreated).
5. **Template** (`templates/roles.mustache`): Renders role buttons with optional group dropdown for student-archetype roles. Groups are split into "Cohort groups" (from meta enrolment) and "Course groups".
6. **User menu hook** (`classes/hook_listener/user_menu.php`): Listens for `\core_user\hook\extend_user_menu` to add a "Switch group in student role" link to the user menu when a role switch is active.
7. **Group switch page** (`switchgroup.php`): Displays the group selection UI and handles switching the temporary group membership during an active role switch session.
8. **Template** (`templates/groups.mustache`): Renders group selection buttons split into "Cohort groups" and "Course groups".

### Key Moodle APIs used

- `role_switch()`, `get_switchable_roles()`, `is_role_switched()`, `get_archetype_roles('student')` — core role switching
- `groups_get_all_groups()`, `groups_add_member()`, `groups_remove_member()`, `groups_is_member()` — group management
- `\core\hook\after_config` — Moodle 4.3+ hook system for request interception
- `\core_user\hook\extend_user_menu` — hook to add items to the user menu dropdown
- `$OUTPUT->render_from_template()` — Mustache template rendering

## Common Commands

### Linting and code style (run from plugin directory)
```bash
# Moodle code checker (PHP_CodeSniffer with Moodle rules) — zero warnings required
php local/codechecker/index.php local/enhancedswitchrole

# Mustache lint (requires moodle-plugin-ci)
moodle-plugin-ci mustache
```

### Testing
```bash
# PHPUnit (from Moodle root, after initialising phpunit)
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --testsuite local_enhancedswitchrole_testsuite

# Single PHPUnit test file
vendor/bin/phpunit local/enhancedswitchrole/tests/privacy/provider_test.php

# Behat (requires initialised behat environment)
php admin/tool/behat/cli/init.php
vendor/bin/behat --config /path/to/behatrun/behat/behat.yml --tags @local_enhancedswitchrole
```

### CI

GitHub Actions workflow at `local/enhancedswitchrole/.github/workflows/moodle-ci.yml` runs: phplint, phpmd, phpcs (zero warnings), phpdoc (zero warnings), validate, savepoints, mustache lint, grunt, phpunit, and behat against Moodle 4.05 with PHP 8.1 and PostgreSQL.

## Coding Conventions

- Follow **Moodle coding standards** (enforced via `moodle` phpcs ruleset). PHP 8.1+ target.
- All PHP files must include the GPL3 license header and `@package local_enhancedswitchrole`.
- Language strings go in `lang/en/local_enhancedswitchrole.php`. Privacy strings are prefixed with `privacy:metadata:`.
- Mustache templates use Bootstrap 4 classes (Moodle 4.x theme layer). Use `{{#str}}` helper for localised strings.
- Database changes require updating `db/install.xml` (XMLDB format) and adding upgrade steps in `db/upgrade.php`.
- Hook callbacks are registered in `db/hooks.php`. No `lib.php` — the plugin uses only the modern hook system.
