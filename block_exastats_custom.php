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

ini_set('memory_limit','512M');
require __DIR__.'/inc.php';

//class block_exastats extends block_base {
class block_exastats_custom extends block_list {

	public function init($config = null) {
		$this->title = get_string('exastats', 'block_exastats');
        if ($config) {
            $this->config = $config;
        }
	}

	function instance_allow_multiple() {
		return false;
	}

	function instance_allow_config() {
		return false;
	}

	function has_config() {
		return true;
	}


	public function specialization() {
		if (isset($this->config)) {
			if (empty($this->config->title)) {
				$this->title = get_string('defaulttitle', 'block_exastats');
			} else {
				$this->title = $this->config->title;
			}
			if (empty($this->config->viewtype)) {
				$this->config->viewtype = 'teacher';
			}
		}
	}

	public function get_content() {
		global $CFG, $COURSE, $OUTPUT, $DB, $USER, $minUsersForReport, $minAnsweredUsersForReport;

		$context = context_system::instance();
		if (!has_capability('block/exastats:use', $context)) {
			$this->content = '';
			return $this->content;
		}

		if ($this->content !== null) {
			return $this->content;
		}

		// get course
		$courseid = optional_param('id', 0, PARAM_INT);
		// if it is quiz editing view - the course will be as course =
		if (!$courseid)
			$courseid = optional_param('course', 0, PARAM_INT);
		//$conditions = array("id" => $courseid);
		//if (!$course = $DB->get_record("course", $conditions)) {
		//	print_error("invalidinstance", "block_exastats");
		//}

		$this->content =  new stdClass;
		$this->content->items = array();
		$this->content->icons = array();
		$this->content->footer = '';

		$output = block_exastats_get_renderer();

		if ($this->config && $this->config->viewtype)
			$view = $this->config->viewtype;
		if (!isset($view) || $view == 'byRole')
			$view = block_exastats_get_role_byusername(); //'byRole';  // for new block (TODO: why default settings is not working?)
		//echo '<span style="display: none;">';
		//echo 'config:';
		//print_r($this->config);
		//echo "\r\n".'role:'.$view.'</span>';

		// fast solution for default view as 'teacher'
		// TODO: find a reason of this thing
		//if ($view != 'director' && $view != 'teacher')
			$view = 'common'; // TEST!! HIDE in production

		switch ($view) {
			case 'common':
				//$this->content->items[] = ' <h2> T e a c h e r !</h2>';
				$questionnaire_results = block_exastats_get_questionnaire_short_results($courseid, array($USER->id));
				//print_r($questionnaire_results); exit;
				// get results of filled quiz
				$quiz_results = block_exastats_get_user_quizzes_short_results($courseid, array($USER->id));
				$block_content = '';
				$block_content .= '<table class="block_exastats_summary_ranks">';
				/*$block_content .= '<tr><th colspan="2">'.get_string('category', 'block_exastats').'</th>
										<th>'.get_string('self-assessment-short', 'block_exastats').'</th>
										<th>'.get_string('knowledge-short', 'block_exastats').'</th></tr>';*/
				foreach ($quiz_results['quizzes'] as $quiz) {
					$categoryInd = substr(trim($quiz['name']), 0, 1);
					if ($questionnaire_results[$categoryInd]['quiestionnairrank'] && $questionnaire_results[$categoryInd]['quiestionnairrank'] > 0)
						$questionnair_value = $questionnaire_results[$categoryInd]['quiestionnairrank'];
					else
						$questionnair_value = 0;
					if ($quiz['bestgrade']['hasgrade']) {
						$quiz_value = $quiz['bestgrade']['grade'] * 100 / $quiz['grade'];
					} else {
						$quiz_value = 0;
					}
					// table view
					/*$q_icon = '<img src="'.$output->image_url('icon', 'mod_quiz').'" class="icon" alt="" />';
					$quiz_text = '<td valign="top" class="exastats_icon_cell">'.$q_icon.'</td>';
					$quiz_text .= '<td valign="top" class="exastats_quiz_name">'.$quiz['name'].'</td>
								  <td valign="top" class="exastats_quiz_grade">'.number_format($questionnair_value, 0).'%</td>
								  <td valign="top" class="exastats_quiz_grade">'.number_format($quiz_value, 0).'%</td>';
					//$this->content->items[] = $quiz_text;
					$block_content .= '<tr>'.$quiz_text.'</tr>';*/
					// bar view
					$block_content .= '<tr class="category_name">
											<td valign="top" colspan="2">'.$quiz['name'].'</td>
										</tr>';
					$block_content .= '<tr>';
					$block_content .= '<td valign="middle" width="99%">
											<span class="bar-name">'.get_string('self-assessment', 'block_exastats').'</span>
											<div class="bar-container">
												<div class="self-bar" style="width: '.number_format($questionnair_value, 0).'%;">&nbsp;</div>
											</div>
										</td>
										<td class="percent_value">'.
						number_format($questionnair_value, 0).'%
										</td>';
					$block_content .= '</tr>';
					$block_content .= '<tr>';
					$block_content .= '<td valign="middle">
												<span class="bar-name">'.get_string('knowledge', 'block_exastats').'</span>
											<div class="bar-container">
												<div class="knowledge-bar" style="width: '.number_format($quiz_value, 0).'%;"></div>
											</div>
										</td>
										<td class="percent_value">'.
						number_format($quiz_value, 0).
						'%</td>';
					$block_content .= '</tr>';
				}
				$block_content .= '</table>';
				$this->content->items[] = $block_content;
				$bottomLinks = '';
				$icon = '<img src="'.$output->image_url('stats', 'block_exastats').'" class="icon" alt="" />';
				//$this->content->items[] = '<a title="'.get_string('link_student', 'block_exastats').'" href="'.$CFG->wwwroot.'/blocks/exastats/stats_student.php?courseid='.$COURSE->id.'">'.$icon.get_string('link_student', 'block_exastats').'</a>';
                $bottomLinks .= '<div class="more_info_link"><a title="'.get_string('more', 'block_exastats').'" href="'.$CFG->wwwroot.'/blocks/exastats/stats_common.php?courseid='.$COURSE->id.'">'.$icon.get_string('more', 'block_exastats').'</a></div>';
                $bottomLinks .= '<div><a title="'.get_string('group_stats', 'block_exastats').'" href="'.$CFG->wwwroot.'/blocks/exastats/stats_group.php?courseid='.$COURSE->id.'">'.$icon.get_string('group_stats', 'block_exastats').'</a></div>';
                $this->content->items[] = $bottomLinks;
				break;
			case 'director':
				//$this->content->items[] = ' <h2> D i r e c t o r !</h2>';
				$schoolid = block_exastats_get_schoolid_byusername();
				$users = block_exastats_get_groupusers($schoolid, true);
				if (count($users) >= $minUsersForReport) {
					$quiz_results = block_exastats_get_user_quizzes_short_results($courseid);
					$questionnaire_results = block_exastats_get_questionnaire_short_results($courseid);
					$block_content = '';
					$block_content .= '<table class="block_exastats_summary_ranks">';
					/*$block_content .= '<tr><th>'.get_string('category', 'block_exastats').'</th>
											<th>'.get_string('self-assessment-short', 'block_exastats').'</th>
											<th>'.get_string('knowledge-short', 'block_exastats').'</th>
										</tr>';*/
					foreach ($questionnaire_results as $categoryKey => $result) {
						$self_global_value = $result['quiestionnairrank'];
						if (isset($quiz_results['quizzes'][$categoryKey]['bestgrade']['hasgrade'])) {
							$knowledge_global_value = $quiz_results['quizzes'][$categoryKey]['bestgrade']['grade'] * 100 / $quiz_results['quizzes'][$categoryKey]['grade'];
						} else {
							$knowledge_global_value = 0;
						}
						// table view
						/*$block_content .= '<tr>';
						$block_content .= '<td valign="top">'.$result['category_name'].'</td>';
						$block_content .= '<td valign="top">'.number_format($self_global_value, 0).'%</td>';
						$block_content .= '<td valign="top">'.number_format($knowledge_global_value, 0).'%</td>';
						$block_content .= '</tr>';*/
						// bar view
						$block_content .= '<tr class="category_name">
											<td valign="top" colspan="2">'.$result['category_name'].'</td>
										</tr>';
						$block_content .= '<tr>';
						$block_content .= '<td valign="middle" width="99%">
											<span class="bar-name">'.get_string('self-assessment', 'block_exastats').'</span>
											<div class="bar-container">
												<div class="self-bar" style="width: '.number_format($self_global_value, 0).'%;">&nbsp;</div>
											</div>
										</td>
										<td class="percent_value">'.
							number_format($self_global_value, 0).'%
										</td>';
						$block_content .= '</tr>';
						$block_content .= '<tr>';
						$block_content .= '<td valign="middle">
											<span class="bar-name">'.get_string('knowledge', 'block_exastats').'</span>
											<div class="bar-container">
												<div class="knowledge-bar" style="width: '.number_format($knowledge_global_value, 0).'%;"></div>
											</div>
										</td>
										<td class="percent_value">'.
							number_format($knowledge_global_value, 0).
							'%</td>';
						$block_content .= '</tr>';
					}
					$block_content .= '</table>';
				} else {
					$block_content = get_string('hiddenByAnonymity', 'block_exastats');
					$block_content .= '<br />';
				}
				$this->content->items[] = $block_content;
                $bottomLinks = '';
				$icon = '<img src="'.$output->image_url('stats', 'block_exastats').'" class="icon" alt="" />';
                $bottomLinks .= '<div class="more_info_link"><a title="'.get_string('more', 'block_exastats').'" href="'.$CFG->wwwroot.'/blocks/exastats/stats_director.php?courseid='.$COURSE->id.'">'.$icon.get_string('more', 'block_exastats').'</a></div>';
                $bottomLinks .= '<div><a title="'.get_string('group_stats', 'block_exastats').'" href="'.$CFG->wwwroot.'/blocks/exastats/stats_group.php?courseid='.$COURSE->id.'">'.$icon.get_string('group_stats', 'block_exastats').'</a></div>';
                $this->content->items[] = $bottomLinks;
				break;
			case 'admin' :
                $bottomLinks = '';
				$icon = '<img src="'.$output->image_url('stats', 'block_exastats').'" class="icon" alt="" />';
                $bottomLinks .= '<div class="more_info_link"><a title="'.get_string('region_statistic_link', 'block_exastats').'" href="'.$CFG->wwwroot.'/blocks/exastats/stats_region.php?courseid='.$COURSE->id.'">'.$icon.get_string('region_statistic_link', 'block_exastats').'</a></div>';
                $bottomLinks .= '<div><a title="'.get_string('group_stats', 'block_exastats').'" href="'.$CFG->wwwroot.'/blocks/exastats/stats_group.php?courseid='.$COURSE->id.'">'.$icon.get_string('group_stats', 'block_exastats').'</a></div>';
                $this->content->items[] = $bottomLinks;
				break;
			default:
				//$this->content->text   = ' <h2> E x a s t a t s !</h2>';
				$this->content->items[] = '<p>'.get_string('access_to_statistics', 'block_exastats').'</p>';
		}

		//$this->content->items[] = html_writer::tag('a', get_string('link_student', 'block_exastats'), array('href' => $CFG->wwwroot.'/blocks/exastats/111111111111.php?courseid='.$COURSE->id));
		//$this->content->icons[] = html_writer::empty_tag('img', array('src' => $output->image_url('stats', 'block_exastats'), 'class' => 'icon'));

		return $this->content;
	}

	public function applicable_formats() {
		return array(
			'course-view' => true,
			'mod' => false
			//'mod-questionnaire' => false
		);
	}


}