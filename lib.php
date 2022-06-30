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
 * Lib for for participation log
 *
 * @package   report_participationlog
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Add Participation log link to the profile report section
 *
 * @param \core_user\output\myprofile\tree $tree
 * @param stdClass $user
 * @param bool $iscurrentuser
 * @param stdClass $course
 * @return bool
 */
function report_participationlog_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $USER;
    if (isguestuser($user)) {
        return true;
    }
    if (!$iscurrentuser) {
        return true; // Only show the link for the actual person, not visitors.
    }
    // Only show the report link if they have the capabilitiy.
    if (!has_capability('report/participationlog:view', context_system::instance())) {
        return true;
    }

    $url = new moodle_url('/report/participationlog/index.php');
    $node = new core_user\output\myprofile\node('reports',
        'participationlog', get_string('pluginname', 'report_participationlog'),
        null, $url);
        $tree->add_node($node);
    return true;
}
