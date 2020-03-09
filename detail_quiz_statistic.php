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

require_once __DIR__.'/inc.php';

$courseid = optional_param('courseid', 0, PARAM_INT);
$quizid = required_param('quizid', PARAM_INT);

$conditions = array("id" => $courseid);
if (!$course = $DB->get_record("course", $conditions)) {
    print_error("invalidinstance", "block_exastats");
}

$context = context_system::instance();
$PAGE->set_context($context);
require_login($courseid);
if (!is_siteadmin()) {
    print_error("only_for_admins", "block_exastats");
}


$url = '/blocks/exastats/detail_quiz_statistic.php';
$PAGE->set_url($url, ['courseid' => $courseid, 'quizid' => $quizid]);

$PAGE->set_title(get_string('page_detail_quiz_title', 'block_exastats'));
$PAGE->set_heading(get_string('pluginname', "block_exastats"));

echo $OUTPUT->header();

$students = block_exastats_get_course_students($courseid);
$firstStudent = true;
foreach ($students as $studentid) {
    if ($firstStudent) {
        $quizObj = quiz::create($quizid, $studentid);
        echo '<h2>'.$quizObj->get_quiz()->name.'</h2>';
        $firstStudent = false;
    }
    $studentStat = block_exastats_get_user_quizzes_short_results_with_categories($quizid, $studentid);
    echo block_exastats_view_fromstatdata_single_user($courseid, $studentStat, $studentid);
}

echo $OUTPUT->footer($course);


