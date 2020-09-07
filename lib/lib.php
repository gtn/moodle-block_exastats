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

defined('MOODLE_INTERNAL') || die();

/**
 * wrote own function, so eclipse knows which type the output renderer is
 * @return block_exastats_renderer
 */
function block_exastats_get_renderer() {
	global $PAGE;

	return $PAGE->get_renderer('block_exastats');
}

function block_exastats_require_login($courseid, $role = null) {
	global $CFG;

	require_login($courseid);
	require_capability('block/exastats:use', context_system::instance());

}

// get role by username
// for example:
// 401660 is a director of the school
// 401660-mutsw is a teacher
function block_exastats_get_role_byusername() {
	global $USER;

	// is admin?
	$adminIds = array_keys(get_admins());
	if (in_array($USER->id, $adminIds))
		return 'admin';
	$username = trim($USER->username);
	//echo '<span style="display:none;">username:'.$username.'</span>';
	if (!$username)
		return false;
	$userD = explode('-', $username);
	//echo '<span style="display:none;">arr:'.print_r($userD, true).'</span>';
	if (isset($userD[2]) || (!isset($userD[2]) && isset($userD[1]))) // NNNNN-GROUP-RANDOM or NNNNN-RANDOM
		return 'common'; // teachers and students
	if ($userD[0] && is_numeric($userD[0]))
		return 'director';
	if ($userD)
		return 'anonymous';
}

function block_exastats_get_schoolid_byusername($username = '') {
	global $USER;
	if (!$username)
		$username = trim($USER->username);
	if (!$username)
		return false;
	$userD = explode('-', $username);
	if ($userD[0] && is_numeric($userD[0]))
		return $userD[0];
	return 0;
}

function block_exastats_get_schools_byfilter($filter) {
	global $DB, $tableCities, $tableSchools, $schultypen;
	$addWhere = '';
	$arguments = array();
	// region filters
	$prefixes = array();
	if ($filter['regions'] && count($filter['regions']) > 0) {
		foreach($filter['regions'] as $region)
			$prefixes[] = ' schulkennzahl LIKE \''.$region.'%\' ';
	}
	// bezirk filters
	// add bezirk in prefix
	// it is possible only for one selected region
	if ($filter['bezirk'] && count($filter['bezirk']) > 0 && count($prefixes) == 1) {
		$bezPrefixes = array();
		$regionPrefix = substr($prefixes[0], 0, -3);
		foreach ($filter['bezirk'] as $bez) {
			$bezPrefixes[] = $regionPrefix.$bez.'%\' ';
		}
	} else {
		$bezPrefixes = $prefixes;
	}

/*	if ($filter['regions'] && count($filter['regions']) > 0) {
		list ($cond, $arg) = $DB->get_in_or_equal($filter['regions']);
		$addWhere .= ' AND c.bundesland '.$cond.' ';
		$arguments = array_merge($arguments, $arg);
	}
	// get zips by region
	$sql = 'SELECT DISTINCT * 
				FROM '.$tableCities.' c 
				 WHERE 1=1 '.$addWhere;
	$zips = array_keys($DB->get_records_sql($sql, $arguments));
	$zips = array_filter(array_map('intval', $zips));*/
	$schoolids = array();
	$arguments = array();
	//list ($cond, $arg) = $DB->get_in_or_equal($zips);
	//$addWhere .= ' AND s.zip '.$cond.' ';
	//$arguments = array_merge($arguments, $arg);
	// school filters
	$addWhereST = array();
	if ($filter['schoolTypes'] && count($filter['schoolTypes']) > 0) {
		//list ($cond, $arg) = $DB->get_in_or_equal($filter['schoolTypes']);
		//$addWhere .= ' AND s.`Primär-Schulart` '.$cond.' ';
		//$arguments = array_merge($arguments, $arg);
		$convertedST = array();
		foreach ($filter['schoolTypes'] as $st)
			$convertedST = array_merge($convertedST, $schultypen[$st]);
		$addWhereST = array();
		foreach ($convertedST as $stIndex) {
			if ($stIndex != '') {
				// field value like '14 - Sonstige Bildungseinrichtung'
				// where 14 => $st
				//$addWhereST[] = ' `Primär-Schulart` LIKE \''.$stIndex.' - %\' ';
				//$addWhereST[] = ' schulart LIKE \''.$stIndex.' - %\' ';
				$addWhereST[] = ' schultyp = \''.$stIndex.'\' ';
			}
		}
	}
	// change prefixes to full Like conditions if the schooltypes is selected
	/*if ($filter['schoolTypes'] && count($filter['schoolTypes']) > 0) {
		$newPrefixes = array();
		if (count($bezPrefixes) > 0) {
			foreach ($bezPrefixes as $bP) {
				$bPrefix = substr($bP, 0, -2);
				foreach ($filter['schoolTypes'] as $st) {
					$newPrefixes[] = $bPrefix.$st.'\' ';  // make: YXX%Z => Y - region; XX - bexirk, Z - schooltype
				}
			}
		} else {
			foreach ($filter['schoolTypes'] as $st) {
				$newPrefixes[] = ' schulkennzahl LIKE \'%'.$st.'\' ';  // make new LIKE conditions: %Z => Z - schooltype
			}
		}
		$bezPrefixes = $newPrefixes;
	}*/

	if (count($bezPrefixes) > 0)
		$addWhere .= ' AND ('.implode(' OR ', $bezPrefixes).') ';
	if (count($addWhereST) > 0)
		$addWhere .= ' AND ('.implode(' OR ', $addWhereST).') ';
	if ($filter['schoolnumber'] > 0) {
		// use ONLY schoolnumber !!
		$addWhere = ' AND schulkennzahl = ? ';
		$arguments = array($filter['schoolnumber']);
	}


	// get schoolids by zips and additional where
	$sql = 'SELECT DISTINCT * 
			FROM '.$tableSchools.' s 
			 WHERE 1=1 '.$addWhere;
	//echo $sql; exit;
	$schoolids = array_keys($DB->get_records_sql($sql, $arguments));
	return $schoolids;
}

