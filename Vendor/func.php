<?php


function get_string($key, $inst = '') {
	global $langString;
	return $langString[$key];
}

/**
 * get categories from questionnair
 */
function blockVendor_exastats_get_categories($courseid = null) {
	global $mysqli;
	$categories = array();
	// get categories from mdl_quiz
	$addWhere = '';
	if ($courseid)
		$addWhere .= ' AND course = '.intval($courseid).' ';
	$sql = 'SELECT * FROM mdl_quiz WHERE 1=1 '.$addWhere.' ORDER BY name';
	$recs = $mysqli->query($sql);
	if ($recs)
	while($rec = $recs->fetch_assoc()) {
		if (!in_array($rec['name'], $categories)) {
			$key = substr($rec['name'], 0, 1); // get name from name of quiz, for axample A = A. Digitale Kompetenzen und
			$categories[$key] = $rec['name'];
		}
	}
	//echo '<pre style="display: none;">categories:';
	//print_r($categories);
	//echo '</pre>';
	return $categories;
}

function blockVendor_exastats_get_schools_byfilter($filter) {
	global $mysqli, $tableSchools, $schultypen;
	$schoolids = array();
	$addWhere = '';
	// region filters
	$prefixes = array();
	if ($filter['regions'] && count($filter['regions']) > 0) {
		foreach($filter['regions'] as $region)
			$prefixes[] = ' schulkennzahl LIKE \''.$region.'%\' ';
	}
	// bezirk filters
	// add bezirk in prefix
	// it is possible only for one selected region
	if (isset($filter['bezirk']) && count($filter['bezirk']) > 0 && count($prefixes) == 1) {
		$bezPrefixes = array();
		$regionPrefix = substr($prefixes[0], 0, -3);
		foreach ($filter['bezirk'] as $bez) {
			$bezPrefixes[] = $regionPrefix.$bez.'%\' ';
		}
	} else {
		$bezPrefixes = $prefixes;
	}

	// school filters
	$addWhereST = array();
	if (isset($filter['schoolTypes']) && count($filter['schoolTypes']) > 0) {
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
	if (count($bezPrefixes) > 0)
		$addWhere .= ' AND ('.implode(' OR ', $bezPrefixes).') ';
	if (count($addWhereST) > 0)
		$addWhere .= ' AND ('.implode(' OR ', $addWhereST).') ';
	if (isset($filter['schoolnumber']) && $filter['schoolnumber'] > 0) {
		// use ONLY schoolnumber !!
		$addWhere = ' AND schulkennzahl = \''.intval($filter['schoolnumber']).'\' ';
	}

	// get schoolids by zips and additional where
	$sql = 'SELECT DISTINCT * 
			FROM '.$tableSchools.' s 
			 WHERE 1=1 '.$addWhere;
	//echo $sql; exit;
	//$schoolids = array_keys($DB->get_records_sql($sql, $arguments));
	$recs = $mysqli->query($sql);
	if ($recs)
	while($rec = $recs->fetch_assoc()) {
		$schoolids[] = intval($rec['schulkennzahl']);
	}
	//echo '<pre style="display: none;">schools:';
	//print_r($schoolids);
	//echo '</pre>';
	return $schoolids;
}

function blockVendor_exastats_get_groupusers($schoolids = null, $onlyIds = false) {
	global $mysqli;
	$users = array();
	if ($schoolids && !is_array($schoolids))
		$schoolids = array($schoolids);
	if (is_array($schoolids) && count($schoolids) > 0) {
		// for many users
		$likeClauseArr = array();
		foreach ($schoolids as $sId) {
			$likeClauseArr[] = ' username LIKE \''.$sId.'-%\' ';
		}
		$sql = 'SELECT * FROM mdl_user WHERE ('.implode(' OR ', $likeClauseArr).') AND deleted = 0 AND confirmed = 1';
		//echo $sql; exit;
		$recs = $mysqli->query($sql);
		if ($recs)
		while($user = $recs->fetch_assoc())
			$users[$user['id']] = $user;
	}
	if ($onlyIds && count($users) > 0) {
		$users = array_keys($users);
	}
	//echo '<pre style="display: none;">users:';
	//print_r($users);
	//echo '</pre>';
	return $users;
}

function blockVendor_exastats_get_questionnaire_short_results($courseid, $users = null) {
	$categories = blockVendor_exastats_get_categories($courseid);
	if (!$users)
		$users = array();
	$result = array();
	foreach ($categories as $categoryKey => $categoryName) {
		$result[$categoryKey] = array();
		$result[$categoryKey]['questions'] = array();
		$result[$categoryKey]['category_name'] = $categoryName;
		$result[$categoryKey]['ranks'] = array_fill_keys(array(1, 2, 3, 4), '');
		$q_result = blockVendor_exastats_get_questionnaireresults_by_category($courseid, $categoryKey, $users);
		//echo '<pre style="display: none;">';print_r($q_result);echo '</pre>';
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

function blockVendor_exastats_get_questionnaireresults_by_category($courseid, $category, $userids) {
	global $mysqli;
	$result = array('questions'=>[]);
	if (!is_array($userids))
		$userids = array($userids);
	if (count($userids) > 0) {
		$sql = 'SELECT qr.* FROM mdl_questionnaire qr 					
					WHERE qr.course = '.intval($courseid);
		$recs = $mysqli->query($sql);
		// list of questionnairs for course
		if ($recs)
		while ($qrrs = $recs->fetch_assoc()) {
				$sqlTemp = 'SELECT DISTINCT CONCAT_WS(\'_\', q.id, u.id, qr.id) as uiniq, 
									q.id as qid, q.name as qname, q.content as qcontent, 
									qrr.rank as qrrrank, u.id as uid, 
									qr.id as qrid, qr.submitted as submitted
						 FROM mdl_questionnaire_response qr 
								JOIN mdl_questionnaire_response_rank qrr ON qrr.response_id = qr.id AND qr.survey_id = '.$qrrs['id'].' AND qr.complete = \'y\' 
								LEFT JOIN mdl_user u ON u.id = CAST(qr.username AS SIGNED) 
								JOIN mdl_questionnaire_question q ON q.id = qrr.question_id
						WHERE qr.username IN ('.implode(',', $userids).') AND q.name LIKE \''.substr($category, 0, 1).'%\' 
						ORDER BY q.id, u.id, qr.submitted DESC';
				//echo '<pre style="display:none;">blockVendor_exastats_get_questionnaireresults_by_category:';
				//echo $sqlTemp.'<br>'; exit;
				//echo '</pre>';
				$questions = $mysqli->query($sqlTemp);
				$alreadyasked = array();
				if ($questions)
				while($q = $questions->fetch_assoc()) {
					$uniqId = $q['qid'].'_'.$q['uid']; // question id + user id
					if (in_array($uniqId, $alreadyasked))
						continue;
					$alreadyasked[] = $uniqId;
					$question = [];
					$question['content'] = strip_tags($q['qcontent']);
					$question['id'] = $q['qid'];
					$question['response'] = $q['qrrrank'];
					$question['userid'] = $q['uid'];
					$result['questions'][] = $question;
				}
		}
	}
	return $result;
}

function blockVendor_exastats_get_user_quizzes_short_results($courseid = null, $users = null) {
	global $mysqli;
	if (!is_array($users))
		$users = array($users);

	$returnedquizzes = array();

	if ($courseid > 0) {

		// Get the quizzes in this course, this function checks users visibility permissions.
		// We can avoid then additional validate_context calls.
		/*$sql = 'SELECT DISTINCT CONCAT(q.id, \'_\', cm.instance) as uniq, q.*, cm.id as coursemodule
					FROM mdl_quiz q
						JOIN mdl_course_modules cm ON cm.course = q.course
						JOIN mdl_modules md ON md.id = cm.module AND md.name = \'quiz\'
					WHERE q.course = '.intval($courseid);*/
		$sql = 'SELECT DISTINCT q.*
					FROM mdl_quiz q						
					WHERE q.course = '.intval($courseid);
		$quizzes = $mysqli->query($sql);
		if ($quizzes)
		while($quiz = $quizzes->fetch_assoc()) {
			// Entry to return.
			$quizdetails = array();
			// First, we return information that any user can see in the web interface.
			$quizdetails['id'] = $quiz['id'];
			//$quizdetails['coursemodule']      = $quiz['coursemodule'];
			$quizdetails['course']            = $quiz['course'];
			$quizdetails['name']              = $quiz['name'];
			$quizdetails['grade']             = number_format($quiz['grade'], 2);
			$categoryInd = substr(trim($quizdetails['name']), 0, 1);
			$quizdetails['bestgrade'] = blockVendor_exastats_get_user_best_grade($quiz['id'], $users, true);
			//$quizdetails['bestgrade']['hasgrade'] = true;
			//$quizdetails['bestgrade']['grade'] = 5;

			$returnedquizzes[$categoryInd] = $quizdetails;
		}
	}
	$result = array();
	$result['quizzes'] = $returnedquizzes;

	return $result;

}

function blockVendor_exastats_get_user_best_grade($quizid, $users = null, $average = false) {
	global $mysqli;

	$result = array();
	$sumGrades = 0;
	$answeredusers = 0;

	if (count($users) > 0) {
		$sql = ' SELECT CONCAT(quiz, userid, timemodified) as uniq, quiz, userid, timemodified, MAX(grade) as grade, MAX(timemodified) as maxtimemodified
				FROM mdl_quiz_grades
				WHERE quiz = '.intval($quizid).' AND userid IN ('.implode(',', $users).')
				GROUP BY quiz, userid, timemodified
				HAVING timemodified = maxtimemodified';
		//echo $sql; exit;
		$result['hasgrade'] = false;
		$res = $mysqli->query($sql);
		if ($res)
		while($r = $res->fetch_assoc()) {
			$result['hasgrade'] = true;
			$result['grade'] = number_format($r['grade'], 2); // for single user
			$sumGrades += $r['grade'];
			$answeredusers++;
		}

	}

	if ($average) {
		if ($answeredusers > 0) {
			$result['grade'] = number_format($sumGrades / $answeredusers, 2);
		} else {
			$result['grade'] = 0;
		}
	}

	return $result;
}

function blockVendor_exastats_get_quizresults_by_category($courseid, $category, $users) {
	global $mysqli;

	$result = array();
	if (count($users) > 0) {
		$sql = 'SELECT * FROM mdl_quiz WHERE course = '.intval($courseid).' AND name LIKE \''.substr($category, 0, 1).'.%\'';
		$records = $mysqli->query($sql);
		if ($records)
		while($quiz = $records->fetch_assoc()) {
				$questions = array();
				$sql_questions = '
				SELECT DISTINCT CONCAT(q.id, \'_\', qa.userid, \'_\', qs.slot, \'_\', questatt.timemodified) as uniqueid, 
								q.id as questionid, q.name as questionname, q.questiontext as questiontext, 
								questatt.questionsummary  as questionsummary, questatt.responsesummary as responsesummary, questatt.rightanswer as response_correct,
								questatt.questionsummary as questionsummary, questatt.flagged as flagged,
								questattstep.state as state,
								qs.maxmark as maxmark, qs.slot as slot,
								qa.userid as userid,
								questatt.timemodified as timemodified /*, MAX(questatt.timemodified) as lasttimemodified*/
						FROM mdl_quiz_attempts qa
							JOIN mdl_quiz_slots qs ON qs.quizid = qa.quiz
							JOIN mdl_question q ON q.id = qs.questionid
							JOIN mdl_question_attempts questatt ON questatt.slot = qs.slot AND questatt.questionid = q.id 
							JOIN mdl_question_attempt_steps questattstep ON questattstep.questionattemptid = questatt.id  AND questattstep.userid = qa.userid AND questattstep.state LIKE \'graded%\'
						WHERE qa.quiz = '.intval($quiz['id']).' AND qa.state = \'finished\' AND qa.userid IN ('.implode(',', $users).')
						ORDER BY questatt.timemodified DESC
						# GROUP BY qa.id, qs.slot, qa.userid
						# HAVING timemodified = lasttimemodified
						';
				//echo $quiz->id.'--<br>';
				//echo $sql_questions.'<br><br>'; exit;
				$questionsRes = $mysqli->query($sql_questions);
				$distinctArr = array();
				//echo '<pre>';print_r($questionsRes);echo '</pre>';
				if ($questionsRes)
				while ($quest = $questionsRes->fetch_assoc()) {
					$uniq = $quest['questionid'].'_'.$quest['userid'].'_'.$quest['slot'].'_'.$quest['timemodified'];
					if (in_array($uniq, $distinctArr))
						continue;
					$distinctArr[] = $uniq;
					//echo substr($category, 0, 1).' == '.$quest->questionid.'==<br>';
					$question = array(
						'id' => $quest['questionid'],
						'name' => $quest['questionname'],
						'questiontext' => strip_tags($quest['questiontext']),
						'slot' => $quest['slot'],
						'flagged' => $quest['flagged'],
						'question_summary' => $quest['questionsummary'],
						'state' => $quest['state'],
						'status' => $quest['state'],
						//'status' = $attemptobj->get_question_status($slot, $displayoptions->correctness);
						'response' => $quest['responsesummary'],
						'response_correct' => $quest['response_correct'],
						'maxmark' => $quest['maxmark'],
						'userid' => $quest['userid']
						//'mark' => $quest['mark']
					);

					$questions[] = $question;
				}
				$result['questions'] = $questions;
				//$result['bestgrade'] = number_format(quiz_get_best_grade($quiz, $userid), 2);
				//$result['grade'] = number_format($quiz->grade, 2);// $attemptobj->get_sum_marks();
		}
	}
	return $result;
}

function blockVendor_exastats_getansweredQuestionnaire_schools($courseid, $userids) {
	global $mysqli;
	$result = array();
	if (count($userids) > 0) {
		$sql = 'SELECT DISTINCT u.username
 					FROM mdl_questionnaire q 
						JOIN mdl_questionnaire_response qr ON qr.survey_id = q.id						   
						LEFT JOIN mdl_user u ON u.id = CAST(qr.username AS SIGNED) 								
						WHERE q.course = '.intval($courseid).' AND qr.complete = \'y\' AND qr.username IN ('.implode(',', $userids).')  
						ORDER BY u.username';
		$users = $mysqli->query($sql);
		if ($users)
		while($username = $users->fetch_assoc()) {
			$schoolid = blockVendor_exastats_get_schoolid_byusername($username['username']);
			if (!in_array($schoolid, $result))
				$result[] = $schoolid;
		}
	}
	return $result;
}

function blockVendor_exastats_getansweredQuiz_schools($courseid, $userids) {
	global $mysqli;
	$result = array();
	if (count($userids) > 0) {
		$sql = 'SELECT DISTINCT u.username
 					FROM mdl_quiz q 
 						JOIN mdl_quiz_attempts qa ON qa.quiz = q.id
						LEFT JOIN mdl_user u ON u.id = CAST(qa.userid AS SIGNED) 								
						WHERE q.course = '.intval($courseid).' AND qa.state = \'finished\' AND qa.userid IN ('.implode(',', $userids).') 
						ORDER BY u.username												
						';
		$users = $mysqli->query($sql);
		if ($users)
		while($username = $users->fetch_assoc()) {
			$schoolid = blockVendor_exastats_get_schoolid_byusername($username['username']);
			if (!in_array($schoolid, $result))
				$result[] = $schoolid;
		}
	}
	return $result;
}

function blockVendor_exastats_get_schoolid_byusername($username = '') {
	if (!$username)
		return false;
	$userD = explode('-', $username);
	if ($userD[0] && is_numeric($userD[0]))
		return $userD[0];
	return 0;
}


function getHeader() {
	$content = '<html>
				<head>
				<title>Aggregierte Statistiken</title>
				<meta charset="UTF-8">
				</head>
				<body>';
	return $content;
}

function getFooter() {
	$content = '</body></html>';
	return $content;
}

