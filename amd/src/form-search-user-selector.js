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
 * Search user selector module.
 *
 * @module report_participationlog/form-search-user-selector
 * @class form-search-user-selector
 * @package
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

export default {
    processResults(selector, results) {
        return results.map(user => ({
            value: user.id,
            label: user.label,
        }));
    },

    transport(selector, query, success, failure) {
        const args = {query};
        const promise = Ajax.call([{methodname: 'report_participationlog_get_relevant_users', args}])[0];
        return promise.done(results => {
            success(results);
        }).fail(failure);
    }
};