function block_exastats_get_groupusers($schoolids = null, $onlyIds = false, $addFilters = array()) {
	global $DB;
	$users = array();
	if ($schoolids && !is_array($schoolids))
		$schoolids = array($schoolids);
	if (is_array($schoolids) && count($schoolids) > 0) {
		$join = '';
		$addWhere = '';
		// for single user
		// $sql = 'SELECT * FROM {user} WHERE username LIKE \''.intval($schoolid).'-%\' AND deleted = 0 AND confirmed = 1';
		// for many users - bad speed!!
		//$inClauseArr = preg_filter('/$/', '-(.*)', $schoolids); // XXXXX-(.*)  add '-(.*)'
		//$inClauseArr = preg_filter('/^/', '^', $inClauseArr); // ^XXXXX-(.*)  add '^'
		//$sql = 'SELECT * FROM {user} WHERE username REGEXP \''.implode('|', $inClauseArr).'\' AND deleted = 0 AND confirmed = 1';
		// for many users
		$likeClauseArr = array();
		foreach ($schoolids as $sId) {
		    if (isset($addFilters['group']) && $addFilters['group'] != '') {
                $likeClauseArr[] = ' u.username LIKE \''.$sId.'-'.md5($addFilters['group']).'-%\' ';
            } else {
		        $likeClauseArr[] = ' u.username LIKE \''.$sId.'-%\' ';
            }
		}
		// add additional filters
		if (count($addFilters) > 0) {
			// gender
			if (isset($addFilters['gender']) && count($addFilters['gender']) > 0 && array_filter($addFilters['gender'])) {
				//$fieldid = block_exastats_get_user_info_field_id('gender');
				$questionid = block_exastats_get_questionid_from_questionnaire('Geschlecht');
				if ($questionid > 0) {
					$join .= '  JOIN {questionnaire_response} resp ON resp.username = u.id AND resp.complete = \'y\'
								LEFT JOIN {questionnaire_resp_single} respGender ON respGender.response_id = resp.id AND respGender.question_id = '.$questionid;
					$tempWhere = ' respGender.choice_id IN (\''.implode('\',\'', $addFilters['gender']).'\')';
					if (in_array('noSelected', $addFilters['gender'])) {
						$tempWhere .= ' OR respGender.choice_id IS NULL OR respGender.choice_id=\'\'';
					}
					$addWhere .= ' AND ('.$tempWhere.') ';
				}
			}
			// years on the job
			if (isset($addFilters['yearsonjob']) && count($addFilters['yearsonjob']) > 0 && array_filter($addFilters['yearsonjob'])) {
				$questionid = block_exastats_get_questionid_from_questionnaire('Schuldienst');
				if ($questionid > 0) {
					$join .= '  JOIN {questionnaire_response} resp2 ON resp2.username = u.id AND resp2.complete = \'y\'
								LEFT JOIN {questionnaire_resp_single} respYearsonjob ON respYearsonjob.response_id = resp2.id AND respYearsonjob.question_id = '.$questionid;
					$tempWhere = ' respYearsonjob.choice_id IN (\''.implode('\',\'', $addFilters['yearsonjob']).'\')';
					if (in_array('noSelected', $addFilters['yearsonjob'])) {
						$tempWhere .= ' OR respYearsonjob.choice_id IS NULL OR respYearsonjob.choice_id=\'\'';
					}
					$addWhere .= ' AND ('.$tempWhere.') ';
				}
				/*$fieldid = block_exastats_get_user_info_field_id('yearsonjob');
				if ($fieldid > 0) {
					preg_match('/([<>=]*)(\d+)/', $addFilters['yearsonjob'], $matches);
					if ($matches[1] && $matches[2] > 0) {
						$join .= ' LEFT JOIN {user_info_data} udYearsonjob ON udYearsonjob.userid = u.id AND udYearsonjob.fieldid = '.$fieldid;  // 5 - id in `mdl_user_info_field`
						switch ($matches[1]) {
							case '>':
							case '<':
							case '>=':
							case '<=':
							case '=>':
							case '=<':
							case '=':
								$addWhere .= ' AND udYearsonjob.data '.$matches[1].' '.intval($matches[2]);
								break;
							default:
								$addWhere .= ' AND udYearsonjob.data = '.intval($matches[2]);
						}
					}
				}*/
			}
		}
		$sql = 'SELECT DISTINCT u.* FROM {user} u '.
						 $join.'
						 WHERE ('.implode(' OR ', $likeClauseArr).') AND u.deleted = 0 AND u.confirmed = 1'.
						 		$addWhere;
		//echo $sql; exit;
		$users = $DB->get_records_sql($sql);
	}
	if ($onlyIds && count($users) > 0) {
		$users = array_keys($users);
	}
	return $users;
}

