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
class block_exastats extends block_list {

	public function init() {
		$this->title = get_string('exastats', 'block_exastats');
	}

	function instance_allow_multiple() {
		return true; // TODO: true for 'moodle categories' and false for 'custom'
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
		global $CFG, $COURSE, $OUTPUT, $DB, $USER, $minUsersForReport, $minAnsweredUsersForReport, $PAGE;

		$context = context_system::instance();
		if (!has_capability('block/exastats:use', $context)) {
			$this->content = '';
			//return $this->content;
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

        $output = block_exastats_get_renderer();

        $this->content =  new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        $main_block = '';
        if (@$this->config->main_block) {
            $main_block = $this->config->main_block;
        }

        //$customAlreadyExististing = $this->checkUsingCustomStats();

        switch ($main_block) {
            case 'custom_statistic':
                // for custom statistic
                require_once(__DIR__.'/block_exastats_custom.php');
                $mainBlock = new block_exastats_custom();
                $mainBlock->init($this->config);
                $this->content = $mainBlock->get_content();
                break;
            case 'moodle_categories':
                // for default 'Moodle categories'
                require_once(__DIR__.'/block_exastats_moodleCats.php');
                $mainBlock = new block_exastats_moodleCats();
                $mainBlock->init($this->config);
                $this->content = $mainBlock->get_content();
                break;
            default:
                // no configurated block yet
                //$this->content->icons[] = $output->pix_url('i/info');
                //$this->content->items[] = html_writer::img(new pix_icon('i/info', 'block_exastats'), ' ').'&nbsp;13123123';
                if (is_siteadmin()) { // message is only for Admin
                    $icon = $output->pix_icon('i/info', ' ');
                    $sesskey = sesskey();
                    $url = new moodle_url($PAGE->url, array('id' => $courseid, 'sesskey' => $sesskey, 'bui_editid' => $this->instance->id));
                    $this->content->items[] = $icon.'&nbsp;'.html_writer::link($url, get_string('configure_block', 'block_exastats'));
                } else { // empty non-configurated block for non-admins
                    $this->content->items[] = '';
                }
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