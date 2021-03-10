<?php

require_once __DIR__.'/config.php';

$content = '';

$getParams = $_GET;

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

// get course
if (isset($getParams['course']) && $getParams['course'] > 0)
	$courseid = $getParams['course'];
else
	$courseid = 88; //88 - live; 7 - my
// get form submit button
if (isset($getParams['form_submit']) && $getParams['form_submit'] != '')
	$formSubmit = $getParams['form_submit'];
else
	$formSubmit = 'no';
// get schoolnumber
if (isset($getParams['schoolnumber']) && $getParams['schoolnumber'] != '')
	$schoolnumberGet = $getParams['schoolnumber'];
else
	$schoolnumberGet = 0;
// get region
if (isset($getParams['region']) && $getParams['region'] != '')
	$regionsGet = $getParams['region'];
else
	$regionsGet = array();
// get bezirk
if (isset($getParams['bezirk']) && $getParams['bezirk'] != '')
	$bezirkGet = $getParams['bezirk'];
else
	$bezirkGet = array();
// get schooltype
if (isset($getParams['schooltype']) && $getParams['schooltype'] != '')
	$schTypesGet = $getParams['schooltype'];
else
	$schTypesGet = array();

// get token
if (isset($getParams['token']) && $getParams['token'] != '')
	$tokenGet = $getParams['token'];
else
	$tokenGet = '';

$toExit = false;
if ($formSubmit != 'no' && $tokenGet != $token) {
	echo ' WRONG TOKEN !!!!!!!!';
	$toExit = true;
}


// show FORM
if ($formSubmit != 'downloadCsv' && $formSubmit != 'notAnsweredSchools') {
	echo getHeader();
	echo  '<h2>'.get_string('region_stats', 'block_exastats').'</h2>';

	$regions = $bundeslands;
	$schTypes = array_keys($schultypen);
	//$schTypes = $schultypen;
	// form
	showForm();
} //<--  end form

if ($toExit)
	exit;

