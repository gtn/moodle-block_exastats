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

class block_exastats_edit_form extends block_edit_form {

	protected function specific_definition($mform) {
	    global $COURSE;

		// Section header title according to language file.
		$mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

		// title
		$mform->addElement('text', 'config_title', get_string('blocktitle', 'block_exastats'));
		$mform->setDefault('config_title', 'Exastats');
		$mform->setType('config_title', PARAM_TEXT);

		// if 'custom_statistic is already added to this page (id of such block)
        $customAlreadyExists = $this->checkUsingCustomStats();

		// what to show
        $options = array(
            'custom_statistic' => get_string('main_block_custom_statistic', 'block_exastats'),
            'moodle_categories' => get_string('main_block_moodle_categories', 'block_exastats')
        );
        $radioattributes = array();
        $radiooptions = array();
        foreach ($options as $key => $opt) {
            if (!($customAlreadyExists && $key == 'custom_statistic' && $this->block->instance->id != $customAlreadyExists)) {
                $radiooptions[] = $mform->createElement('radio', 'config_main_block', '', $opt, $key, $radioattributes);
            }
        }
        $mform->addGroup($radiooptions, 'mainblock_radio', '', array(' '), false);

        if (!($customAlreadyExists && $this->block->instance->id != $customAlreadyExists)) {
            $mform->setDefault('main_block', 'moodle_categories');
        } else {
            $mform->setDefault('main_block', 'custom_statistic');
        }

		// -- FOR custom statistic
		// A type of content for block
		$type_options = array(
			'byRole' => get_string('type_byrole', 'block_exastats'),
			'student' => get_string('type_student', 'block_exastats'),
			'teacher' => get_string('type_teacher', 'block_exastats'),
			'director' => get_string('type_director', 'block_exastats')
		);
		$mform->addElement('select', 'config_viewtype', get_string('view_type', 'block_exastats'), $type_options);
		$mform->setDefault('config_viewtype', 'byRole');
		$mform->getElement('config_viewtype')->setSelected('byRole');

		// -- FOR default "moodle categories" statistic
        // A quiz to show
        $activities = get_array_of_activities($COURSE->id);
        $quizes = array_filter($activities, function($a) {if ($a->mod == 'quiz') return true; else return false;});
        $quizeIds = array('' => '');
        foreach ($quizes as $q) {
            $quizeIds[$q->id] = $q->name;
        }
        if (count($quizeIds) == 1) {
            $mform->addElement('static', 'no_any_quizes_in_course', '', '<span class="alert alert-danger">'.get_string('no_any_quizes_in_course', 'block_exastats').'</span>');
        } else {
            $mform->addElement('select', 'config_quiz', get_string('config_quiz_moodle', 'block_exastats'), $quizeIds);
            $mform->setDefault('config_quiz', '');
            //$mform->getElement('config_quiz')->setSelected(0);
        }

        // Manage groups of setting:
        $onlyForCustom = array('config_viewtype');
        $onlyForMoodleCats = array('no_any_quizes_in_course', 'config_quiz');
        $disableElements =  function($mainBlockValue, $arrayOfElements) use ($mform) {
            foreach ($arrayOfElements as $paramName) {
                $mform->disabledIf($paramName, 'config_main_block', 'neq', $mainBlockValue);
            }
        };
        $disableElements('custom_statistic', $onlyForCustom);
        $disableElements('moodle_categories', $onlyForMoodleCats);

	}

    public function checkUsingCustomStats() {
        $blockManager = $this->page->blocks;
        $regions = $blockManager->get_regions();
        foreach ($regions as $rKey => $region) {
            $blocks = $blockManager->get_blocks_for_region($region);
            foreach ($blocks as $kB => $blockObj) {
                $blockName = get_class($blockObj);
                if ($blockName == 'block_exastats') {
                    $conf = $blockObj->config;
                    if ($conf && $conf->main_block == 'custom_statistic') {
                        return $blockObj->instance->id;
                    }
                }
            }
        }
        return false;
    }

}