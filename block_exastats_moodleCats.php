<?php
// This file is part of Quiz Exastats plugin for Moodle
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

class block_exastats_moodleCats extends block_list {

	public function init($config = null) {
		$this->title = get_string('exastats', 'block_exastats');
		if ($config) {
		    $this->config = $config;
        }
	}

	function instance_allow_multiple() {
		return true;
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
				$this->config->viewtype = 'student';
			}
		}
	}

	public function get_content() {
		global $CFG, $COURSE, $OUTPUT, $DB, $USER;

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
		if (!$courseid) {
            $courseid = optional_param('course', 0, PARAM_INT);
        }

		$this->content =  new stdClass;
		$this->content->items = array();
		$this->content->icons = array();
		$this->content->footer = '';

		$output = block_exastats_get_renderer();
        $quizid = null;
        if ($this->config && $this->config->quiz) {
            $quizid = $this->config->quiz;
        }

		if (!isset($quizid)) { // select quiz
		    if (is_siteadmin($USER)) {
                $view = 'admin_settings';
            } else {
                $this->content->items[] = get_string('no_quizes', 'block_exastats');
            }
        } else {
            if (is_siteadmin($USER)) {
                $view = 'admin';
            } else {
                $view = 'student';
            }
        }
        //if ($this->config && $this->config->viewtype) {
        //    $view = $this->config->viewtype;
        //}
        if (!isset($view)) {
            $view = '';
        }

        $block_content = '';

		switch ($view) {
			case 'student':
			    
                $stat = block_exastats_get_user_quizzes_short_results_with_categories($quizid, $USER->id);
                $block_content = block_exastats_short_view_fromstatdata($stat);
                $this->content->items[] = $block_content;

				//$bottomLinks = '';
				//$icon = '<img src="'.$output->image_url('stats', 'block_exastats').'" class="icon" alt="" />';
				//$this->content->items[] = '<a title="'.get_string('link_student', 'block_exastats').'" href="'.$CFG->wwwroot.'/blocks/exastats/stats_student.php?courseid='.$COURSE->id.'">'.$icon.get_string('link_student', 'block_exastats').'</a>';
                //$bottomLinks .= '<div class="more_info_link"><a title="'.get_string('more', 'block_exastats').'" href="'.$CFG->wwwroot.'/blocks/exastats/stats_common.php?courseid='.$COURSE->id.'">'.$icon.get_string('more', 'block_exastats').'</a></div>';
                //$this->content->items[] = $bottomLinks;
				break;
			case 'admin' :

			    // get students
                $students = block_exastats_get_course_students($courseid);
                if (count($students) > 0) {
                    //$students = array(48534, $USER->id);
                    
                    $stat = block_exastats_get_user_quizzes_short_results_with_categories($quizid, $students);
                    //echo "<pre>debug:<strong>block_exastats_moodleCats.php:132</strong>\r\n"; print_r($stat); echo '</pre>'; exit; // !!!!!!!!!! delete it
                    $block_content = block_exastats_short_view_fromstatdata($stat);
                    $this->content->items[] = $block_content;
                    $this->content->items[] = get_string('students_count', 'block_exastats', count($students));

                    // bottom links
                    if (!array_key_exists('action', $stat) || $stat['action'] != 'hide_bottom_links') {
                        $bottomLinks = '';
                        $icon = '<img src="'.$output->image_url('stats', 'block_exastats').'" class="icon" alt="" />';
                        $bottomLinks .= '<div class="more_info_link"><a title="'.
                                get_string('detail_quiz_statistic_link', 'block_exastats').'" href="'.$CFG->wwwroot.
                                '/blocks/exastats/detail_quiz_statistic.php?courseid='.$COURSE->id.'&quizid='.$quizid.'">'.$icon.
                                get_string('detail_quiz_statistic_link', 'block_exastats').'</a></div>';
                        //$bottomLinks .= '<div><a title="'.get_string('group_stats', 'block_exastats').'" href="'.$CFG->wwwroot.'/blocks/exastats/stats_group.php?courseid='.$COURSE->id.'">'.$icon.get_string('group_stats', 'block_exastats').'</a></div>';
                        $this->content->items[] = $bottomLinks;
                    }
                } else {
                    $this->content->items[] = get_string('no_students_in_course', 'block_exastats');
                }
				break;
            case 'admin_settings':
                $this->content->items[] = '<p>'.get_string('configure_block', 'block_exastats').'</p>';
                break;
			default:
				//$this->content->items[] = '<p>'.get_string('access_to_statistics', 'block_exastats').'</p>';
		}

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