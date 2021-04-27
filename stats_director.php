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

require_once __DIR__.'/inc.php';

$courseid = optional_param('courseid', 0, PARAM_INT);

$context = context_system::instance();
$conditions = array("id" => $courseid);
if (!$course = $DB->get_record("course", $conditions)) {
	print_error("invalidinstance", "block_exastats");
}

$url = '/blocks/exastats/stats_director.php';
$PAGE->set_url($url, ['courseid' => $courseid]);

require_login($courseid);

$userRole = block_exastats_get_role_byusername();

// only for directors!!
if ($userRole != 'director') {
	echo block_exastats_show_lastmessage(get_string('noAccess', 'block_exastats'), $course);
	return true;
}

$PAGE->set_title(get_string('page_stats_director_title', 'block_exastats'));
$PAGE->set_heading(get_string('pluginname', "block_exastats"));


echo $OUTPUT->header();

// in new version of questionnaire module they changed real value in radio input 0 -> 1, 1 -> 2,....
// so we need to use this koeff
$responsesEmptyArray = array_fill_keys(array(0, 1, 2, 3), '');
$minRespVal = 0;
$maxRespVal = 3;
$responseKoeff = 0;
if ($CFG->version >= 2018050104) {
    $responsesEmptyArray = array_fill_keys(array(1, 2, 3, 4), '');
    $minRespVal = 1;
    $maxRespVal = 4;
    $responseKoeff = 1;
}

echo '<h2>'.get_string('director_stats', 'block_exastats').'</h2>';
echo '<div class="bloks_exastats director_stats">';

