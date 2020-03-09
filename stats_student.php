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

$url = '/blocks/exastats/stats_student.php';
$PAGE->set_url($url, ['courseid' => $courseid]);

$PAGE->set_title(get_string('page_stats_student_title', 'block_exastats'));
$PAGE->set_heading(get_string('pluginname', "block_exastats"));


echo $OUTPUT->header();

echo ' SOON ! ';

echo $OUTPUT->footer($course);


