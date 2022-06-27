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
 * API helper for participation log
 *
 * @package   report_participationlog
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_participationlog;

use core_date;
use DateInterval;
use DateTime;

class api {
    /**
     * Start of day
     * @param int $time timestamp
     * @return int start of day
     */
    public static function stats_get_base_daily($time=0) {
        if (empty($time)) {
            $time = time();
        }

        core_date::set_default_server_timezone();
        $time = strtotime(date('d-M-Y', $time));

        return $time;
    }

    /**
     * Start of next day
     * @param int $time timestamp
     * @return start of next day
     */
    public static function stats_get_next_day_start($time) {
        $next = self::stats_get_base_daily($time);
        $nextdate = new DateTime();
        $nextdate->setTimestamp($next);
        $nextdate->add(new DateInterval('P1D'));
        return $nextdate->getTimestamp();
    }
}
