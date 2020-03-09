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

//$CFG->cachejs = false;
$PAGE->requires->js_call_amd('block_exastats/stats_regions', 'initialise');
//$PAGE->requires->js('/blocks/exastats/javascript/stats_region.js', true);

$context = context_system::instance();
$conditions = array("id" => $courseid);
if (!$course = $DB->get_record("course", $conditions)) {
	print_error("invalidinstance", "block_exastats");
}

$url = '/blocks/exastats/stats_region.php';
$PAGE->set_url($url, ['courseid' => $courseid]);

require_login($courseid);

$userRole = block_exastats_get_role_byusername();
// only for admins!!
if ($userRole != 'admin') {
	echo block_exastats_show_lastmessage(get_string('noAccess', 'block_exastats'), $course);
	return true;
}
// check on needed tables
$tableCount = 0; // we need 2 foreign tables
//$tableCount += count($DB->get_records_sql('SHOW TABLES LIKE ?', [$tableCities]));
$tableCount += count($DB->get_records_sql('SHOW TABLES LIKE ?', [$tableSchools]));
if ($tableCount < 1) {
	echo block_exastats_show_lastmessage(get_string('noForeignTables', 'block_exastats'), $course);
	return true;
}

$PAGE->set_title(get_string('page_stats_region_title', 'block_exastats'));
$PAGE->set_heading(get_string('pluginname', "block_exastats"));

$content = '';