// get all categories
$categories = block_exastats_get_categories($courseid);
$schoolid = block_exastats_get_schoolid_byusername();
$users = block_exastats_get_groupusers($schoolid, true);
if (count($users) >= 0){//$minUsersForReport) {
//$all_category_responses = array();
//$count_category_questions = array();
	$all_quiz_statuses = array();
	$categoryAnsweredusers = array();
	$categoryQuizAnsweredusers = array();
// calculate results for all group users
	foreach ($categories as $categoryKey => $category) {
		$answeredusers = array();
		$categoryAnsweredusers[$categoryKey] = 0;
		$tempUserArray = array();
		// QUESTIONNAIRE
		$questionnair_results[$categoryKey] = array();
		$questionnair_results[$categoryKey]['questions'] = array();
		$all_category_responses[$categoryKey] = $responsesEmptyArray;
		$questionnair_result = block_exastats_get_questionnaireresults_by_category($courseid, $category, $users);
		foreach ($questionnair_result['questions'] as $question) {
			$questionId = $question['id'];
			$currentQuestionResponse = $question['response']; // may be 0:Trifft völlig zu., 1:Trifft eher zu., 2:Trifft e....
			// count of users, which asked on questions (only one attempt for user+question, so we can keep only qustionid)
			if (!array_key_exists($questionId, $answeredusers)) {
				$answeredusers[$questionId] = 0;
			}
			if (!in_array($question['userid'], $tempUserArray)) {
				$categoryAnsweredusers[$categoryKey] += 1;
				$tempUserArray[] = $question['userid'];
			}
			$answeredusers[$questionId] += 1;
			if (!key_exists($questionId, $questionnair_results[$categoryKey]['questions'])) {
				// default values
				$questionnair_results[$categoryKey]['questions'][$questionId]['id'] = $questionId;
				$questionnair_results[$categoryKey]['questions'][$questionId]['content'] = $question['content'];
				//$questionnair_results[$categoryKey]['questions'][$questionId]['response'] = array();
				$questionnair_results[$categoryKey]['questions'][$questionId]['response'] = $responsesEmptyArray;
				$questionnair_results[$categoryKey]['questions'][$questionId]['responsepercent'] = $responsesEmptyArray;
			}
			// count of question responses
			@$questionnair_results[$categoryKey]['questions'][$questionId]['response'][$currentQuestionResponse] += 1; // +1 for needed question response (or for 0:Trifft völlig zu., or for 1:Trifft eher zu. ....)
		}
		// fill percents
		foreach ($answeredusers as $questionId => $countResponses) {
			for ($resp_i = $minRespVal; $resp_i <= $maxRespVal; $resp_i++) {
				if ($countResponses > 0) {
					@$questionnair_results[$categoryKey]['questions'][$questionId]['responsepercent'][$resp_i] =
						$questionnair_results[$categoryKey]['questions'][$questionId]['response'][$resp_i] / $countResponses * 100;
				} else {
					@$questionnair_results[$categoryKey]['questions'][$questionId]['responsepercent'][$resp_i] = 0;
				}
			}
		}
		//}
		//}
		// total percents for category
		//foreach($all_category_responses[$categoryKey] as $responseKey => $responseCount)
		//	$category_percents[$categoryKey][$responseKey] = $responseCount / $category_questions_count[$categoryKey] * 100;

		// QUIZ
		$answeredusers = array();
		$quiz_results[$categoryKey] = array();
		$quiz_results[$categoryKey]['questions'] = array();
		$categoryQuizAnsweredusers[$categoryKey] = 0;
		$tempUserArray = array();
		//foreach ($users as $userid) {
		$quiz_res = block_exastats_get_quizresults_by_category($courseid, $category, $users);
		//echo '<pre>';print_r($quiz_res);echo '</pre>';
		if (isset($quiz_res['questions']) && count($quiz_res['questions']) > 0) {
			foreach ($quiz_res['questions'] as $question) {
				$questionId = $question['id'];
				if (!array_key_exists($questionId, $answeredusers)) {
					$answeredusers[$questionId] = 0;
				}
				if (!in_array($question['userid'], $tempUserArray)) {
					$categoryQuizAnsweredusers[$categoryKey] += 1;
					$tempUserArray[] = $question['userid'];
				}
				$answeredusers[$questionId] += 1;
				if (!key_exists($questionId, $quiz_results[$categoryKey]['questions'])) {
					$quiz_results[$categoryKey]['questions'][$questionId] = $question;
					$quiz_results[$categoryKey]['questions'][$questionId]['status'] = array();
					$quiz_results[$categoryKey]['questions'][$questionId]['statuspercent'] = array();
				}
				if (!key_exists($question['status'], $quiz_results[$categoryKey]['questions'][$questionId]['status'])) {
					$quiz_results[$categoryKey]['questions'][$questionId]['status'][$question['status']] = 0;
					$quiz_results[$categoryKey]['questions'][$questionId]['statuspercent'][$question['status']] = 0;
				}
				$quiz_results[$categoryKey]['questions'][$questionId]['status'][$question['status']] += 1;
				if (!in_array($question['status'], $all_quiz_statuses)) {
					$all_quiz_statuses[] = $question['status'];
				}
			}
		}
		//}
		// fill quiz questions percents
		foreach ($answeredusers as $questionId => $countResponses) {
			foreach ($all_quiz_statuses as $status) {
				if ($countResponses > 0 && key_exists($status, $quiz_results[$categoryKey]['questions'][$questionId]['statuspercent'])) {
					$quiz_results[$categoryKey]['questions'][$questionId]['statuspercent'][$status] =
						$quiz_results[$categoryKey]['questions'][$questionId]['status'][$status] / $countResponses * 100;
				} else {
					$quiz_results[$categoryKey]['questions'][$questionId]['statuspercent'][$status] = 0;
				}
			}
		}
	}

	$questionnaireShort_results = block_exastats_get_questionnaire_short_results($courseid, $users);
	$quiz_allresults = block_exastats_get_user_quizzes_short_results($courseid, $users)['quizzes'];
//echo '<pre>';print_r($quiz_allresults);echo '</pre>';
//display summary results
	echo '<div>'.get_string('result_count_users', 'block_exastats').' '.count($users).'</div>';
	foreach ($categories as $categoryKey => $category) {
		echo '<br>';
		echo '<h3>'.$category.'</h3>';
		// bars
		if ($questionnaireShort_results[$categoryKey]['quiestionnairrank'] && $questionnaireShort_results[$categoryKey]['quiestionnairrank'] > 0) {
			$questionnair_value = $questionnaireShort_results[$categoryKey]['quiestionnairrank'];
		} else {
			$questionnair_value = 0;
		}
		if ($quiz_allresults[$categoryKey]['bestgrade']['hasgrade']) {
			$quiz_value = $quiz_allresults[$categoryKey]['bestgrade']['grade'] * 100 / $quiz_allresults[$categoryKey]['grade'];
		} else {
			$quiz_value = 0;
		}

		echo '<table class="more_info_bars">
			<tr>
				<td class="row-bar-name">
					<span class="bar-name">'.get_string('self-assessment', 'block_exastats').'</span>
				</td>
				<td valign="middle" width="99%">
					<div class="bar-container">
						<div class="self-bar" style="width: '.number_format($questionnair_value, 0).'%;">&nbsp;</div>
					</div>
				</td>
				<td class="percent_value">'.
			number_format($questionnair_value, 0).'%
				</td>
			</tr>
			<tr>
				<td class="row-bar-name">
					<span class="bar-name">'.get_string('knowledge', 'block_exastats').'</span>
				</td>
				<td valign="middle" width="99%">
					<div class="bar-container">
						<div class="knowledge-bar" style="width: '.number_format($quiz_value, 0).'%;">&nbsp;</div>
					</div>
				</td>
				<td class="percent_value">'.
			number_format($quiz_value, 0).'%
				</td>
			</tr>			
		</table>';
		echo '<div><a href="#" data-toggle="collapse" data-target="#category'.$categoryKey.'">'.get_string('more', 'block_exastats').'</a></div>';

		echo '<div class="collapse" id="category'.$categoryKey.'">';

		// questionnaire report
		if (isset($questionnair_results[$categoryKey]['questions'])
					&& count($questionnair_results[$categoryKey]['questions']) > 0
					&& $categoryAnsweredusers[$categoryKey] >= $minAnsweredUsersForReport) {
			echo '<table class="table">';
			echo '<tr>';
			echo '<th></th>';
			echo '<th>Trifft völlig zu.</th>';
			echo '<th>Trifft eher zu.</th>';
			echo '<th>Trifft eher nicht zu.</th>';
			echo '<th>Trifft gar nicht zu.</th>';
			echo '</tr>';
			foreach ($questionnair_results[$categoryKey]['questions'] as $question) {
				echo '<tr>';
				echo '<td>'.$question['content'].'</td>';
				echo '<td align="center" valign="middle">'.
					$question['response'][0 + $responseKoeff].
					($question['response'][0 + $responseKoeff] > 0 ? ' ('.number_format($question['responsepercent'][0 + $responseKoeff], 2).'%)' : '').
					'</td>';
				echo '<td align="center" valign="middle">'.
					$question['response'][1 + $responseKoeff].
					($question['response'][1 + $responseKoeff] > 0 ? ' ('.number_format($question['responsepercent'][1 + $responseKoeff], 2).'%)' : '').
					'</td>';
				echo '<td align="center" valign="middle">'.
					$question['response'][2 + $responseKoeff].
					($question['response'][2 + $responseKoeff] > 0 ? ' ('.number_format($question['responsepercent'][2 + $responseKoeff], 2).'%)' : '').
					'</td>';
				echo '<td align="center" valign="middle">'.
					$question['response'][3 + $responseKoeff].
					($question['response'][3 + $responseKoeff] > 0 ? ' ('.number_format($question['responsepercent'][3 + $responseKoeff], 2).'%)' : '').
					'</td>';
				echo '</tr>';
			}
			// footer
			echo '<tr>';
			echo '<td colspan="5" align="right"><strong>'.get_string('count_answered_users', 'block_exastats').': '.$categoryAnsweredusers[$categoryKey].'</strong></td>';
			echo '</tr>';
			/*echo '<tr>';
			echo '<td align="right"><strong>'.get_string('result_total', 'block_exastats').'</strong></td>';
			echo '<td align="center"><strong>'.
				$all_category_responses[$categoryKey][0].
				($category_percents[$categoryKey][0] > 0 ? ' ('.number_format($category_percents[$categoryKey][0], 2).'%)' : '').
				'</strong></td>';
			echo '<td align="center"><strong>'.
				$all_category_responses[$categoryKey][1].
				($category_percents[$categoryKey][1] > 0 ? ' ('.number_format($category_percents[$categoryKey][1], 2).'%)' : '').
				'</strong></td>';
			echo '<td align="center"><strong>'.
				$all_category_responses[$categoryKey][2].
				($category_percents[$categoryKey][2] > 0 ? ' ('.number_format($category_percents[$categoryKey][2], 2).'%)' : '').
				'</strong></td>';
			echo '<td align="center"><strong>'.
				$all_category_responses[$categoryKey][3].
				($category_percents[$categoryKey][3] > 0 ? ' ('.number_format($category_percents[$categoryKey][3], 2).'%)' : '').
				'</strong></td>';
			echo '</tr>';*/
			echo '</table>';
			echo '<br>';
		} else if ($categoryAnsweredusers[$categoryKey] < $minAnsweredUsersForReport) {
			echo get_string('hiddenQuestionnairByAnonymity', 'block_exastats');
			echo '<br>';
		} else {
			echo get_string('result_questionnaire_notfinished', 'block_exastats');
			echo '<br>';
		}

		// quiz report
		if (isset($quiz_results[$categoryKey]['questions'])
				&& count($quiz_results[$categoryKey]['questions']) > 0
				&& $categoryQuizAnsweredusers[$categoryKey] >= $minAnsweredUsersForReport) {
			echo '<table class="table">';
			echo '<tr>';
			echo '<th>'.get_string('result_questiontable_question', 'block_exastats').'</th>';
			foreach ($all_quiz_statuses as $questionStatus) {
				echo '<th data-status="'.$questionStatus.'">'.get_string($questionStatus, 'block_exastats').'</th>';
			}
			echo '</tr>';
			foreach ($quiz_results[$categoryKey]['questions'] as $question) {
				echo '<tr>';
				echo '<td>'.$question['questiontext'].'</td>';
				foreach ($all_quiz_statuses as $questionStatus) {
					echo '<td>'.
						(isset($question['status'][$questionStatus]) ? $question['status'][$questionStatus] : '').
						(isset($question['status'][$questionStatus]) && $question['status'][$questionStatus] > 0 && isset($question['statuspercent'][$questionStatus]) > 0 ? ' ('.number_format($question['statuspercent'][$questionStatus], 2).'%)' : '').
						'</td>';
				}
				echo '</tr>';
			}
			echo '<tr>';
			echo '<td colspan="4" align="right"><strong>'.get_string('count_answered_users', 'block_exastats').': '.$categoryQuizAnsweredusers[$categoryKey].'</strong></td>';
			echo '</tr>';
			echo '</table>';
			echo '<br>';
		} else if ($categoryQuizAnsweredusers[$categoryKey] < $minAnsweredUsersForReport) {
			echo get_string('hiddenQuizByAnonymity', 'block_exastats');
			echo '<br>';
		} else {
			echo get_string('result_quiz_notfinished', 'block_exastats');
			echo '<br>';
		}
		echo '</div>';
	}
} else {
	echo '<div>'.get_string('result_count_users', 'block_exastats').' '.count($users).'</div>';
	echo get_string('hiddenByAnonymity', 'block_exastats');
	echo '<br />';
}

echo '</div>';
echo $OUTPUT->footer($course);


