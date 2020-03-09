<?php
// This file is part of Exastats plugin for Moodle
//
// (c) 2017 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Eportfolio is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

namespace block_exastats\task;
defined('MOODLE_INTERNAL') || die();

/**
 * Simple task to delete user accounts for users who have not completed their profile in time.
 */
class tempuser_cleanup_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('tempusercleanuptask', 'block_exastats');
    }

    public function execute() {
        global $CFG, $DB;

        // Delete users who have 999999 in the name.
        $cuttime = time() - (24 * 3600);
        $rs = $DB->get_recordset_sql('SELECT * FROM {user} WHERE username LIKE "99999%" AND timecreated < ? ', array($cuttime));
        foreach ($rs as $user) {
            delete_user($user);
            mtrace(" Deleted temporary user $user->username ($user->id) ");
            // The function delete_user deletes user by rename username and deleted=0
            // We need to delete it also as real deleting
            $DB->delete_records('user', array('id' => $user->id));
        }
        $rs->close();
    }

}