$formSubmit = optional_param('form_submit', 'no', PARAM_RAW);
$schoolnumberPost = optional_param('schoolnumber', 0, PARAM_INT);
//$yearsonjobPost = optional_param('yearsonjob', '', PARAM_RAW);
$regionsPost = optional_param_array('region', array(), PARAM_RAW);
$bezirkPost = optional_param_array('bezirk', array(), PARAM_RAW);
$schTypesPost = optional_param_array('schooltype', array(), PARAM_RAW);
$genderPost = optional_param_array('gender', array(), PARAM_RAW);
$yearsonjobPost = optional_param_array('yearsonjob', array(), PARAM_RAW);
//echo $formSubmit; exit;
if ($formSubmit != 'downloadCsv' && $formSubmit != 'notAnsweredSchools') {
	echo $OUTPUT->header();
	echo  '<h2>'.get_string('region_stats', 'block_exastats').'</h2>';

	// get regions
	/*$sql = 'SELECT DISTINCT c.bundesland
				FROM '.$tableCities.' c	
				ORDER BY bundesland
			';
	$regions = $DB->get_records_sql($sql);*/
	$regions = $bundeslands;
	// get schooltypes
	/*$sql = 'SELECT DISTINCT `Primär-Schulart` as type
				FROM '.$tableSchools.'
			 	WHERE `Primär-Schulart` IS NOT NULL
				ORDER BY `Primär-Schulart` ASC ';
	$schTypes = $DB->get_records_sql($sql);*/
	$schTypes = array_keys($schultypen);
	//$schTypes = $schultypen;
	// form
	echo '<form method="post" action="'.$PAGE->url.'">
					<div class="form-group">
						<label class="col-sm-6 control-label " for="regionselect">'.get_string('select_region', 'block_exastats').'</label>
						<select class="form-control" id="regionselect" name="region[]" multiple="multiple" size="'.count($regions).'">';
							foreach ($regions as $ind => $reg) {
								echo  '<option value="'.$ind.'" '.(in_array($ind, $regionsPost) ? ' selected="selected" ' : '').'>'.$reg.'</option>';
							}
						echo '</select>
					</div>
					<div class="form-group hidden">
						<label class="col-sm-6 control-label " for="bezirkselect" >'.get_string('select_bezirk', 'block_exastats').'</label>';
						foreach($regions as $rInd => $reg) {
							if (isset($bezirkList[$rInd]) && is_array($bezirkList[$rInd])) {
								echo '<select class="form-control bezirkSelectBox" id="bezirkselect'.$rInd.'" data-region="'.$rInd.'" name="bezirk[]" multiple="multiple" size="'.count($bezirkList[$rInd]).'">';
								foreach ($bezirkList[$rInd] as $bInd => $bez) {
									echo '<option value="'.$bInd.'" '.(in_array($bInd, $bezirkPost) ? ' selected="selected" ' : '').'>'.$bez.'</option>';
								}
								echo '</select>';
							}
						}
					echo '
					</div>
					<div class="form-group">
						<label class="col-sm-6 control-label " for="schooltypeselect">'.get_string('select_schooltype', 'block_exastats').'</label>
						<select class="form-control" id="schooltypeselect" name="schooltype[]" multiple="multiple" size="'.count($schTypes).'">';
							foreach ($schTypes as $ind => $st) {
								echo  '<option value="'.$st.'" '.(in_array($st, $schTypesPost) ? ' selected="selected" ' : '').'>'.$st.'</option>';
							}
						echo '</select>
					</div>
					<div class="form-group">
						<label class="col-sm-6 control-label " for="schoolnumberinput">'.get_string('input_schoolnumber', 'block_exastats').'</label>
						<input class="form-control" id="schoolnumber" name="schoolnumber" value="'.($schoolnumberPost > 0 ? $schoolnumberPost : '').'" />						
					</div>
					';
	$genderVariants = block_exastats_get_variants_from_questionnaire('Geschlecht');
	if (count($genderVariants) > 0) {
		echo '		<div class="form-group">
						<label class="col-sm-6 control-label " for="schoolnumberinput">'.get_string('select_gender', 'block_exastats').'</label>
						<select class="form-control" id="schoolnumberinput" name="gender[]">
							<option value="" '.(!$genderPost ? ' selected="selected" ' : '').'></option>';
						foreach ($genderVariants as $variant) {
							echo '<option value="'.$variant->id.'" '.(in_array($variant->id, $genderPost) ? ' selected="selected" ' : '').'>'.$variant->content.'</option>';
						};
		echo '				<option value="noSelected" '.(in_array('noSelected', $genderPost) ? ' selected="selected" ' : '').'>nicht ausgewählt</option>							
						</select>						
					</div>';
	} else {
		echo '<div class="form-group"> not found any questionnaire variant for using filter "'.get_string('select_gender', 'block_exastats').'"</div>';
	}
	$yearsOnJobVariants = block_exastats_get_variants_from_questionnaire('Schuldienst');
	if (count($yearsOnJobVariants) > 0) {
		echo '		<div class="form-group">
						<label class="col-sm-6 control-label " for="yearsonjobinput">'.get_string('input_years_on_job', 'block_exastats').'</label>
						<select class="form-control" id="schoolnumberinput" name="yearsonjob[]">
							<option value="" '.(!$yearsonjobPost ? ' selected="selected" ' : '').'></option>';
						foreach ($yearsOnJobVariants as $variant) {
							echo '<option value="'.$variant->id.'" '.(in_array($variant->id, $yearsonjobPost) ? ' selected="selected" ' : '').'>'.$variant->content.'</option>';
						};
		echo '				<option value="noSelected" '.(in_array('noSelected', $yearsonjobPost) ? ' selected="selected" ' : '').'>nicht ausgewählt</option>
						</select>						
					</div>';
	} else {
		echo '<div class="form-group"> not found any questionnaire variant for using filter "'.get_string('input_years_on_job', 'block_exastats').'"</div>';
	}
	echo '			<div class="form-group">
						<button type="submit" class="btn btn-default" name="form_submit" value="show">'.get_string('button_show', 'block_exastats').'</button>
						<button type="submit" class="btn btn-default" name="form_submit" value="downloadCsv">'.get_string('button_downloadCsv', 'block_exastats').'</button>
						<button type="submit" class="btn btn-default" name="form_submit" value="notAnsweredSchools">'.get_string('button_notAnsweredSchools', 'block_exastats').'</button>
						<button type="button" class="btn btn-default pull-right" name="clear_filter" id="clear_filter">'.get_string('button_clearFilter', 'block_exastats').'</button>
					</div>
				</form>';
} //<--  end form