if ($formSubmit != 'no' && $formSubmit != 'notAnsweredSchools') {
	// get all categories
	$categories = blockVendor_exastats_get_categories($courseid);
	// get shoolids from filter
	$filters = array(
		'regions' => $regionsGet,
		'bezirk' => $bezirkGet,
		'schoolTypes' => $schTypesGet,
		'schoolnumber' => $schoolnumberGet
	);
	//print_r($filters);
	$schoolids = blockVendor_exastats_get_schools_byfilter($filters);
	//echo '<pre style="display:none;">';
	//print_r($schoolids); exit;
	//echo implode(',', $schoolids); exit;
	//echo '</pre>';
	$users = blockVendor_exastats_get_groupusers($schoolids, true);
	//echo '<pre style="display:none;">';
	//echo count($users).'===';print_r($users); exit;
	//echo '</pre>';

	// SHORT RESULTS
	$questionnaireAll_results = blockVendor_exastats_get_questionnaire_short_results($courseid, $users);
	//print_r($questionnaireAll_results); exit;
	//exit;
	$quiz_allresults = blockVendor_exastats_get_user_quizzes_short_results($courseid, $users)['quizzes'];
	//print_r($quiz_allresults); exit;
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
		$all_category_responses[$categoryKey] = $responsesEmptyArray;
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
				$questionnair_results[$categoryKey]['questions'][$questionId]['response'] = $responsesEmptyArray;
				$questionnair_results[$categoryKey]['questions'][$questionId]['responsepercent'] = $responsesEmptyArray;
			}
			// count of question responses
			$questionnair_results[$categoryKey]['questions'][$questionId]['response'][$currentQuestionResponse] += 1; // +1 for needed question response (or for 0:Trifft völlig zu., or for 1:Trifft eher zu. ....)
		}
		// fill percents
		foreach ($answeredusers as $questionId => $countResponses) {
			for ($resp_i = $minRespVal; $resp_i <= $maxRespVal; $resp_i++) {
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
		$quiz_res = blockVendor_exastats_get_quizresults_by_category($courseid, $category, $users);
		//echo count($quiz_res['questions']);
		//exit;
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
		//print_r($categories);
		foreach ($categories as $categoryKey => $category) {
			$content .= '<br>';
			$content .= '<h3>'.$category.'</h3>';
			// bars
			if ($questionnaireAll_results[$categoryKey]['quiestionnairrank'] && $questionnaireAll_results[$categoryKey]['quiestionnairrank'] > 0) {
				$questionnair_value = $questionnaireAll_results[$categoryKey]['quiestionnairrank'];
			} else {
				$questionnair_value = 0;
			}
			if (isset($quiz_allresults[$categoryKey]['bestgrade']['hasgrade']) && $quiz_allresults[$categoryKey]['bestgrade']['hasgrade']) {
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
								<div class="self-bar" style="background-color:#385924; height: 5px; width: '.number_format($questionnair_value, 0).'%;">&nbsp;</div>
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
								<div class="knowledge-bar" style="background-color:#f7c810; height: 5px; width: '.number_format($quiz_value, 0).'%;">&nbsp;</div>
							</div>
						</td>
						<td class="percent_value">'.
				number_format($quiz_value, 0).'%
						</td>
					</tr>			
				</table>';
			//$content .= '<div><a href="#" data-toggle="collapse" data-target="#category'.$categoryKey.'">'.get_string('more', 'block_exastats').'</a></div>';
			//echo '------';
			$content .= '<div class="collapse" id="category'.$categoryKey.'">';

			// questionnair report
			if (isset($questionnair_results[$categoryKey]['questions']) && count($questionnair_results[$categoryKey]['questions']) > 0) {
				$content .= '<table class="table" width="100%">';
				$content .= '<tr>';
				$content .= '<th width="50%"></th>';
				$content .= '<th>Trifft völlig zu.</th>';
				$content .= '<th>Trifft eher zu.</th>';
				$content .= '<th>Trifft eher nicht zu.</th>';
				$content .= '<th>Trifft gar nicht zu.</th>';
				$content .= '</tr>';
				foreach ($questionnair_results[$categoryKey]['questions'] as $question) {
					$content .= '<tr>';
					$content .= '<td>'.$question['content'].'</td>';
					$content .= '<td align="center" valign="top">'.
						$question['response'][0 + $responseKoeff].
						($question['response'][0 + $responseKoeff] > 0 ? ' ('.number_format($question['responsepercent'][0 + $responseKoeff], 2).'%)' : '').
						'</td>';
					$content .= '<td align="center" valign="top">'.
						$question['response'][1 + $responseKoeff].
						($question['response'][1 + $responseKoeff] > 0 ? ' ('.number_format($question['responsepercent'][1 + $responseKoeff], 2).'%)' : '').
						'</td>';
					$content .= '<td align="center" valign="top">'.
						$question['response'][2 + $responseKoeff].
						($question['response'][2 + $responseKoeff] > 0 ? ' ('.number_format($question['responsepercent'][2 + $responseKoeff], 2).'%)' : '').
						'</td>';
					$content .= '<td align="center" valign="top">'.
						$question['response'][3 + $responseKoeff].
						($question['response'][3 + $responseKoeff] > 0 ? ' ('.number_format($question['responsepercent'][3 + $responseKoeff], 2).'%)' : '').
						'</td>';
					$content .= '</tr>';
				}
				// footer
				$content .= '<tr>';
				$content .= '<td colspan="5" align="right"><strong>'.get_string('count_answered_users', 'block_exastats').': '.$categoryAnsweredusers[$categoryKey].'</strong></td>';
				$content .= '</tr>';
				$content .= '</table>';
				$content .= '<br>';
			} else {
				$content .= get_string('result_questionnaire_notfinished', 'block_exastats');
				$content .= '<br>';
			}

			// quiz report
			if (isset($quiz_results[$categoryKey]['questions']) && count($quiz_results[$categoryKey]['questions']) > 0) {
				$content .= '<table class="table" width="100%">';
				$content .= '<tr>';
				$content .= '<th width="50%" align="left">'.get_string('result_questiontable_question', 'block_exastats').'</th>';
				foreach ($all_quiz_statuses as $questionStatus) {
					$content .= '<th align="left" data-status="'.$questionStatus.'">'.get_string($questionStatus, 'block_exastats').'</th>';
				}
				$content .= '</tr>';
				foreach ($quiz_results[$categoryKey]['questions'] as $question) {
					$content .= '<tr>';
					$content .= '<td width="50%">'.$question['questiontext'].'</td>';
					foreach ($all_quiz_statuses as $questionStatus) {
						$content .= '<td valign="top">'.
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
		if ($regionsGet && count($regionsGet) > 0) {
			fputcsv($f, array(implode(', ', $regionsGet)), $delimiter);
		}
		if ($schTypesGet && count($schTypesGet) > 0) {
			fputcsv($f, array(implode(', ', $schTypesGet)), $delimiter);
		}
		fputcsv($f, array(''), $delimiter);
		fputcsv($f, array(get_string('result_count_users', 'block_exastats'), count($users)), $delimiter);
		foreach ($categories as $categoryKey => $category) {
			// calculate general data
			if ($questionnaireAll_results[$categoryKey]['quiestionnairrank'] && $questionnaireAll_results[$categoryKey]['quiestionnairrank'] > 0) {
				$questionnair_value = $questionnaireAll_results[$categoryKey]['quiestionnairrank'];
			} else {
				$questionnair_value = 0;
			}
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
						$question['response'][0 + $responseKoeff].($question['response'][0 + $responseKoeff] > 0 ? ' ('.number_format($question['responsepercent'][0 + $responseKoeff], 2).'%)' : ' '),
						$question['response'][1 + $responseKoeff].($question['response'][1 + $responseKoeff] > 0 ? ' ('.number_format($question['responsepercent'][1 + $responseKoeff], 2).'%)' : ' '),
						$question['response'][2 + $responseKoeff].($question['response'][2 + $responseKoeff] > 0 ? ' ('.number_format($question['responsepercent'][2 + $responseKoeff], 2).'%)' : ' '),
						$question['response'][3 + $responseKoeff].($question['response'][3 + $responseKoeff] > 0 ? ' ('.number_format($question['responsepercent'][3 + $responseKoeff], 2).'%)' : ' ')
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
				foreach ($all_quiz_statuses as $headCell) {
					$headCells[] = get_string($headCell, 'block_exastats');
				}
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
			'regions' => $regionsGet,
			'schoolTypes' => $schTypesGet
		);
		$allSchoolids = blockVendor_exastats_get_schools_byfilter($filters);
		//print_r($schoolids); exit;
		$users = blockVendor_exastats_get_groupusers($allSchoolids, true);
		$answeredQuestionnaireSchools = blockVendor_exastats_getansweredQuestionnaire_schools($courseid, $users);
		$answeredQuizSchools = blockVendor_exastats_getansweredQuiz_schools($courseid, $users);
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
			fputcsv($f, array('schulkennzahl', 'schultitel'), $delimiter);
			$sql = ' SELECT * FROM '.$tableSchools.' WHERE schulkennzahl IN ('.implode(',', $notAnswered).') ';
			$sch = $mysqli->query($sql);
			if ($sch) {
				while ($s = $sch->fetch_assoc()) {
					fputcsv($f, array($s['schulkennzahl'], $s['schultitel']), $delimiter);
				}
			}
		} else {
			echo getHeader();
			echo  '<h2>'.get_string('region_stats', 'block_exastats').'</h2>';
			$regions = $bundeslands;
			$schTypes = array_keys($schultypen);
			//$schTypes = $schultypen;
			// form
			showForm();
			echo '<span style="color:red;">'.get_string('noRecords_schools', 'block_exastats').'</span>';
		}
		exit;
		break;
}

echo getFooter();

function showForm() {
	global $courseid, $tokenGet, $regionsGet, $regions, $bezirkGet, $bezirkList, $schTypesGet, $schTypes, $schoolnumberGet;
	echo '
		<script>
			function regionChanged() {		
				// hide all bezirke
				var bezirks = document.getElementsByClassName("bezirkSelectBox");			
				for (var i = 0; i < bezirks.length; i++){
					bezirks[i].style.display = "none";
					var options = bezirks[i].options;
					for(var j = 0; j < options.length; j++){
					  bezirks[i].options[j].selected = false;
					}
				};
				var row = document.getElementById("bezirkRow");
				row.style.display = "none";							
				// hide row
				var regionselect = document.getElementById("regionselect");
				var selectedRegions = []
				for (var i = 0; i < regionselect.length; i++) {
                    if (regionselect.options[i].selected) selectedRegions.push(regionselect.options[i].value);
                }                
                if (selectedRegions.length == 1) {
					var bezirkSelect = document.getElementById("bezirkselect"+selectedRegions[0]);					
					if (bezirkSelect) {						
						if (row.style.display === "none") {
							row.style.display = "table-row";
						}
						if (bezirkSelect.style.display === "none") {
							bezirkSelect.style.display = "block";
						} 												
					}
				} 
			}		
		</script>
		<form method="get">
			<table border="0">
					<tr>						
						<td>
							course:
							<input class="form-control" id="course" name="course" value="'.($courseid > 0 ? $courseid : '').'" placeholder="course"/><br>
					    </td>
					    <td>
							token:
							<input class="form-control" id="token" name="token" value="'.($tokenGet != '' ? $tokenGet : '').'" placeholder="!!! token !!!"/>
						</td>
					</tr>
					<tr>
						<td valign="top">
							<label class="col-sm-6 control-label " for="regionselect">'.get_string('select_region', 'block_exastats').'</label>
						</td>
						<td>
							<select class="form-control" id="regionselect" name="region[]" multiple="multiple" size="'.count($regions).'" onChange="regionChanged();">';
							foreach ($regions as $ind => $reg) {
								echo  '<option value="'.$ind.'" '.(in_array($ind, $regionsGet) ? ' selected="selected" ' : '').'>'.$reg.'</option>';
							}
							echo '</select>
						</td>
					</tr>
					<tr id="bezirkRow" '.(count($bezirkGet) || (count($regionsGet) == 1 && isset($bezirkList[$regionsGet[0]])) > 0 ? "" : 'style="display: none;"').' >
						<td valign="top">
							<label class="col-sm-6 control-label " for="bezirkselect" >'.get_string('select_bezirk', 'block_exastats').'</label>
						</td>
						<td>';
						foreach($regions as $rInd => $reg) {
							if (isset($bezirkList[$rInd]) && is_array($bezirkList[$rInd])) {
								echo '<select class="form-control bezirkSelectBox" id="bezirkselect'.$rInd.'" data-region="'.$rInd.'" name="bezirk[]" multiple="multiple" size="'.count($bezirkList[$rInd]).'">';
								foreach ($bezirkList[$rInd] as $bInd => $bez) {
									echo '<option value="'.$bInd.'" '.(in_array($bInd, $bezirkGet) ? ' selected="selected" ' : '').'>'.$bez.'</option>';
								}
								echo '</select>';
							}
						}
						echo '</td>
					</tr>
					<tr>
						<td valign="top">
							<label class="col-sm-6 control-label " for="schooltypeselect">'.get_string('select_schooltype', 'block_exastats').'</label>
						</td>
						<td>
							<select class="form-control" id="schooltypeselect" name="schooltype[]" multiple="multiple" size="'.count($schTypes).'">';
							foreach ($schTypes as $ind => $st) {
								echo  '<option value="'.$st.'" '.(in_array($st, $schTypesGet) ? ' selected="selected" ' : '').'>'.$st.'</option>';
							}
							echo '</select>
						</td>
					</tr>
					<tr>
						<td valign="top">
							<label class="col-sm-6 control-label " for="schoolnumberinput">'.get_string('input_schoolnumber', 'block_exastats').'</label>
						</td>
						<td>
							<input class="form-control" id="schoolnumber" name="schoolnumber" value="'.($schoolnumberGet > 0 ? $schoolnumberGet : '').'" />
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<button type="submit" class="btn btn-default" name="form_submit" value="show">'.get_string('button_show', 'block_exastats').'</button>
							<button type="submit" class="btn btn-default" name="form_submit" value="downloadCsv">'.get_string('button_downloadCsv', 'block_exastats').'</button>
							<button type="submit" class="btn btn-default" name="form_submit" value="notAnsweredSchools">'.get_string('button_notAnsweredSchools', 'block_exastats').'</button>
							<!--<button type="button" class="btn btn-default pull-right" name="clear_filter" id="clear_filter">'.get_string('button_clearFilter', 'block_exastats').'</button>-->
						</td>
					</tr>
					</table>
				</form>';
}