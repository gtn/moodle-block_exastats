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

$url = '/blocks/exastats/stats_common.php';
$PAGE->set_url($url, ['courseid' => $courseid]);

require_login($courseid);

$userRole = block_exastats_get_role_byusername();
// only for teachers!!
if (!in_array($userRole, ['common', 'admin', 'director', 'teacher'])) {
	echo block_exastats_show_lastmessage(get_string('noAccess', 'block_exastats'), $course);
	return true;
}

$PAGE->set_title(get_string('page_stats_teacher_title', 'block_exastats'));
$PAGE->set_heading(get_string('pluginname', "block_exastats"));


echo $OUTPUT->header();

echo '<h2>'.get_string('my_stats', 'block_exastats').'</h2>';
echo '<div class="bloks_exastats teacher_stats">';

$questionnaire_results = block_exastats_get_questionnaire_short_results($courseid, array($USER->id));
$quiz_allresults = block_exastats_get_user_quizzes_short_results($courseid, array($USER->id))['quizzes'];
// get all categories
$categories = block_exastats_get_categories($courseid);
foreach ($categories as $categoryKey => $category) {
	echo '<br>';
	echo '<h3>'.$category.'</h3>';
	// bars
	if ($questionnaire_results[$categoryKey]['quiestionnairrank'] && $questionnaire_results[$categoryKey]['quiestionnairrank'] > 0)
		$questionnair_value = $questionnaire_results[$categoryKey]['quiestionnairrank'];
	else
		$questionnair_value = 0;
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
	// questionnair report
	$questionnair_results = block_exastats_get_questionnaireresults_by_category($courseid, $category, $USER->id);
	if (isset($questionnair_results['questions']) && count($questionnair_results['questions']) > 0) {
		echo '<table class="table">';
		echo '<tr>';
		echo '<th></th>';
		echo '<th>Trifft völlig zu.</th>';
		echo '<th>Trifft eher zu.</th>';
		echo '<th>Trifft eher nicht zu.</th>';
		echo '<th>Trifft gar nicht zu.</th>';
		echo '</tr>';
		foreach ($questionnair_results['questions'] as $question) {
			echo '<tr>';
			echo '<td>'.$question['content'].'</td>';
			echo '<td align="center" valign="middle">'.($question['response'] == 0 ? 'X' : '').'</td>';
			echo '<td align="center" valign="middle">'.($question['response'] == 1 ? 'X' : '').'</td>';
			echo '<td align="center" valign="middle">'.($question['response'] == 2 ? 'X' : '').'</td>';
			echo '<td align="center" valign="middle">'.($question['response'] == 3 ? 'X' : '').'</td>';
			echo '</tr>';
		}
		echo '</table>';
		echo '<br>';
	}  else {
		echo get_string('result_questionnaire_notfinished', 'block_exastats');
		echo '<br>';
	}
	// quiz report
	$quiz_results = block_exastats_get_quizresults_by_category($courseid, $category, $USER->id);
	if (isset($quiz_results['questions']) && count($quiz_results['questions']) > 0) {
		echo '<table class="table">';
		echo '<tr>';
		echo '<th>'.get_string('result_questiontable_question', 'block_exastats').'</th>';
		echo '<th>'.get_string('result_questiontable_myresponse', 'block_exastats').'</th>';
		//echo '<th>'.get_string('result_questiontable_correctresponse', 'block_exastats').'</th>';
		echo '<th>'.get_string('result_questiontable_status', 'block_exastats').'</th>';
		//echo '<th>'.get_string('result_questiontable_questiongrade', 'block_exastats').'</th>';
		echo '</tr>';
		foreach ($quiz_results['questions'] as $question) {
			echo '<tr>';
			echo '<td>'.$question['questiontext'].'</td>';
			echo '<td>'.preg_replace("/\r*\n*;/", ';<br>', $question['response']).'</td>';
			//echo '<td>'.$question['response_correct'].'</td>';
			echo '<td>'.$question['status'].'</td>';
			//echo '<td>'.$question['mark'].'/'.$question['maxmark'].'</td>';
			echo '</tr>';
		}
		echo '<tr>';
		echo '<td colspan="1"></td>';
		echo '<td align="right"><strong>'.get_string('result_questiontable_totalgrade', 'block_exastats').'</strong></td>';
		echo '<td><strong>'.$quiz_results['bestgrade'].'/'.$quiz_results['grade'].'</strong></td>';
		echo '</tr>';
		echo '</table>';
		echo '<br>';
	} else {
		echo get_string('result_quiz_notfinished', 'block_exastats');
		echo '<br>';
	}
	echo '</div>';
}

echo '</div>';
echo $OUTPUT->footer($course);


