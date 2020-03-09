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

$string['pluginname'] = 'Exastats block';
$string['exastats'] = 'Exastats';
$string['exastats:addinstance'] = 'Add a new Exastats block';
$string['exastats:myaddinstance'] = 'Add a new Exastats block to the My Moodle page';
$string['exastats:use'] = 'Use Exastats block';
$string['blocktitle'] = 'Block title';
$string['defaulttitle'] = 'Exastats';
$string['view_type'] = 'Type of view in block';
$string['type_byrole'] = 'Auto by user\'s role';
$string['type_student'] = 'Stats for Student';
$string['type_teacher'] = 'Stats for Teacher';
$string['type_director'] = 'Stats for Director';
$string['link_student'] = 'Stats for Student';
$string['link_teacher'] = 'Stats for Teacher';
$string['link_director'] = 'Stats for Director';
$string['invalidinstance'] = 'That\'s an invalid instance';
$string['page_stats_student_title'] = 'Student\'s statistic';
$string['page_stats_teacher_title'] = 'Teacher\'s statistic';
$string['page_stats_director_title'] = 'Director\'s statistic';
$string['page_stats_group_title'] = 'Group\'s statistic';
$string['my_stats'] = 'Auswertung';
$string['group_stats'] = 'Gruppen-Statistik';
$string['director_stats'] = 'Gruppen-Statistik';
$string['region_stats'] = 'Aggregierte Statistiken';
$string['more'] = 'weitere Informationen';
/* result question table */
$string['result_questiontable_question'] = 'Frage';
$string['result_questiontable_myresponse'] = 'Antwort';
$string['result_questiontable_correctresponse'] = 'Correct response';
$string['result_questiontable_status'] = 'Ergebnis';
$string['result_questiontable_questiongrade'] = 'Grade';
$string['result_questiontable_totalgrade'] = 'Testergebnis';
$string['result_questionnaire_notfinished'] = 'Questionnaire is not finished yet';
$string['result_quiz_notfinished'] = 'Quiz is not finished yet';
/* result for director */
$string['result_count_users'] = 'Anzahl der ausgegebenen Tans:';
$string['result_total'] = 'Total';
$string['category'] = 'Category';
$string['rank'] = 'Rank';
$string['self-assessment'] = 'Selbsteinschätzung';
$string['self-assessment-short'] = 'S';
$string['knowledge'] = 'Wissen';
$string['knowledge-short'] = 'W';

$string['access_to_statistics'] = 'Zugriff auf die Statistik';
$string['count_answered_users'] = 'Anzahl der Antworten';
$string['region_statistic_link'] = 'Aggregierte Statistiken';
$string['page_stats_region_title'] = 'Aggregierte Statistiken';
$string['select_region'] = 'Bundesland auswählen';
$string['select_bezirk'] = 'Bezirk auswählen';
$string['select_schooltype'] = 'Schultypen auswählen';
$string['select_gender'] = 'Geschlecht auswählen';
$string['input_schoolnumber'] = 'Schulkennzahl';
$string['input_years_on_job'] = 'Dienstjahre';
$string['years_on_job_help'] = 'es können auch Vergleichszeichen zur Filterung wie ">", "<" und "=" verwendet werden';
$string['gradedright'] = 'Richtig';
$string['gradedpartial'] = 'Teilweise richtig';
$string['gradedwrong'] = 'Falsch';
$string['button_show'] = 'Anzeigen';
$string['button_downloadCsv'] = 'Download CSV';
$string['button_notAnsweredSchools'] = 'Fehlende Schulen';
$string['button_clearFilter'] = 'Filter entfernen';

// error messages
$string['noAccess'] = 'Sie haben keine Berechtigung diese Seite anzuzeigen.';
$string['noForeignTables'] = 'Der Block ist nicht korrekt installiert. Notwendige Tabellen fehlen.<br /> Bitte wenden sie sich an den Administrator.';
$string['hiddenByAnonymity'] = 'Bei der Anzahl von Teilnehmer/innen steht leider kein Schul-Ergebnis zur Verfügung.';
$string['hiddenQuestionnairByAnonymity'] = 'Selbsteinschätzung: Bei der Anzahl von Teilnehmer/innen steht leider kein Schul-Ergebnis zur Verfügung';
$string['hiddenQuizByAnonymity'] = 'Wissen: Bei der Anzahl von Teilnehmer/innen steht leider kein Schul-Ergebnis zur Verfügung';

$string['noRecords_schools'] = 'Keine fehlenden Schulen in dieser Kategorie gefunden';

// scheduler task
$string['tempusercleanuptask'] = 'Clean temporary users';

// group filter
$string['fillSchoolnumberAndGroup'] = 'Bitte die Schulkennzahl und Passphrase eingeben';
$string['form_schoolnumber_label'] = 'Schulkennzahl';
$string['form_group_label'] = 'Gruppe/Passphrase';

$string['no_any_quizes_in_course'] = 'No any quizes in this course!';
$string['no_quizes'] = 'No quizes to view';
$string['no_answered'] = 'No answered yet';
$string['students_count'] = '{$a} students';
$string['detail_quiz_statistic_link'] = 'Detail statistic';
$string['page_detail_quiz_title'] = 'Detail statistic';
$string['configure_block'] = 'Please select a quiz in block settings';
$string['only_for_admins'] = 'Only for admins';
$string['config_quiz_moodle'] = 'Select a quiz to show';
