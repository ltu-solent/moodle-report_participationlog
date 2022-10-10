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
 * External functions for Participationlog
 *
 * @package   report_participationlog
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_participationlog;

use core_user\external\user_summary_exporter;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

/**
 * Web service functions for AJAX calls
 */
class external extends external_api {

    /**
     * Returns parameter types for get_relevant_users function.
     *
     * @return \external_function_parameters Parameters
     */
    public static function get_relevant_users_parameters() {
        return new external_function_parameters([
                'query' => new external_value(PARAM_RAW,
                    'Query string (full or partial user full name or other details)'),
                ]);
    }

    /**
     * Returns result type for get_relevant_users function.
     *
     * @return \external_description Result type
     */
    public static function get_relevant_users_returns() {
        return new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'User id'),
                    'fullname' => new external_value(PARAM_RAW, 'Full name as text'),
                    'idnumber' => new external_value(PARAM_RAW, 'Idnumber field for user', VALUE_OPTIONAL),
                    'email' => new external_value(PARAM_RAW, 'email address for user', VALUE_OPTIONAL),
                    'profileimageurlsmall' => new external_value(PARAM_URL, 'URL to small profile image')
                ]));
    }

    /**
     * Searches for users given a query, taking into account the current user's permissions and
     * possibly a course to check within.
     *
     * @param string $query Query text
     * @return array Defined return structure
     */
    public static function get_relevant_users($query) {
        global $CFG, $PAGE;

        // Validate parameter.
        [
            'query' => $query,
        ] = self::validate_parameters(self::get_relevant_users_parameters(), [
            'query' => $query,
        ]);

        // Validate the context (search page is always system context).
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);

        // If not logged in, can't see anyone when forceloginforprofiles is on.
        if (!empty($CFG->forceloginforprofiles)) {
            if (!isloggedin() || isguestuser()) {
                return [];
            }
        }

        $users = \core_user::search($query);
        $showuseridentity = explode(',', $CFG->showuseridentity);
        $result = [];
        foreach ($users as $user) {
            // Get a standard exported user object.
            $fulldetails = (new user_summary_exporter($user))->export($PAGE->get_renderer('core'));

            $item = [
                'id' => $fulldetails->id,
                'fullname' => $fulldetails->fullname,
                'profileimageurlsmall' => $fulldetails->profileimageurlsmall,
            ];
            foreach ($showuseridentity as $ident) {
                $item[$ident] = $fulldetails->{$ident};
            }

            $result[] = (object)$item;
        }
        return $result;
    }
}
