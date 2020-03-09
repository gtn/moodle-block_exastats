<?php
// This file is part of Exabis Eportfolio
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
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

function xmldb_block_exastats_upgrade($oldversion) {
	global $DB,$CFG;
	$dbman = $DB->get_manager();
	$result=true;

	if ($oldversion < 2017111705) {
		// add indexes
		// questionnaire_response
        if ($dbman->table_exists('questionnaire_response')) {
            $table = new xmldb_table('questionnaire_response');
            $index = new xmldb_index('username', null, ['username']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
            $index = new xmldb_index('complete', null, ['survey_id', 'complete']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
		// user
		$table = new xmldb_table('user');
		$index = new xmldb_index('username', null, ['username']);
		if (!$dbman->index_exists($table, $index))
			$dbman->add_index($table, $index);
		// questionnaire_response_rank
        if ($dbman->table_exists('questionnaire_response_rank')) {
            $table = new xmldb_table('questionnaire_response_rank');
            $index = new xmldb_index('response_id', null, ['response_id']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
            $index = new xmldb_index('question_id', null, ['question_id']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
		// question
		$table = new xmldb_table('question');
		$index = new xmldb_index('name', null, ['name']);
		if (!$dbman->index_exists($table, $index))
			$dbman->add_index($table, $index);
		// quiz_attempts
		$table = new xmldb_table('quiz_attempts');
		$index = new xmldb_index('state', null, ['state']);
		if (!$dbman->index_exists($table, $index))
			$dbman->add_index($table, $index);
		// quiz_slots
		$table = new xmldb_table('quiz_slots');
		$index = new xmldb_index('slot', null, ['slot']);
		if (!$dbman->index_exists($table, $index))
			$dbman->add_index($table, $index);
		// question_attempts
		$table = new xmldb_table('question_attempts');
		$index = new xmldb_index('slot', null, ['slot']);
		if (!$dbman->index_exists($table, $index))
			$dbman->add_index($table, $index);
		// question_attempt_steps
		$table = new xmldb_table('question_attempt_steps');
		$index = new xmldb_index('att_user_state', null, ['questionattemptid', 'userid', 'state']);
		if (!$dbman->index_exists($table, $index))
			$dbman->add_index($table, $index);

	}

   	return $result;
}