if ($formSubmit != 'no' && $formSubmit != 'notAnsweredSchools') {
	// get all categories
	$categories = block_exastats_get_categories($courseid);
	// get schoolids from filter
	$filters = array(
		'regions' => $regionsPost,
		'bezirk' => $bezirkPost,
		'schoolTypes' => $schTypesPost,
		'schoolnumber' => $schoolnumberPost,
		'gender' => $genderPost,
		'yearsonjob' => $yearsonjobPost
	);
	//print_r($filters);
	$schoolids = block_exastats_get_schools_byfilter($filters);
	//print_r($schoolids); exit;
	$users = block_exastats_get_groupusers($schoolids, true, $filters);
	//print_r($users); exit;

	// SHORT RESULTS
	$questionnaireAll_results = block_exastats_get_questionnaire_short_results($courseid, $users);
	$quiz_allresults = block_exastats_get_user_quizzes_short_results($courseid, $users)['quizzes'];
	$all_quiz_statuses = array();
	$categoryAnsweredusers = array();
	$categoryQuizAnsweredusers = array();
	// calculate results for all region users
	foreach ($categories as $categoryKey => $category) {
		$answeredusers = array();
		$categoryAnsweredusers[$categoryKey] = 0;
		$tempUserArray = array();
		// QUESTIONNAIRE
		$questionnair_results[$categoryKey] = array();
		$questionnair_results[$categoryKey]['questions'] = array();
		$all_category_responses[$categoryKey] = array_fill_keys(array(0, 1, 2, 3), '');
		//$questionnair_result = block_exastats_get_questionnaireresults_by_category($courseid, $category, $users);
		$questionnair_result = $questionnaireAll_results[$categoryKey];
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
				$questionnair_results[$categoryKey]['questions'][$questionId]['response'] = array_fill_keys(array(0, 1, 2, 3), '');
				$questionnair_results[$categoryKey]['questions'][$questionId]['responsepercent'] = array_fill_keys(array(0, 1, 2, 3), '');
			}
			// count of question responses
			$questionnair_results[$categoryKey]['questions'][$questionId]['response'][$currentQuestionResponse] += 1; // +1 for needed question response (or for 0:Trifft völlig zu., or for 1:Trifft eher zu. ....)
		}
		// fill percents
		foreach ($answeredusers as $questionId => $countResponses) {
			for ($resp_i = 0; $resp_i <= 3; $resp_i++) {
				if ($countResponses > 0) {
					$questionnair_results[$categoryKey]['questions'][$questionId]['responsepercent'][$resp_i] =
						$questionnair_results[$categoryKey]['questions'][$questionId]['response'][$resp_i] / $countResponses * 100;
				} else {
					$questionnair_results[$categoryKey]['questions'][$questionId]['responsepercent'][$resp_i] = 0;
				}
			}
		}

		// QUIZ
		$answeredusers = array();
		$quiz_results[$categoryKey] = array();
		$quiz_results[$categoryKey]['questions'] = array();
		$categoryQuizAnsweredusers[$categoryKey] = 0;
		$tempUserArray = array();
		$tStart = time();
		//foreach ($users as $userid) {
			$quiz_res = block_exastats_get_quizresults_by_category($courseid, $category, $users);
		//$tEnd = time();
		//$timeAll = $tEnd - $tStart;
		//echo 'time: '.$timeAll.'<br>';
		//exit;
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

} // end calculating if the form is submitted

