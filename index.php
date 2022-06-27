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
 * Main landing page
 *
 * @package   report_participationlog
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

$userid = optional_param('userid', 0, PARAM_INT);
$action = optional_param('action', 'none', PARAM_ALPHA);
$startdate = optional_param('start', strtotime('6 MONTHS AGO'), PARAM_INT);
$enddate = optional_param('end', time(), PARAM_INT);

require_capability('report/participationlog:view', context_system::instance());
$PAGE->set_context(context_system::instance());
$title = get_string('pluginname', 'report_participationlog');
if ($userid > 0) {
    $user = core_user::get_user($userid, '*', MUST_EXIST);
    if ($action == 'displaychart') {
        $title = get_string('chartfor', 'report_participationlog', fullname($user));
    }
    if ($action == 'displaylogs') {
        $title = get_string('logsfor', 'report_participationlog', fullname($user));
    }
}

$filterform = new report_participationlog\forms\filter();
$params = [];

if ($filterdata = $filterform->get_data()) {
    $userid = $filterdata->userid;
    $params = [
        'userid' => $userid
    ];
    if (isset($filterdata->displaychart)) {
        $action = 'displaychart';
    }
    if (isset($filterdata->displaylogs)) {
        $action = 'displaylogs';
    }
    $startdate = $filterdata->startdate ?? strtotime('6 MONTHS AGO');
    $enddate = $filterdata->enddate ?? time();
}

if ($userid > 0) {
    $params['userid'] = $userid;
    $params['action'] = $action;
    $params['startdate'] = $startdate ?? strtotime('6 MONTHS AGO');
    $params['enddate'] = $enddate ?? time();
    if ($params['enddate'] > time()) {
        $params['enddate'] = time();
    }
    $filterform->set_data($params);
}

$PAGE->set_url(new moodle_url('/report/participationlog.php'), $params);
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();

$filterform->display();

if ($action == 'displaylogs') {
    $table = new report_participationlog\tables\participationlog('report_participationlog', $params);
    $table->out(50, true);
    $event = \report_participationlog\event\report_viewed::create([
        'context' => context_user::instance($params['userid']),
        'relateduserid' => $params['userid'],
        'other' => [
            'startdate' => $params['startdate'],
            'enddate' => $params['enddate']
        ]
    ]);
    $event->trigger();
}
if ($action == 'displaychart') {
    $chart = new report_participationlog\partchart($params);
    $chart->print_chart();
    $event = \report_participationlog\event\chart_viewed::create([
        'context' => context_user::instance($params['userid']),
        'relateduserid' => $params['userid'],
        'other' => [
            'startdate' => $params['startdate'],
            'enddate' => $params['enddate']
        ]
    ]);
    $event->trigger();
}

echo $OUTPUT->footer();
