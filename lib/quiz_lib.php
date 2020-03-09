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

function block_exastats_get_user_quizzes_short_results($courseid = null, $users = null) {
	global $USER, $CFG, $DB;
	// include quiz lib
	//require_once __DIR__.'/../../../mod/quiz/lib.php';
	require_once $CFG->dirroot.'/mod/quiz/locallib.php';

	if (!$courseid)
		print_error("invalidinstance", "block_exastats");

	if (!$users) {
		$schoolid = block_exastats_get_schoolid_byusername();
		$users = block_exastats_get_groupusers($schoolid, true);
	}

	//if (!$users)
	//	$users = $USER->id;

	if (!is_array($users))
		$users = array($users);

	//$quizExternalObj = new mod_quiz_external();
	//$courseQuizzes = $quizExternalObj::get_quizzes_by_courses(array($courseid));
	//$courseQuizzes = mod_quiz_external::get_quizzes_by_courses(array($courseid));
	//print_r($courseQuizzes);
	$warnings = array();
	$returnedquizzes = array();
	$isAdmin = false;

	if (!is_array($courseid))
		$courseids = array($courseid);
	// is admin?
	$adminIds = array_keys(get_admins());
	if (in_array($USER->id, $adminIds)) {
		$isAdmin = true;
		$coursesToShow = array($courseid);
	} else {
		$mycourses = enrol_get_my_courses();
		$coursesToShow = array_intersect_key($mycourses, array_combine($courseids, $courseids));
	}

	// Ensure there are courseids to loop through.
	if (!empty($coursesToShow)) {

		// Get the quizzes in this course, this function checks users visibility permissions.
		// We can avoid then additional validate_context calls.
		if ($isAdmin) {
			/*$sql = 'SELECT DISTINCT CONCAT(q.id, \'_\', cm.instance) as uniq, q.*, cm.id as coursemodule
						FROM {quiz} q
					 		JOIN {course_modules} cm ON cm.course = q.course 
					    	JOIN {modules} md ON md.id = cm.module AND md.name = \'quiz\'
						WHERE q.course = ?';*/
			$sql = 'SELECT DISTINCT q.*
						FROM {quiz} q					 		
						WHERE q.course = ?';
			$quizzes = $DB->get_records_sql($sql, [$courseid]);
		} else {
			$quizzes = get_all_instances_in_courses("quiz", $coursesToShow);
		}
		foreach ($quizzes as $quiz) {
			//echo '<pre>'; print_r($quiz); echo '</pre>';
			//$context = context_module::instance($quiz->coursemodule);

			// Update quiz with override information.
			//$quiz = quiz_update_effective_access($quiz, $USER->id);

			// Entry to return.
			$quizdetails = array();
			// First, we return information that any user can see in the web interface.
			$quizdetails['id'] = $quiz->id;
			//$quizdetails['coursemodule']      = $quiz->coursemodule;
			$quizdetails['course']            = $quiz->course;
			$quizdetails['name']              = $quiz->name;
			$quizdetails['grade']             = number_format($quiz->grade, 2);
			$categoryInd = substr(trim($quizdetails['name']), 0, 1);
			$quizdetails['bestgrade'] = block_exastats_get_user_best_grade($quiz->id, $users, true);
			//$quizdetails['bestgrade']['hasgrade'] = true;
			//$quizdetails['bestgrade']['grade'] = 5;

			$returnedquizzes[$categoryInd] = $quizdetails;
		}
	}
	$result = array();
	$result['quizzes'] = $returnedquizzes;

	//print_r($result); exit;
	//echo '!!!!!!!!!!!'; // for found my last changing place !!!!!!
	return $result;
//print_r($courseQuizzes); exit;

}

/**
 * Get the best current grade for the given user on a quiz.
 *
 * @param int $quizid quiz instance id
 * @param array $users user ids
 * @return array of warnings and the grade information
 * @since Moodle 3.1
 */
