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
 * Chart class that does the processing for graph.
 *
 * @package   report_participationlog
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_participationlog;

use core_user;
/**
 * Participation chart
 */
class partchart {

    /**
     * Chart object
     *
     * @var core\chart_line
     */
    private $chart;

    /**
     * Raw data from the SQL query
     *
     * @var array
     */
    private $data;

    /**
     * Params for the SQL query
     *
     * @var array
     */
    private $params;

    /**
     * Chart constructor
     *
     * @param array $params including date range and selected userid
     */
    public function __construct($params) {
        $this->params = $params;
        $this->params += [
            'start' => $params['startdate'],
            'end' => strtotime(date('Y-m-d 23:59:59', $params['enddate']))
        ];

        $this->chart = new \core\chart_line();
        $this->chart->get_xaxis(0, true)->set_label(get_string('date', 'report_participationlog'));
        $this->chart->get_yaxis(0, true)->set_label(get_string('hits', 'report_participationlog'));
        if ($this->params['userid']) {
            $user = core_user::get_user($this->params['userid']);
            $this->chart->set_title(get_string('chartfor', 'report_participationlog', fullname($user)));
            $this->buildquery();
        }
    }

    /**
     * Build and run the SQL query. If there's data go on to create the chart.
     *
     * @return void
     */
    private function buildquery() {
        global $DB;
        $sql = "SELECT l.id, l.userid, l.eventname,
        l.timecreated,
        l.courseid, l.action, l.component, l.target,
        l.crud, l.edulevel, l.objecttable, l.objectid, l.contextlevel, l.contextid, l.contextinstanceid, l.relateduserid
            FROM {logstore_standard_log} l
            WHERE l.userid = :userid
                AND (l.timecreated >= :start AND l.timecreated <= :end)
                ORDER BY l.timecreated ASC";

        $this->data = $DB->get_records_sql($sql, $this->params);
        if (count($this->data) > 0) {
            // Add formatted date after the query so this remains crossplatform compatible.
            foreach ($this->data as $item) {
                $item->datecreated = date('Y-m-d', $item->timecreated);
            }
            $this->prepareoutput();
        }
    }

    /**
     * Collates the data and adds to the chart
     *
     * @return void
     */
    private function prepareoutput() {
        $params = $this->params;

        $params['type'] = 'alldata';
        $alldata = $this->filter_data($params);
        $serie = new \core\chart_series(
            get_string('alldata', 'report_participationlog'),
            array_values($alldata));
        $this->chart->add_series($serie);
        // All the series will have the same date labels, so you only need to do this once.
        $labels = array_keys($alldata);
        $this->chart->set_labels($labels);

        $params['type'] = 'login';
        $login = new \core\chart_series(
            get_string('login', 'report_participationlog'),
            array_values($this->filter_data($params)));
        $this->chart->add_series($login);

        $params['type'] = 'participating';
        $login = new \core\chart_series(
            get_string('participatingactivity', 'report_participationlog'),
            array_values($this->filter_data($params)));
        $this->chart->add_series($login);

        $params['type'] = 'viewing';
        $login = new \core\chart_series(
            get_string('viewing', 'report_participationlog'),
            array_values($this->filter_data($params)));
        $this->chart->add_series($login);
    }

    /**
     * Public function to echo or return the chart html
     *
     * @param boolean $echo
     * @return void|string
     */
    public function print_chart($echo = true) {
        global $OUTPUT;
        $html = '';
        if (count($this->data) == 0) {
            $html = get_string('nochartdata', 'report_participationlog');
        } else {
            $html = $OUTPUT->render($this->chart);
        }
        if ($echo) {
            echo $html;
        } else {
            return $html;
        }
    }

    /**
     * The data will have missing data for dates, so we need to fill the gaps so that the
     * chart displays correctly.
     *
     * @param array $params Filter parameters
     * @return array Filtered results
     */
    private function filter_data($params): array {
        // The data is timestamped to the second.
        // We need to group and sum by the day - the SQL includes a date stamp for this.
        // If there are missing dates between the time periods, we need an empty entry.
        // The key will be a cannonical date for each day.
        $starttime = api::stats_get_base_daily($params['start']);
        $endtime = strtotime(date('Y-m-d 23:59:59', $params['end']));
        $aftertime = $starttime;
        $times = [];

        while ($aftertime < $endtime) {
            $timekey = date('Y-m-d', $aftertime);
            $entries = array_filter($this->data, function($row) use($timekey, $params) {
                if ($row->datecreated != $timekey) {
                    return false;
                }
                if ($params['type'] == 'alldata') {
                    return true;
                }
                if ($params['type'] == 'login') {
                    if (strpos($row->eventname, 'loggedin') !== false) {
                        return true;
                    }
                }
                if ($params['type'] == 'participating') {
                    if ($row->edulevel == \core\event\base::LEVEL_PARTICIPATING) {
                        return true;
                    }
                }
                if ($params['type'] == 'viewing') {
                    if ($row->crud == 'r') {
                        return true;
                    }
                }
                return false;
            });

            $times[$timekey] = count($entries);
            $aftertime = api::stats_get_next_day_start($aftertime);
        }
        return $times;
    }
}