/**
 * get categories from questionnair
 */
function block_exastats_get_categories($courseid = null) {
	global $DB;
	$categories = array();
	// get categories from mdl_quiz
	$params = [];
	if ($courseid)
		$params['course'] = intval($courseid);
	$recs = $DB->get_records('quiz', $params, 'name');
	foreach ($recs as $rec) {
		if (!in_array($rec->name, $categories)) {
			$key = substr($rec->name, 0, 1); // get name from name of quiz, for axample A = A. Digitale Kompetenzen und
			$categories[$key] = $rec->name;
		}
	}
	return $categories;
}


/**
 * @param int $courseid
 * @param string $category
 * @param mixed $users
 * @return array
 */
function block_exastats_get_quizresults_by_category($courseid, $category, $users) {
	global $DB, $CFG, $PAGE;
	if (!is_array($users))
		$users = array($users);
	$result = array();
	require_once($CFG->dirroot . '/mod/quiz/locallib.php');

	if (count($users) > 0) {
		$sql = 'SELECT * FROM {quiz} WHERE course = :courseid AND name LIKE \''.substr($category, 0, 1).'.%\'';

		$params = array('courseid' => $courseid);
		if ($records = $DB->get_records_sql($sql, $params)) {

			foreach ($records as $quiz) {
				$questions = array();
				$sql_questions = '
				SELECT DISTINCT CONCAT(q.id, \'_\', qa.userid, \'_\', qs.slot, \'_\', questatt.timemodified) as uniqueid, q.id as questionid, q.name as questionname, q.questiontext as questiontext, 
								questatt.questionsummary  as questionsummary, questatt.responsesummary as responsesummary, questatt.rightanswer as response_correct,
								questatt.questionsummary as questionsummary, questatt.flagged as flagged,
								questattstep.state as state,
								qs.maxmark as maxmark, qs.slot as slot,
								qa.userid as userid,
								questatt.timemodified as timemodified /*, MAX(questatt.timemodified) as lasttimemodified*/
						FROM {quiz_attempts} qa
							JOIN {quiz_slots} qs ON qs.quizid = qa.quiz
							JOIN {question} q ON q.id = qs.questionid
							JOIN {question_attempts} questatt ON questatt.slot = qs.slot AND questatt.questionid = q.id 
							JOIN {question_attempt_steps} questattstep ON questattstep.questionattemptid = questatt.id  AND questattstep.userid = qa.userid AND questattstep.state LIKE \'graded%\'
						WHERE qa.quiz = ? AND qa.state = \'finished\' AND qa.userid IN ('.implode(',', $users).')
						ORDER BY questatt.timemodified DESC
						# GROUP BY qa.id, qs.slot, qa.userid
						# HAVING timemodified = lasttimemodified
						';
				//echo $quiz->id.'--<br>';
				//echo $sql_questions.'<br><br>'; exit;
				$questionsRes = $DB->get_records_sql($sql_questions, [$quiz->id]);
				$distinctArr = array();
				//echo '<pre>';print_r($questionsRes);echo '</pre>';
				foreach ($questionsRes as $quest) {
					$uniq = $quest->questionid.'_'.$quest->userid.'_'.$quest->slot.'_'.$quest->timemodified;
					if (in_array($uniq, $distinctArr))
						continue;
					$distinctArr[] = $uniq;
					//echo substr($category, 0, 1).' == '.$quest->questionid.'==<br>';
					$question = array(
						'id' => $quest->questionid,
						'name' => $quest->questionname,
						'questiontext' => strip_tags($quest->questiontext),
						'slot' => $quest->slot,
						'flagged' => $quest->flagged,
						'question_summary' => $quest->questionsummary,
						'state' => $quest->state,
						'status' => $quest->state,
						//'status' = $attemptobj->get_question_status($slot, $displayoptions->correctness);
						'response' => $quest->responsesummary,
						'response_correct' => $quest->response_correct,
						'maxmark' => $quest->maxmark,
						'userid' => $quest->userid
						//'mark' => $quest['mark']
					);

					$questions[] = $question;
				}
				$result['questions'] = $questions;
				//$result['bestgrade'] = number_format(quiz_get_best_grade($quiz, $userid), 2);
				//$result['grade'] = number_format($quiz->grade, 2);// $attemptobj->get_sum_marks();
			}
			/* it is working, but very slow */
			// TODO: it is only one quiz?
			/*		foreach ($records as $quiz) {
						$questions = array();
						$attemptobj = null;
						$cm = get_coursemodule_from_instance('quiz', $quiz->id, $courseid);
						$quizobj = quiz::create($cm->instance, $userid);
						$quiz = $quizobj->get_quiz();
						// TODO: now it is only for one attempt or ok?
						$attempts = quiz_get_user_attempts($quiz->id, $userid, 'finished', true);
						foreach ($attempts as $id => $att) {
							$attemptobj = quiz_attempt::create($id);
							if ($attemptobj->is_finished())
								break; // stop on first finished attempts or return last not finished
						}

						if (!$attemptobj instanceof quiz_attempt)
							continue;
						$displayoptions = $attemptobj->get_display_options(true);
						$slots = $attemptobj->get_slots();

						foreach ($slots as $slot) {
							$question = array(
								'id' => $attemptobj->get_question_attempt($slot)->get_question()->id,
								'name' => $attemptobj->get_question_name($slot),
								'questiontext' => strip_tags($attemptobj->get_question_attempt($slot)->get_question()->questiontext),
								'slot' => $slot,
								//'type' => $attemptobj->get_question_type_name($slot),
								//'page' => $attemptobj->get_question_page($slot),
								'flagged' => $attemptobj->is_question_flagged($slot),
								//'html' => $attemptobj->render_question($slot, $review, $renderer) . $PAGE->requires->get_end_code(),
								//'sequencecheck' => $attemptobj->get_question_attempt($slot)->get_sequence_check_count(),
								//'lastactiontime' => $attemptobj->get_question_attempt($slot)->get_last_step()->get_timecreated(),
								//'hasautosavedstep' => $attemptobj->get_question_attempt($slot)->has_autosaved_step()
								'question_summary' => $attemptobj->get_question_attempt($slot)->get_question_summary()
							);
							if ($attemptobj->is_real_question($slot)) {
								//$question['number'] = $attemptobj->get_question_number($slot);
								$question['state'] = (string) $attemptobj->get_question_state($slot);
								$question['status'] = $attemptobj->get_question_status($slot, $displayoptions->correctness);
								//$question['blockedbyprevious'] = $attemptobj->is_blocked_by_previous_question($slot);
								$question['response'] = $attemptobj->get_question_attempt($slot)->get_response_summary();
								$question['response_correct'] = $attemptobj->get_question_attempt($slot)->get_correct_response();
							}
							if ($displayoptions->marks >= question_display_options::MAX_ONLY) {
								$question['maxmark'] = $attemptobj->get_question_attempt($slot)->get_max_mark();
							}
							if ($displayoptions->marks >= question_display_options::MARK_AND_MAX) {
								$question['mark'] = $attemptobj->get_question_mark($slot);
							}
							//print_r($question); echo '<br><br>';
							$questions[] = $question;
						}

						$result['questions'] = $questions;
						$result['bestgrade'] = number_format(quiz_get_best_grade($quiz, $userid), 2);
						$result['grade'] = number_format($quiz->grade, 2);// $attemptobj->get_sum_marks();
					}*/

		}
	}
	return $result;
}


/**
 * @param $courseid
 * @param $category
 * @param $userid
 * @return array
 * @deprecated
 */
function block_exastats_get_quizresults_by_category2($courseid, $category, $userid) {
	global $DB, $CFG, $PAGE;
	require_once $CFG->dirroot.'/question/engine/questionusage.php';
	//$qoutput = $PAGE->get_renderer('core', 'question');
	//echo $qoutput->;
	$result = array();
	$sql = 'SELECT * FROM {quiz} WHERE course = :courseid AND name LIKE \''.substr($category, 0, 1).'.%\'';
	$params = array('courseid' => $courseid);
	if ($records = $DB->get_records_sql($sql, $params)) {
		foreach ($records as $quiz) {
			// get slots => get quiestions
			//$quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quiz);
			//$slots = $quba->get_slots();
			$sql_questions = 'SELECT q.* 
								FROM {quiz_slots} s
									LEFT JOIN {question} q ON q.id = s.questionid 
								WHERE quizid = :quizid';
			$params = array('quizid' => $quiz->id);
			$questions = $DB->get_records_sql($sql_questions, $params);
			foreach ($questions as $question) {
				$result[$question->id]['name'] = $question->questiontext;
				// get answers
				//$result[$question->id]['answers'] = array();
				$sql_answers = 'SELECT * FROM {question_answers} WHERE question = :questionid';
				$answ_params = array('questionid'=>$question->id);
				$answers = $DB->get_records_sql($sql_answers, $answ_params);
				$result[$question->id]['answers'] = $answers;
				// get user answers on question
				//$question
				$result[$question->id]['result'] = $question->questiontext;
			}
		}

		//get_question
		//$quizes = $records;
	}
	return $result;
}

function block_exastats_get_questionnaireresults_by_category($courseid, $category, $userids) {
	global $DB, $CFG, $PAGE;
	$result = array('questions'=>[]);
	if (!is_array($userids))
		$userids = array($userids);
	if (count($userids) > 0) {
		require_once $CFG->dirroot.'/mod/questionnaire/questionnaire.class.php';
		//require_once $CFG->dirroot.'/mod/questionnaire/classes/response/base.php';
		//$respBase = new mod_questionnaire\response\base();
		$sql = 'SELECT qr.* FROM {questionnaire} qr 					
					WHERE qr.course = :courseid ';
		$params = array('courseid' => $courseid);
		if ($qrrs = $DB->get_records_sql($sql, $params)) {
		    $rankfieldname = 'rank';
            if ($CFG->version >= 2018050104) {
                $rankfieldname = 'rankvalue';
            }
			// list of questionnairs for course
			foreach ($qrrs as $questionnair) {
				$sql = 'SELECT DISTINCT CONCAT_WS(\'_\', q.id, u.id, qr.id) as uiniq, q.id as qid, q.name as qname, q.content as qcontent,
 									qrr.'.$rankfieldname.' as qrrrank, u.id as uid,
 									qr.id as qrid, qr.submitted as submitted
						 FROM {questionnaire_response} qr 
								JOIN {questionnaire_response_rank} qrr ON qrr.response_id = qr.id AND qr.survey_id = ? AND qr.complete = \'y\' 
								LEFT JOIN {user} u ON u.id = CAST(qr.username AS SIGNED) 
								JOIN {questionnaire_question} q ON q.id = qrr.question_id
						WHERE qr.username IN ('.implode(',', $userids).') AND q.name LIKE \''.substr($category, 0, 1).'%\' 
						ORDER BY q.id, u.id, qr.submitted DESC';
				// may be with HEAVING?
/*				SELECT DISTINCT CONCAT_WS('_', q.id, u.id, qr.id) as uiniq, q.id as qid, q.name as qname, q.content as qcontent, qrr.rank as qrrrank, u.id as uid, qr.id as qrid, qr.submitted as qrsubmitted, MAX(qr.submitted) as lasttimesubmitted
FROM mdl_questionnaire_response qr
JOIN mdl_questionnaire_response_rank qrr ON qrr.response_id = qr.id
LEFT JOIN mdl_user u ON u.id = CAST(qr.username AS SIGNED)
JOIN mdl_questionnaire_question q ON q.id = qrr.question_id
WHERE
qr.survey_id = 4 AND qr.complete = 'y'
AND
qr.username IN (6188,6189,6190,6191,6192,6193,6194,6195,6196,6197,6198,6199,6200,6201,6202,6203,6204,6205,6206,6207,6208,6209,6210,6211,6212,6213,6214,6215,6216,6217,6218,6219,6220,6221,6222,6223,6224,6225,6226,6227,6228,6229,6230,6231,6232,6233,6234,6235,6236,6237,6238,6239,6240,6241,6242,6243,6244,6245,6246,6247,6248,6249,6250,6251,6252,6253,6254,6255,6256,6257,6258,6259,6260,6261,6262,6263,6264,6265,6266,6267,6268,6269,6270,6271,6272,6273,6274,6275,6276,6277,6278,6279,6280,6281,6282,6283,6284,6285,6286,6287,6288,6289,6290,6291,6292,6293,6294,6295,6296,6297,6298,6299,6300,6301,6302,6303,6304,6305,6306,6307,6308,6309,6310,6311,6312,6313,6314,6315,6316,6317,6318,6319,6320,6321,6322,6323,6324,6325,6326,6327,6328,6329,6330,6331,6332,6333,6334,6335,6336,6337,6338,6339,6340,6341,6342,6353,6354,6355,6356,6357,6358,6359,6360,6361,6362,6363,6364,6365,6366,6367,6368,6369,6370,6371,6372,6373,6374,6375,6376,6547,6548,6549,6550,6551,6552,6553,6554,6555,6556,6557,6558,6559,6560,6561,6562,6563,6564,6565,6566,6724,6725,6726,6727,6728,6729,6730,6731,6732,6733,7959,7960,7961,7962,7963,7964,7965,7966,7967,7968,7969,7970,7971,7972,7973,7974,7975,7976,7977,7978,7979,7980,7981,7982,7983,7984,8336,8337,8338,8339,8340,8341,8342,8343,8344,8345,8781,8782,8783,8784,8785,8786,8787,8788,8789,8790,8791,8792,8793,8794,8795,8796,8797,8798,8799,8800,9527,9528,9529,9530,9531,9532,9533,9534,9535,9536,9537,9538,9539,9540,9541,9542,9543,9544,9545,9546,9547,9548) AND q.name LIKE 'A%'
GROUP BY qr.username, q.id
HAVING qrsubmitted = lasttimesubmitted*/
				//echo $questionnair->id.'--';
				//echo $sql.'<br>'; exit;
				$questions = $DB->get_records_sql($sql, array($questionnair->id));
				$alreadyasked = array();
				foreach ($questions as $q) {
					$uniqId = $q->qid.'_'.$q->uid; // question id + user id
					if (in_array($uniqId, $alreadyasked))
						continue;
					$alreadyasked[] = $uniqId;
					$question = [];
					$question['content'] = strip_tags($q->qcontent);
					$question['id'] = $q->qid;
					$question['response'] = $q->qrrrank;
					$question['userid'] = $q->uid;
					$result['questions'][] = $question;
				}
			}
		}
	}
	return $result;
}

function block_exastats_get_questionnaireresults_by_category2($courseid, $category, $userid) {
	global $DB, $CFG, $PAGE;
	$result = array();
	require_once $CFG->dirroot.'/mod/questionnaire/questionnaire.class.php';
	//require_once $CFG->dirroot.'/mod/questionnaire/classes/response/base.php';
	//$respBase = new mod_questionnaire\response\base();
	$sql = 'SELECT q.* FROM {questionnaire_question} q
 					JOIN {questionnaire} qr ON qr.id = q.survey_id
					WHERE qr.course = :courseid AND q.name LIKE \''.substr($category, 0, 1).'.%\'';
	$params = array('courseid' => $courseid);
	if ($records = $DB->get_records_sql($sql, $params)) {
		foreach ($records as $qr_question) {
			//$user_responses = questionnaire_get_user_responses($qr_question->survey_id, $userid, true);
			$question = [];
			$question['content'] = strip_tags($qr_question->content);
			$question['id'] = $qr_question->id;
			$cm = get_coursemodule_from_instance('questionnaire', $qr_question->survey_id, $courseid);
			$questionnairobj = new questionnaire($qr_question->survey_id, null, $courseid, $cm);
			$qr_questions = $questionnairobj->questions;
			$qq = array_shift($qr_questions);
			$user_responses_sql = $qq->response->get_bulk_sql($qr_question->survey_id, false, $userid, false);// ->get_results();
			$user_responses_sql[0] .= ' AND question_id = ? ';
			$user_responses_sql[1][] = $qr_question->id;
			print_r($user_responses_sql); echo '<br><br>';
			$user_response = $DB->get_records_sql($user_responses_sql[0], $user_responses_sql[1]);
			if ($user_response) {
				$user_response_last = end($user_response);
				$question['response'] = $user_response_last->rank;
			} else
				$question['response'] = null;
			$result['questions'][] = $question;
		}
	}
	return $result;
}

function block_exastats_get_questionnaire_short_results($courseid, $users = null) {
	$categories = block_exastats_get_categories($courseid);
	if (!$users) {
		$schoolid = block_exastats_get_schoolid_byusername();
		if ($schoolid > 0)
			$users = block_exastats_get_groupusers($schoolid, true);
		else
			$users = array();
	}
	$result = array();
	foreach ($categories as $categoryKey => $categoryName) {
		$result[$categoryKey] = array();
		$result[$categoryKey]['questions'] = array();
		$result[$categoryKey]['category_name'] = $categoryName;
		$result[$categoryKey]['ranks'] = array_fill_keys(array(1, 2, 3, 4), '');
		//foreach ($users as $userid) {
			$q_result = block_exastats_get_questionnaireresults_by_category($courseid, $categoryKey, $users);
			//echo '<pre>';print_r($q_result);echo '</pre>';
			if (isset($q_result['questions'])) {
				$result[$categoryKey]['questions'] = $q_result['questions'];
				foreach ($q_result['questions'] as $question) {
					$result[$categoryKey]['ranks'][$question['response'] + 1] += 1;
				}
			}
		//}
		$total_sum_ranks = 0;
		$koef = array(0, 100, 66.66, 33.33, 0);
		$total_count_users = 0;
		foreach ($result[$categoryKey]['ranks'] as $rankKey => $rankCount) {
			$total_sum_ranks += $koef[$rankKey] * $rankCount;
			$total_count_users += $rankCount;
		}
		if ($total_count_users > 0)
			$result[$categoryKey]['quiestionnairrank'] = $total_sum_ranks / $total_count_users;
		else
			$result[$categoryKey]['quiestionnairrank'] = 0;
	}
	return $result;
}

function block_exastats_get_questionnaire_short_results2($courseid) {
	$categories = block_exastats_get_categories($courseid);
	$schoolid = block_exastats_get_schoolid_byusername();
	$users = block_exastats_get_groupusers($schoolid, true);
	$result = array();
	foreach ($categories as $categoryKey => $categoryName) {
		$result[$categoryKey] = array();
		$result[$categoryKey]['category_name'] = $categoryName;
		$result[$categoryKey]['ranks'] = array_fill_keys(array(1, 2, 3, 4), '');
		foreach ($users as $userid) {
			$q_result = block_exastats_get_questionnaireresults_by_category($courseid, $categoryKey, $userid);
			foreach ($q_result['questions'] as $question) {
				$result[$categoryKey]['ranks'][$question['response']+1] += 1;
			}
		}
		$total_sum_ranks = 0;
		foreach ($result[$categoryKey]['ranks'] as $rank) {
			$total_sum_ranks += $rank;
		}
		$result[$categoryKey]['globalrank'] = $total_sum_ranks / count(array_keys($result[$categoryKey]['ranks']));
	}
	return $result;
}

function block_exastats_show_lastmessage($text, $course, $class='danger') {
	global $OUTPUT;
	$content = $OUTPUT->header();
	$content .= '<div class="alert alert-'.$class.'">'.$text.'</div>';
	$content .= $OUTPUT->footer($course);
	return $content;
}


function block_exastats_getansweredQuestionnaire_schools($courseid, $userids) {
	global $DB;
	$result = array();
	if (count($userids) > 0) {
		$sql = 'SELECT DISTINCT u.username
 					FROM {questionnaire} q 
						JOIN {questionnaire_response} qr ON qr.survey_id = q.id						   
						LEFT JOIN {user} u ON u.id = CAST(qr.username AS SIGNED) 								
						WHERE q.course = ? AND qr.complete = \'y\' AND qr.username IN ('.implode(',', $userids).')  
						ORDER BY u.username';
		$users = $DB->get_records_sql($sql, array($courseid));
		foreach ($users as $username) {
			$schoolid = block_exastats_get_schoolid_byusername($username->username);
			if (!in_array($schoolid, $result))
				$result[] = $schoolid;
		}
	}
	return $result;
}

function block_exastats_getansweredQuiz_schools($courseid, $userids) {
	global $DB;
	$result = array();
	if (count($userids) > 0) {
		$sql = 'SELECT DISTINCT u.username
 					FROM {quiz} q 
 						JOIN {quiz_attempts} qa ON qa.quiz = q.id
						LEFT JOIN {user} u ON u.id = CAST(qa.userid AS SIGNED) 								
						WHERE q.course = ? AND qa.state = \'finished\' AND qa.userid IN ('.implode(',', $userids).') 
						ORDER BY u.username												
						';
		$users = $DB->get_records_sql($sql, array($courseid));
		foreach ($users as $username) {
			$schoolid = block_exastats_get_schoolid_byusername($username->username);
			if (!in_array($schoolid, $result))
				$result[] = $schoolid;
		}
	}
	return $result;
}

/**
 * get id of custom user info field from 'mdl_user_info_field' by shortname
 * get FIRST of found records
 * @param string $fieldname
 * @return integer
 */
function block_exastats_get_user_info_field_id($fieldname = '') {
	global $DB;
	if ($fieldname != '') {
		$res = $DB->get_record('user_info_field', array('shortname' => $fieldname), 'id', IGNORE_MULTIPLE);
		if ($res)
			return $res->id;
	}
	return 0;
}

/**
 * get id of question from questionnairs
 * @param string $target
 * @return integer
 */
function block_exastats_get_questionid_from_questionnaire($target = '') {
	global $DB;
	$result = 0;
	if ($target != '') {
		$question = $DB->get_record('questionnaire_question', array('name' => $target), 'id', IGNORE_MULTIPLE);
		if ($question)
			$result = $question->id;
	}
	return $result;
}

/**
 * get array of variants from questionnairs
 * @param string $target
 * @return integer
 */
function block_exastats_get_variants_from_questionnaire($target = '') {
	global $DB;
	$result = array();
	if ($target != '') {
		$questionid = block_exastats_get_questionid_from_questionnaire($target);
		if ($questionid > 0) {
			$res = $DB->get_records('questionnaire_quest_choice', array('question_id' => $questionid), 'id');
			//print_r($res);
			$result = $res;
		}
	}
	return $result;
}


function block_exastats_short_view_fromstatdata($stat) {
    $content = '';

    $content .= '<h4>'.$stat['header'].'</h4>';
    if ($stat['message'] != '') {
        $content .= '<div class="alert alert-info">'.$stat['message'].'</div>';
    }
    if (count($stat['data']) > 0) {
        $content .= '<table class="block_exastats_summary_ranks">';
        foreach ($stat['data'] as $category) {
            /*$block_content .= '<tr class="category_name">
                                <td valign="top" colspan="2">'.$category['categoryname'].'</td>
                            </tr>';*/
            $content .= '<tr>';
            $content .= '<td valign="middle">
												<span class="bar-name">'.$category['categoryname'].
                    ' <small>('.$category['countquestions'].')</small></span>
											<div class="bar-container">
												<div class="knowledge-bar" style="width: '.number_format($category['averageFraction'], 0).'%;"></div>
											</div>
										</td>
										<td class="percent_value">'.
                    number_format($category['averageFraction'], 0).
                    '%</td>';
            $content .= '</tr>';
        }
        $content .= '</table>';
    }
    return $content;
}

function block_exastats_view_fromstatdata_single_user($courseid, $stat, $userid) {
    global $DB, $PAGE;
    $content = '<div style="margin-top: 35px;">';
    // user data
    $userData = $DB->get_record('user', array('id' => $userid));
    $userpicture = new user_picture($userData);
    $userpicture->courseid = $courseid;
    $userpicture->size = 2;
    //$userpicture->size = 2;
    $imgsrc = $userpicture->get_url($PAGE)->out(false);
    $userDataTable = new html_table();
    $r1 = new html_table_row();
    $t1 = new html_table_cell();
    $t1->style = 'width: 50px;';
    $t1->text = html_writer::link(new moodle_url('/user/view.php', array('id' => $userid, 'courseid' => $courseid)),
            html_writer::img($imgsrc, fullname($userData)),
            array('target' => '_blank')
    );
    $r1->cells[] = $t1;
    $t2 = new html_table_cell();
    $t2->text = html_writer::tag('strong', fullname($userData));
    $r1->cells[] = $t2;
    $userDataTable->data[] = $r1;

    $content .= html_writer::table($userDataTable);

    if ($stat['message'] != '') {
        $content .= '<div class="alert alert-info">'.$stat['message'].'</div>';
    }
    if (count($stat['data']) > 0) {
        $content .= '<table class="block_exastats_summary_ranks">';
        foreach ($stat['data'] as $category) {
            /*$block_content .= '<tr class="category_name">
                                <td valign="top" colspan="2">'.$category['categoryname'].'</td>
                            </tr>';*/
            $content .= '<tr>';
            $content .= '<td valign="middle">
												<span class="bar-name">'.$category['categoryname'].
                    ' <small>('.$category['countquestions'].')</small></span>
											<div class="bar-container">
												<div class="knowledge-bar" style="width: '.number_format($category['averageFraction'], 0).'%;"></div>
											</div>
										</td>
										<td class="percent_value">'.
                    number_format($category['averageFraction'], 0).
                    '%</td>';
            $content .= '</tr>';
        }
        $content .= '</table>';
    }
    $content .= '</div>';
    return $content;
}


function block_exastats_stopOutputWithMessage(&$OUTPUT, $message = '', $courseid = 0) {
    echo $message;
    echo $OUTPUT->footer($courseid);
    return true;
}

function block_exastats_is_user_has_role_in_course($user_id, $course_id, $role = 'student') {
    global $DB;

    $sql = 'SELECT * 
              FROM {role_assignments} AS ra 
                LEFT JOIN {user_enrolments} AS ue ON ra.userid = ue.userid 
                LEFT JOIN {role} AS r ON ra.roleid = r.id 
                LEFT JOIN {context} AS c ON c.id = ra.contextid 
                LEFT JOIN {enrol} AS e ON e.courseid = c.instanceid AND ue.enrolid = e.id 
              WHERE r.shortname = ? 
                AND ue.userid = ? 
                AND e.courseid = ? ';
    $result = $DB->get_records_sql($sql, array($role, $user_id, $course_id));
    if ($result) {
        return true;
    }
    return false;
}


function block_exastats_get_course_students($courseid) {
    $students = array();
    $courseUsers = get_enrolled_users(context_course::instance($courseid));
    foreach ($courseUsers as $user) {
        if (block_exastats_is_user_has_role_in_course($user->id, $courseid)) {
            $students[] = $user->id;
        }
    }
    return $students;
}

