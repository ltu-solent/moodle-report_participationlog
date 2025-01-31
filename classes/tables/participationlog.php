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
 * Participation log table
 *
 * @package   report_participationlog
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_participationlog\tables;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/tablelib.php");

use core_user;
use moodle_exception;
use moodle_url;
use table_sql;

/**
 * Participation log table
 */
class participationlog extends table_sql {

    /**
     * Form data from filter form
     *
     * @var stdClass
     */
    private $filters;
    /**
     * Strings that will be used on the page a lot
     *
     * @var array
     */
    private $participationstrings = [];

    /**
     * Table constructor
     *
     * @param string $uniqueid
     * @param array|stdClass $filters
     */
    public function __construct($uniqueid, $filters) {
        parent::__construct($uniqueid);
        $this->filters = (object)$filters;
        $this->useridfield = 'userid';
        $this->set_participationstrings();
        $columns = [
            'id',
            'eventname',
            'object',
            'course',
            'participationtype',
            'timecreated',
        ];
        $this->define_columns($columns);
        $this->define_headers([
            get_string('id', 'report_participationlog'),
            get_string('event', 'report_participationlog'),
            get_string('object', 'report_participationlog'),
            get_string('modulepage', 'report_participationlog'),
            get_string('participationtype', 'report_participationlog'),
            get_string('datetime', 'report_participationlog'),
        ]);
        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->no_sorting('participationtype');
        $this->no_sorting('object');
        $this->show_download_buttons_at([TABLE_P_BOTTOM]);
        // This just prevents showing all users by accident.
        $where = 'false = true';
        $params = [];
        if (isset($this->filters->userid)) {
            $params['userid'] = $this->filters->userid;
            $params['action'] = 'displaylogs';

            if (isset($this->filters->startdate)) {
                $params['start'] = $this->filters->startdate;
            }
            if (isset($this->filters->enddate)) {
                // Enddate timestamp should be the end of the day.
                $endtime = strtotime(date('Y-m-d 23:59:59', $this->filters->enddate));
                if ($endtime < $params['start']) {
                    throw new moodle_exception('invalid date range');
                }
                $params['end'] = $endtime;
            }
            $where = "l.userid = :userid AND l.timecreated >= :start AND l.timecreated <= :end";
        }
        $url = new moodle_url('/report/participationlog/index.php', $params);
        $this->define_baseurl($url);
        $select = "l.id, l.userid, l.eventname, l.timecreated, c.shortname as course, l.courseid, l.action, l.component, l.target,
        l.crud, l.edulevel, l.objecttable, l.objectid, l.contextlevel, l.contextid, l.contextinstanceid, l.relateduserid,
        cm.id cmid";
        $from = "{logstore_standard_log} l
        LEFT JOIN {course} c ON l.courseid = c.id
        LEFT JOIN {course_modules} cm ON cm.id = l.contextinstanceid";

        $this->set_sql($select, $from, $where, $params);
    }

    /**
     * Human readable activity description column
     *
     * @param stdClass $row
     * @return string
     */
    protected function col_object($row) {
        if ($row->contextlevel == CONTEXT_MODULE) {
            return $this->coursemodule($row);
        }
        if ($row->contextlevel == CONTEXT_COURSE) {
            return $this->courseinfo($row);
        }
        if ($row->contextlevel == CONTEXT_USER) {
            return $this->userinfo($row);
        }
        if ($row->contextlevel == CONTEXT_SYSTEM) {
            if (strpos($row->eventname, 'loggedin')) {
                return $this->userinfo($row);
            }
        }
        return s($row->eventname . ' ' . $row->objecttable . ' ' . $row->objectid . ' ' . $row->contextlevel);
    }

    /**
     * Course info column
     *
     * @param stdClass $row
     * @return string
     */
    private function courseinfo($row) {
        global $DB;
        if (!$course = $DB->get_record('course', ['id' => $row->courseid])) {
            return get_string('deletedcourse', 'report_participationlog', $row->courseid);
        }
        return s($course->fullname . ': ' . $row->target . ' ' . $row->action);
    }

    /**
     * Activity name and actions if still exists
     *
     * @param stdClass $row
     * @return string
     */
    private function coursemodule($row) {
        global $DB;
        if (!$DB->record_exists('course', ['id' => $row->courseid])) {
            return get_string('courseactivitydeleted', 'report_participationlog', $row->cmid);
        }
        $modinfo = get_fast_modinfo($row->courseid);
        if (!isset($modinfo->cms[$row->cmid])) {
            return get_string('activitydeleted', 'report_participationlog');
        }
        $mod = $modinfo->cms[$row->cmid];
        return s($mod->modfullname . ': "' . $mod->name . '" ' . $row->target . ' ' . $row->action);
    }

    /**
     * User information
     *
     * @param stdClass $row
     * @return string
     */
    private function userinfo($row) {
        $user = core_user::get_user($row->userid);
        $content = fullname($user) . ' ' . $row->action . ' ' . $row->target;
        if (isset($row->relateduserid)) {
            if ($relateduser = core_user::get_user($row->relateduserid)) {
                $content .= ' ' . fullname($relateduser);
            }
        }
        return $content;
    }

    /**
     * Set up some reused strings
     *
     * @return void
     */
    private function set_participationstrings() {
        $this->participationstrings = [
            'crud' => [
                'c' => get_string('create', 'report_participationlog'),
                'r' => get_string('read', 'report_participationlog'),
                'u' => get_string('update', 'report_participationlog'),
                'd' => get_string('delete', 'report_participationlog'),
            ],
            'edulevel' => [
                \core\event\base::LEVEL_PARTICIPATING => get_string('participating', 'report_participationlog'),
                \core\event\base::LEVEL_TEACHING => get_string('teaching', 'report_participationlog'),
                \core\event\base::LEVEL_OTHER => '',
            ],
        ];
    }

    /**
     * Participation type column
     *
     * @param stdClass $row
     * @return string
     */
    protected function col_participationtype($row) {
        $participation =
            $this->participationstrings['crud'][$row->crud] . ' ' .
            $this->participationstrings['edulevel'][$row->edulevel];

        if (strpos($row->eventname, 'loggedin') !== false) {
            return $row->action;
        }
        return $participation;
    }

    /**
     * Time created column
     *
     * @param stdClass $row
     * @return string
     */
    protected function col_timecreated($row) {
        return userdate($row->timecreated);
    }

    /**
     * Download
     *
     * @return void
     */
    public function download() {
        \core\session\manager::write_close();
        $this->out(0, false);
        exit;
    }
}