function block_exastats_get_user_best_grade($quizid, $users = null, $average = false) {
	global $DB, $USER;

	//list($quiz, $course, $cm, $context) = validate_quiz($quizid);

	if (!$users) {
		$users = $USER->id;
	}

	if (!is_array($users))
		$users = array($users);

	//$user = core_user::get_user($userid, '*', MUST_EXIST);
	//core_user::require_active_user($user);

	// Extra checks so only users with permissions can view other users attempts.
	//if ($USER->id != $user->id) {
	//	require_capability('mod/quiz:viewreports', $context);
	//}

	$result = array();
	$sumGrades = 0;
	$answeredusers = 0;

	if (count($users) > 0) {
		$sql = ' SELECT CONCAT(quiz, userid, timemodified) as uniq, quiz, userid, timemodified, MAX(grade) as grade, MAX(timemodified) as maxtimemodified
				FROM {quiz_grades}
				WHERE quiz = ? AND userid IN ('.implode(',', $users).')
				GROUP BY quiz, userid, timemodified
				HAVING timemodified = maxtimemodified';
		//echo $sql.'<br>';
		$result['hasgrade'] = false;
		$res = $DB->get_records_sql($sql, [$quizid]);
		foreach ($res as $r) {
			$result['hasgrade'] = true;
			$result['grade'] = number_format($r->grade, 2); // for single user
			$sumGrades += $r->grade;
			$answeredusers++;
		}

	}
	//foreach ($users as $userid) {
	//	$grade = quiz_get_best_grade($quiz, $userid);
	//	if ($grade === null) {
	//		if (!isset($result['hasgrade'])) {
	//			$result['hasgrade'] = false;
	//		}
	//	} else {
	//		$result['hasgrade'] = true;
	//		$result['grade'] = number_format($grade, 2);
	//		$sumGrades += $grade;
	//		$answeredusers++;
	//	}
	//}
	if ($average) {
		if ($answeredusers > 0) {
			$result['grade'] = number_format($sumGrades / $answeredusers, 2);
		} else {
			$result['grade'] = 0;
		}
	}

	return $result;
}

function validate_quiz($quizid) {
	global $DB;
	// Request and permission validation.
	$quiz = $DB->get_record('quiz', array('id' => $quizid), '*', MUST_EXIST);
	list($course, $cm) = get_course_and_cm_from_instance($quiz, 'quiz');
	$context = context_module::instance($cm->id);
	return array($quiz, $course, $cm, $context);
}


