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
 * Filter form for search for user logs
 *
 * @package   report_participationlog
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_participationlog\forms;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

use context_system;
use moodleform;

/**
 * Filter form for searching for users over a time period.
 */
class filter extends moodleform {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $USER;
        $mform =& $this->_form;
        $cansearchusers = has_capability('report/participationlog:view', context_system::instance());
        if ($cansearchusers) {
            $options = [
                'ajax' => 'report_participationlog/form-search-user-selector',
                'multiple' => false,
                'noselectionstring' => get_string('selectuser', 'report_participationlog'),
                'valuehtmlcallback' => function($value) {
                    global $DB, $OUTPUT;
                    $user = $DB->get_record('user', ['id' => (int)$value], '*', IGNORE_MISSING);
                    if (!$user || !user_can_view_profile($user)) {
                        return false;
                    }
                    $details = user_get_user_details($user);
                    return $OUTPUT->render_from_template(
                            'report_participationlog/form-user-selector-suggestion', $details);
                },
            ];
            $mform->addElement('autocomplete', 'userid', get_string('users'), [], $options);
            $mform->addRule('userid', get_string('required'), 'required', null, 'client');
        } else {
            // Set the userid to the logged in user if they cannot search for users
            // because they can only view their own report.
            $mform->addElement('hidden', 'userid', $USER->id);
            $mform->setType('userid', PARAM_INT);
        }

        $thisyear = date('Y');
        $mform->addElement('date_selector', 'startdate', get_string('searchfrom', 'report_participationlog'), [
            'startyear' => $thisyear - 3,
            'stopyear' => $thisyear,
        ]);

        $mform->setDefault('startdate', strtotime('6 MONTHS AGO'));
        $mform->addElement('date_selector', 'enddate', get_string('searchto', 'report_participationlog'), [
            'startyear' => $thisyear - 3,
            'stopyear' => $thisyear,
        ]);

        $mform->addElement('hidden', 'action', 'none');
        $mform->setType('action', PARAM_ALPHA);

        $this->add_display_buttons();
    }

    /**
     * Add display buttons including one each for each type of display
     *
     * @return void
     */
    private function add_display_buttons() {
        $mform =& $this->_form;
        $buttonarray = [];
        $buttonarray[] = &$mform->createElement('submit', 'displaychart', get_string('displaychart', 'report_participationlog'));
        $buttonarray[] = &$mform->createElement('submit', 'displaylogs', get_string('displaylogs', 'report_participationlog'));
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
    }
}
