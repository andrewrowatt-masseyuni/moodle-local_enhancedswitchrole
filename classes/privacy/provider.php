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

namespace local_enhancedswitchrole\privacy;

use context;
use context_course;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

/**
 * Privacy Subsystem implementation for the Enhanced Switch Role plugin.
 *
 * @package    local_enhancedswitchrole
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /** @var string Database table holding temporary role-switch memberships. */
    const TEMP_MEMBERSHIPS_TABLE = 'local_enhancedswitchrole_temp';

    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection The metadata collection to append to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $fieldDescriptions = [
            'userid' => 'privacy:metadata:local_enhancedswitchrole_temp:userid',
            'groupid' => 'privacy:metadata:local_enhancedswitchrole_temp:groupid',
            'timecreated' => 'privacy:metadata:local_enhancedswitchrole_temp:timecreated',
        ];

        $collection->add_database_table(
            self::TEMP_MEMBERSHIPS_TABLE,
            $fieldDescriptions,
            'privacy:metadata:local_enhancedswitchrole_temp'
        );

        return $collection;
    }

    /**
     * Locate course contexts that contain temporary membership data for a given user.
     *
     * @param int $userid The user to search for.
     * @return contextlist Contexts with data belonging to this user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $ctxlist = new contextlist();

        $ctxlist->add_from_sql(
            "SELECT DISTINCT ctx.id
               FROM {" . self::TEMP_MEMBERSHIPS_TABLE . "} tm
               JOIN {context} ctx ON ctx.instanceid = tm.courseid AND ctx.contextlevel = :courselevel
              WHERE tm.userid = :uid",
            ['uid' => $userid, 'courselevel' => CONTEXT_COURSE]
        );

        return $ctxlist;
    }

    /**
     * Find all users who have temporary membership records within a specific context.
     *
     * @param userlist $userlist The userlist to populate.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $ctx = $userlist->get_context();
        if (!($ctx instanceof context_course)) {
            return;
        }

        $userlist->add_from_sql(
            'userid',
            '{' . self::TEMP_MEMBERSHIPS_TABLE . '}',
            'courseid = :cid',
            ['cid' => $ctx->instanceid]
        );
    }

    /**
     * Export temporary membership records for a user across their approved course contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $ownerid = $contextlist->get_user()->id;
        $subpath = [get_string('pluginname', 'local_enhancedswitchrole')];

        foreach ($contextlist->get_contexts() as $ctx) {
            if (!($ctx instanceof context_course)) {
                continue;
            }

            $rows = $DB->get_records(
                self::TEMP_MEMBERSHIPS_TABLE,
                ['userid' => $ownerid, 'courseid' => $ctx->instanceid],
                'timecreated ASC'
            );

            if (empty($rows)) {
                continue;
            }

            $exportedrows = array_map(function ($row) {
                return [
                    'switched_as_user' => $row->userid,
                    'target_group' => $row->groupid,
                    'in_course' => $row->courseid,
                    'membership_created_at' => transform::datetime($row->timecreated),
                ];
            }, $rows);

            writer::with_context($ctx)->export_data($subpath, (object) [
                'temp_memberships' => $exportedrows,
            ]);
        }
    }

    /**
     * Remove every temporary membership row tied to a given context (course-level only).
     *
     * @param context $context The context whose data should be erased.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        if (!($context instanceof context_course)) {
            return;
        }
        self::purge_temp_memberships_for_course($context->instanceid);
    }

    /**
     * Remove temporary membership rows for a single user in their approved course contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to delete from.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $ownerid = $contextlist->get_user()->id;
        $courseids = self::extract_course_ids_from_contexts($contextlist->get_contexts());

        if (empty($courseids)) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'crs');
        $inparams['uid'] = $ownerid;
        $DB->delete_records_select(
            self::TEMP_MEMBERSHIPS_TABLE,
            "userid = :uid AND courseid {$insql}",
            $inparams
        );
    }

    /**
     * Bulk-delete temporary membership rows for multiple users within one context.
     *
     * @param approved_userlist $userlist The approved users whose data should be removed.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $ctx = $userlist->get_context();
        if (!($ctx instanceof context_course)) {
            return;
        }

        $targetuserids = $userlist->get_userids();
        if (empty($targetuserids)) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($targetuserids, SQL_PARAMS_NAMED, 'usr');
        $inparams['cid'] = $ctx->instanceid;
        $DB->delete_records_select(
            self::TEMP_MEMBERSHIPS_TABLE,
            "courseid = :cid AND userid {$insql}",
            $inparams
        );
    }

    /**
     * Retrieve course contexts that hold temporary membership entries for a specific person.
     *
     * @param int $userid The person whose contexts we need.
     * @return array Array of context objects.
     */
    private static function fetch_temp_membership_course_contexts(int $userid): array {
        $ctxlist = self::get_contexts_for_userid($userid);
        return array_filter($ctxlist->get_contexts(), function ($ctx) {
            return $ctx instanceof context_course;
        });
    }

    /**
     * Wipe all temporary membership rows that belong to a particular course.
     *
     * @param int $courseid The course whose temporary memberships should be removed.
     */
    private static function purge_temp_memberships_for_course(int $courseid): void {
        global $DB;
        $DB->delete_records(self::TEMP_MEMBERSHIPS_TABLE, ['courseid' => $courseid]);
    }

    /**
     * Pull course IDs out of an iterable of context objects, keeping only course-level ones.
     *
     * @param iterable $contexts The context objects to inspect.
     * @return int[] Course IDs extracted from course contexts.
     */
    private static function extract_course_ids_from_contexts(iterable $contexts): array {
        $courseids = [];
        foreach ($contexts as $ctx) {
            if ($ctx instanceof context_course) {
                $courseids[] = $ctx->instanceid;
            }
        }
        return $courseids;
    }
}