/**
 * Returns quizes result with categories
 * @param integer $quizid
 * @param mixed $users
 * @param integer|null $staticAttempt
 * @return array
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function block_exastats_get_user_quizzes_short_results_with_categories($quizid = 0, $users = null, $staticAttempt = null) {
    global $USER, $CFG, $DB;

    static $staticCategoryAvgs = null;
    if (!$staticCategoryAvgs) {
        $staticCategoryAvgs = array();
    }

    if (!$users) {
        $users = array($USER->id);
    }
    if (!is_array($users)) {
        $users = array($users);
    }

    $result = array(
            'header' => '',
            'message' => '',
            'data' => array(),
            'users' => $users
    );

    $categories = array();
    $first_user = true;

    $isQuizAverage = false;

    foreach ($users as $userid) {

        $quizObj = quiz::create($quizid, $userid);
        $quizObj->preload_questions();
        $quizObj->load_questions();

        $quiz = $quizObj->get_quiz();
        if ($quiz->grademethod) {
            $isQuizAverage = true;
        }
        $questions = $quizObj->get_questions();
        $maxQuizgrade = quiz_format_grade($quiz, $quiz->sumgrades);

        $result['header'] = $quiz->name;
        // attempt for calculating can be from last/first/best/average
        $neededattempt = null;

        // get attempts
        $attempts = quiz_get_user_attempts($quiz->id, $userid, 'finished', true);
        if ($staticAttempt) {
            $neededattempt = $attempts[$staticAttempt];
        } else {
            switch ($quiz->grademethod) {
                case 1: // Highest grade
                    $maxGrade = -1;
                    $attemptMaxGrade = null;
                    foreach ($attempts as $attempt) {
                        if ($attempt->sumgrades > $maxGrade) {
                            $maxGrade = $attempt->sumgrades;
                            $attemptMaxGrade = $attempt;
                        }
                    }
                    $neededattempt = $attemptMaxGrade;
                    break;
                case 2: // Average grade
                    if (array_key_exists($userid, $staticCategoryAvgs)) {
                        $staticCategoryAvgs[$userid] = array();
                    }
                    foreach ($attempts as $attempt) {
                        $staticCategoryAvgs[$userid][] =
                                block_exastats_get_user_quizzes_short_results_with_categories($quizid, $userid, $attempt->id);
                    }
                    $neededattempt = 'average'; // enable average calculation!
                    break;
                case 3: // First attempt
                    $neededattempt = reset($attempts);
                    break;
                case 4: // Last attempt
                    $neededattempt = end($attempts);
                    break;
                default:
                    $neededattempt = end($attempts); // last
            }
        }
        //echo "<pre>debug:<strong>quiz_lib.php:237</strong>\r\n"; print_r($neededattempt); echo '</pre>'; exit; // !!!!!!!!!! delete it

        $attemptnumber = null;
        if ($neededattempt == 'average') {
            // get average values for categories and insert it in $result['data']
            foreach ($users as $uId) {
                if (array_key_exists($uId, $staticCategoryAvgs)) {
                    $tempCatData = array();
                    foreach ($staticCategoryAvgs as $useridKey => $avgArr) {
                        foreach ($avgArr as $tempAttempt => $attemptData) {
                            foreach ($attemptData['data'] as $catTempId => $catData) {
                                if (!array_key_exists($catTempId, $tempCatData)) {
                                    $tempCatData[$catTempId] = array(
                                            'categoryname' => $catData['categoryname'],
                                            'averageFractions' => [],
                                            'countquestions' => $catData['countquestions']
                                    ) ;
                                }
                                $tempCatData[$catTempId]['averageFractions'][] = $catData['averageFraction'];
                                //echo "<pre>debug:<strong>quiz_lib.php:295</strong>\r\n"; print_r($catData); echo '</pre>'; // !!!!!!!!!! delete it
                            }
                        }
                    }
                    foreach ($tempCatData as $catTempId => $tempCatData) {
                        if (count($tempCatData["averageFractions"]) > 0) {
                            $avgVal = array_sum($tempCatData['averageFractions']) / count($tempCatData['averageFractions']);
                            $avgVal = round($avgVal);
                        } else {
                            $avgVal = 0;
                        }
                         $avg = array(
                            'categoryname' => $tempCatData['categoryname'],
                            'averageFraction' => $avgVal,
                            'countquestions' => $tempCatData['countquestions']
                        );
                        $result['data'][$catTempId] = $avg;
                    }

                }
            }
        } elseif ($neededattempt) { // If an finished attempt exists
            $attemptGrade = quiz_format_grade($quiz, $neededattempt->sumgrades);
            // If the attempt is now overdue, deal with that.
            $att = $quizObj->create_attempt_object($neededattempt); //->handle_if_time_expired($timenow, true);
            $slots = $att->get_slots();

            $qubaidscondition = new qubaid_join('{quiz_attempts} quiza', 'quiza.uniqueid',
                    'quiza.quiz = :quizaquiz AND quiza.userid = :userid AND quiza.id = :quizaid ',
                    array('quizaquiz' => $quiz->id, 'userid' => $userid, 'quizaid' => $neededattempt->id));
            
            $dm = new question_engine_data_mapper();
            // here is not real latest, but latest vith selected attempt! Look conditions in $qubaidscondition
            $latesstepdata = $dm->load_questions_usages_latest_steps($qubaidscondition, $slots);

            $userAnsweredData = array();

            foreach ($latesstepdata as $ans) {
                //echo "<pre>debug:<strong>block_exaquizstats.php:173</strong>\r\n"; print_r($ans); echo '</pre>'; // !!!!!!!!!! delete it
                // TODO: wich value to use? fraction? (fraction has minfraction - maxfraction value)
                $userAnsweredData[$ans->questionid] = array(
                        'fraction' => $ans->fraction,
                        'minfraction' => $ans->minfraction,
                        'maxfraction' => $ans->maxfraction,
                );
            }

            foreach ($questions as $question) {
                if (!array_key_exists($question->category, $categories)) {
                    $questioncategory =
                            $DB->get_record('question_categories', array('id' => $question->category), '*', IGNORE_MISSING);
                    $categories[$question->category] = array(
                            'categoryname' => $questioncategory->name,
                            'maxfraction' => 0,
                            'fraction' => 0,
                            'answersCount' => 0,
                    );
                }
                if (array_key_exists($question->id, $userAnsweredData)) {
                    $categories[$question->category]["maxfraction"] += $userAnsweredData[$question->id]['maxfraction'];
                    $categories[$question->category]["fraction"] += $userAnsweredData[$question->id]['fraction'];
                    if ($first_user) { // get count only from first user
                        $categories[$question->category]["answersCount"]++;
                    }
                }
            }
            $first_user = false;
        }

    }

    if (count($categories) > 0 ) {
        // get average values for categories
        foreach ($categories as $catid => $category) {
            if ($category["maxfraction"] > 0) {
                $avgVal = $category['fraction'] / $category['maxfraction'] * 100;
                $avgVal = round($avgVal);
            } else {
                $avgVal = 0;
            }
            $avg = array(
                    'categoryname' => $category['categoryname'],
                    'averageFraction' => $avgVal,
                    'countquestions' => $category['answersCount']
            );
            $result['data'][$catid] = $avg;
        }
    } else if ($isQuizAverage ) {
        if (count($result['data']) == 0) {
            $result['message'] = get_string('no_answered', 'block_exastats');
        }
    } else {
        $result['message'] = get_string('no_answered', 'block_exastats');
    }


    if (!$staticAttempt && $isQuizAverage) { // only AVERAGE grading from a few attempts - last result
        // get average values for categories
        //if (array_key_exists($userid, ))
        //foreach ($staticCategoryAvgs as $useridKey => $avgArr) {
        //    echo "<pre>debug:<strong>quiz_lib.php:359</strong>\r\n"; print_r($avgArr); echo '</pre>'; // !!!!!!!!!! delete it
        //}
    }

    return $result;
}


