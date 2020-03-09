<?php

function xmldb_block_exastats_install() {
	global $DB,$CFG;
	$dbman = $DB->get_manager();
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

	return true;
}