//display summary results
//echo '!!!!!'.$formSubmit;
switch ($formSubmit) {
	case 'show':
		// $OUTPUT->header already shown with the form!
		echo '<div class="bloks_exastats region_stats">';
		$content = '';
		$content .= '<div>'.get_string('result_count_users', 'block_exastats').' '.count($users).'</div>';
		foreach ($categories as $categoryKey => $category) {
			$content .= '<br>';
			$content .= '<h3>'.$category.'</h3>';
			// bars
			if ($questionnaireAll_results[$categoryKey]['quiestionnairrank'] && $questionnaireAll_results[$categoryKey]['quiestionnairrank'] > 0)
				$questionnair_value = $questionnaireAll_results[$categoryKey]['quiestionnairrank'];
			else
				$questionnair_value = 0;
			if ($quiz_allresults[$categoryKey]['bestgrade']['hasgrade']) {
				$quiz_value = $quiz_allresults[$categoryKey]['bestgrade']['grade'] * 100 / $quiz_allresults[$categoryKey]['grade'];
			} else {
				$quiz_value = 0;
			}

			$content .= '<table class="more_info_bars">
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
			$content .= '<div><a href="#" data-toggle="collapse" data-target="#category'.$categoryKey.'">'.get_string('more', 'block_exastats').'</a></div>';
			//echo '------';
			$content .= '<div class="collapse" id="category'.$categoryKey.'">';

			// questionnair report
			if (isset($questionnair_results[$categoryKey]['questions']) && count($questionnair_results[$categoryKey]['questions']) > 0) {
				$content .= '<table class="table">';
				$content .= '<tr>';
				$content .= '<th></th>';
				$content .= '<th>Trifft völlig zu.</th>';
				$content .= '<th>Trifft eher zu.</th>';
				$content .= '<th>Trifft eher nicht zu.</th>';
				$content .= '<th>Trifft gar nicht zu.</th>';
				$content .= '</tr>';
				foreach ($questionnair_results[$categoryKey]['questions'] as $question) {
					$content .= '<tr>';
					$content .= '<td>'.$question['content'].'</td>';
					$content .= '<td align="center" valign="middle">'.
						$question['response'][0].
						($question['response'][0] > 0 ? ' ('.number_format($question['responsepercent'][0], 2).'%)' : '').
						'</td>';
					$content .= '<td align="center" valign="middle">'.
						$question['response'][1].
						($question['response'][1] > 0 ? ' ('.number_format($question['responsepercent'][1], 2).'%)' : '').
						'</td>';
					$content .= '<td align="center" valign="middle">'.
						$question['response'][2].
						($question['response'][2] > 0 ? ' ('.number_format($question['responsepercent'][2], 2).'%)' : '').
						'</td>';
					$content .= '<td align="center" valign="middle">'.
						$question['response'][3].
						($question['response'][3] > 0 ? ' ('.number_format($question['responsepercent'][3], 2).'%)' : '').
						'</td>';
					$content .= '</tr>';
				}
				// footer
				$content .= '<tr>';
				$content .= '<td colspan="5" align="right"><strong>'.get_string('count_answered_users', 'block_exastats').': '.$categoryAnsweredusers[$categoryKey].'</strong></td>';
				$content .= '</tr>';
				$content .= '</table>';
				$content .= '<br>';
			}  else {
				$content .= get_string('result_questionnaire_notfinished', 'block_exastats');
				$content .= '<br>';
			}

			// quiz report
			if (isset($quiz_results[$categoryKey]['questions']) && count($quiz_results[$categoryKey]['questions']) > 0) {
				$content .= '<table class="table">';
				$content .= '<tr>';
				$content .= '<th>'.get_string('result_questiontable_question', 'block_exastats').'</th>';
				foreach ($all_quiz_statuses as $questionStatus)
					$content .= '<th data-status="'.$questionStatus.'">'.get_string($questionStatus, 'block_exastats').'</th>';
				$content .= '</tr>';
				foreach ($quiz_results[$categoryKey]['questions'] as $question) {
					$content .= '<tr>';
					$content .= '<td>'.$question['questiontext'].'</td>';
					foreach ($all_quiz_statuses as $questionStatus) {
						$content .= '<td>'.
							(isset($question['status'][$questionStatus]) ? $question['status'][$questionStatus] : '').
							(isset($question['status'][$questionStatus]) && $question['status'][$questionStatus] > 0 && isset($question['statuspercent'][$questionStatus]) > 0 ? ' ('.number_format($question['statuspercent'][$questionStatus], 2).'%)' : '').
							'</td>';
					}
					$content .= '</tr>';
				}
				$content .= '<tr>';
				$content .= '<td colspan="4" align="right"><strong>'.get_string('count_answered_users', 'block_exastats').': '.$categoryQuizAnsweredusers[$categoryKey].'</strong></td>';
				$content .= '</tr>';
				$content .= '</table>';
				$content .= '<br>';
			} else {
				$content .= get_string('result_quiz_notfinished', 'block_exastats');
				$content .= '<br>';
			}
			$content .= '</div>';
		}
		echo $content;
		echo '</div>';
		break;
	case 'downloadCsv':
		header('Content-Encoding: UTF-8');
		header('Content-Type: application/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="exastats.csv";');
		echo "\xEF\xBB\xBF"; // UTF-8 BOM
		$delimiter = ';';
		$f = fopen('php://output', 'w');
		fputcsv($f, array(get_string('region_stats', 'block_exastats')), $delimiter);
		if ($regionsPost && count($regionsPost) > 0)
			fputcsv($f, array(implode(', ', $regionsPost)), $delimiter);
		if ($schTypesPost && count($schTypesPost) > 0)
			fputcsv($f, array(implode(', ', $schTypesPost)), $delimiter);
		fputcsv($f, array(''), $delimiter);
		fputcsv($f, array(get_string('result_count_users', 'block_exastats'), count($users)), $delimiter);
		foreach ($categories as $categoryKey => $category) {
			// calculate general data
			if ($questionnaireAll_results[$categoryKey]['quiestionnairrank'] && $questionnaireAll_results[$categoryKey]['quiestionnairrank'] > 0)
				$questionnair_value = $questionnaireAll_results[$categoryKey]['quiestionnairrank'];
			else
				$questionnair_value = 0;
			if ($quiz_allresults[$categoryKey]['bestgrade']['hasgrade']) {
				$quiz_value = $quiz_allresults[$categoryKey]['bestgrade']['grade'] * 100 / $quiz_allresults[$categoryKey]['grade'];
			} else {
				$quiz_value = 0;
			}
			// empty lines
			fputcsv($f, array(''), $delimiter);
			fputcsv($f, array(''), $delimiter);
			// category title
			fputcsv($f, array($category), $delimiter);
			// general category info:
			fputcsv($f, array(get_string('self-assessment', 'block_exastats'), number_format($questionnair_value, 0).'%'), $delimiter);
			fputcsv($f, array(get_string('knowledge', 'block_exastats'), number_format($quiz_value, 0).'%'), $delimiter);
			// detailed information
			// QUESTIONNAIRE
			if (isset($questionnair_results[$categoryKey]['questions']) && count($questionnair_results[$categoryKey]['questions']) > 0) {
				// table header
				fputcsv($f, array('', 'Trifft völlig zu.', 'Trifft eher zu.', 'Trifft eher nicht zu.', 'Trifft gar nicht zu.'), $delimiter);
				foreach ($questionnair_results[$categoryKey]['questions'] as $question) {
					$row = array(
						$question['content'],
						$question['response'][0].($question['response'][0] > 0 ? ' ('.number_format($question['responsepercent'][0], 2).'%)' : ' '),
						$question['response'][1].($question['response'][1] > 0 ? ' ('.number_format($question['responsepercent'][1], 2).'%)' : ' '),
						$question['response'][2].($question['response'][2] > 0 ? ' ('.number_format($question['responsepercent'][2], 2).'%)' : ' '),
						$question['response'][3].($question['response'][3] > 0 ? ' ('.number_format($question['responsepercent'][3], 2).'%)' : ' ')
					);
					fputcsv($f, $row, $delimiter);
				}
				// footer
				fputcsv($f, array(get_string('count_answered_users', 'block_exastats'), $categoryAnsweredusers[$categoryKey]), $delimiter);
			} else {
				fputcsv($f, array(get_string('result_questionnaire_notfinished', 'block_exastats')), $delimiter);
			}

			fputcsv($f, array(''), $delimiter);
			// QUIZ
			if (isset($quiz_results[$categoryKey]['questions']) && count($quiz_results[$categoryKey]['questions']) > 0) {
				// table header
				$headCells = array(get_string('result_questiontable_question', 'block_exastats'));
				foreach ($all_quiz_statuses as $headCell)
					$headCells[] = get_string($headCell, 'block_exastats');
				fputcsv($f, $headCells, $delimiter);
				foreach ($quiz_results[$categoryKey]['questions'] as $question) {
					$row = array($question['questiontext']);
					foreach ($all_quiz_statuses as $questionStatus) {
						$row[] = (isset($question['status'][$questionStatus]) ? $question['status'][$questionStatus] : ' ').
							(isset($question['status'][$questionStatus]) && $question['status'][$questionStatus] > 0 && isset($question['statuspercent'][$questionStatus]) > 0 ? ' ('.number_format($question['statuspercent'][$questionStatus], 2).'%)' : ' ');
					}
					fputcsv($f, $row, $delimiter);
				}
				fputcsv($f, array(get_string('count_answered_users', 'block_exastats'), $categoryQuizAnsweredusers[$categoryKey]), $delimiter);
			} else {
				fputcsv($f, array(get_string('result_quiz_notfinished', 'block_exastats')), $delimiter);
			}

		}
		exit;
		break;
	case 'notAnsweredSchools':
		// get shoolids from filter
		$filters = array(
			'regions' => $regionsPost,
			'schoolTypes' => $schTypesPost
		);
		$allSchoolids = block_exastats_get_schools_byfilter($filters);
		//print_r($schoolids); exit;
		$users = block_exastats_get_groupusers($allSchoolids, true);
		$answeredQuestionnaireSchools = block_exastats_getansweredQuestionnaire_schools($courseid, $users);
		$answeredQuizSchools = block_exastats_getansweredQuiz_schools($courseid, $users);
		// mix of answered
		$allAnswered = array_merge($answeredQuestionnaireSchools, $answeredQuizSchools);
		$allAnswered = array_unique($allAnswered);
		// get NOT answered
		$notAnswered = array_diff($allSchoolids, $allAnswered);
		//echo 'all filtered schools: '.count($allSchoolids).'<br>';
		//echo 'not answered schools: '.count($notAnswered).'<br>';
		//exit;
		// get school data
		if (count($notAnswered) > 0) {
			header('Content-Encoding: UTF-8');
			header('Content-Type: application/csv; charset=UTF-8');
			header('Content-Disposition: attachment; filename="exastats.csv";');
			echo "\xEF\xBB\xBF"; // UTF-8 BOM
			$delimiter = ';';
			$f = fopen('php://output', 'w');
			$sql = ' SELECT * FROM '.$tableSchools.' WHERE schulkennzahl IN ('.implode(',', $notAnswered).') ';
			$sch = $DB->get_records_sql($sql);
			fputcsv($f, array('schulkennzahl', 'schultitel'), $delimiter);
			foreach ($sch as $s) {
				fputcsv($f, array($s->schulkennzahl, $s->schultitel), $delimiter);
			};
		}
		exit;
		break;
}

echo $OUTPUT->footer($course);
