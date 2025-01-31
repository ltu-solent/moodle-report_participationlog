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
 * Test external functions
 *
 * @package   report_participationlog
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_participationlog\external;

use advanced_testcase;
use context_system;
use Exception;

/**
 * Test external functions.
 */
final class get_relevant_users_test extends advanced_testcase {

    /**
     * Reset DB after running
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }
    /**
     * Search users test.
     *
     * @covers \report_participationlog\external::get_relevant_users
     * @return void
     */
    public function test_get_relevant_users(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $manager = $generator->create_user(['firstname' => 'Manager', 'lastname' => 'One']);
        $student1 = $generator->create_user(['firstname' => 'Amelia', 'lastname' => 'Aardvark']);
        $student2 = $generator->create_user(['firstname' => 'Amelia', 'lastname' => 'Beetle']);
        $generator->create_user(['firstname' => 'Zebedee', 'lastname' => 'Boing']);
        // The role_assign function doesn't take a string value until M4.1.
        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        $generator->role_assign($managerroleid, $manager->id, context_system::instance());

        $this->setUser($manager);
        $result = get_relevant_users::clean_returnvalue(
            get_relevant_users::execute_returns(),
            get_relevant_users::execute('Amelia')
        );
        $this->assertEquals([
            $student1->id,
            $student2->id,
        ], array_column($result, 'id'));

        // Only those with the permission to search should be able to.
        $this->setUser($student1);
        $result = [];
        try {
            $result = get_relevant_users::clean_returnvalue(
                get_relevant_users::execute_returns(),
                get_relevant_users::execute('Amelia')
            );
        } catch (Exception $ex) {
            $this->assertEquals(
                'Sorry, but you do not currently have permissions to do that (View participation log for anyone).',
                $ex->getMessage());
        } finally {
            $this->assertNotEquals([
                $student1->id,
                $student2->id,
            ], array_column($result, 'id'));
        }
    }
}
