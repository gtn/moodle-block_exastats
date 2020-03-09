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
set_time_limit(10800);

global $tableCities;
$tableCities = 'tx_eeducation_orte';
global $tableSchools;
$tableSchools = 'tx_eeducation_schulen';

global $bundeslands;
/*$bundeslands = [
	'Bgld' => 'Burgenland',
	'Kntn' => 'Kärnten',
	'NÖ' => 'Niederösterreich',
	'OÖ' => 'Oberösterreich',
	'Sbg' => 'Salzburg',
	'Stmk' => 'Steiermark',
	'Tirol' => 'Tirol',
	'Vlbg' => 'Vorarlberg',
	'Wien' => 'Wien',
];*/
$bundeslands = [
	1 => 'Burgenland',
	2 => 'Kärnten',
	3 => 'Niederösterreich',
	4 => 'Oberösterreich',
	5 => 'Salzburg',
	6 => 'Steiermark',
	7 => 'Tirol',
	8 => 'Vorarlberg',
	9 => 'Wien',
];

global $schultypen;
// 'TYPE' => indexes in 'Primär-Schulart' field
/*$schultypen = array(
	'AHS' => ['06', '23'],
	'Andere BMHS' => ['21'],
	'BAfEP' => ['--'],
	'BORG' => ['--'],
	'BS' =>['05', '15', '17'],
	'HAK' => ['08'],
	'HLT' => ['09' , '10'],
	'HTL' => ['07'],
	'HUM' => ['--'],
	'NMS/HS' => ['22'],
	'PTS' => ['04'],
	'Sonstige Schulen' => ['03', '11', '12', '13', '14', '18', '19'],
	'Volksschule' => ['01']
);*/
// schooltypes by new field
$schultypen = array(
	'AHS' => ['AHS'],
	'Andere BMHS' => ['LFS'],
	'BAfEP' => ['BAKIP'],
	'BORG' => ['AHS'],
	'BS' =>['BS'],
	'HAK' => ['HAK'],
	'HLT' => ['HUM'],
	'HTL' => ['HTL'],
	'HUM' => ['HUM', 'HLW'],
	'NMS/HS' => ['NMS', 'NMSpa', 'NMSsp'],
	'PTS' => ['PTS', 'PTSa'],
	'Sonstige Schulen' => ['Sonstige'],
	'Volksschule' => ['VS', 'VSsp', 'SS', 'SSa']
	// HLW,
);
// school types by coding numbers: xxxxxN - N schooltype
/*$schultypen = array(
	'1' => 'Volksschule (VS)',
	'2' => 'Hauptschule (NMS/HS)',
	'3' => 'Sonderschule (Sonstige Schule)',
	'4' => 'Polytechn. Schule (PTS)',
	'5' => 'Berufschule (BS)',
	'6' => 'Gymnasium (AHS, BORG)',
	'7' => 'HTL',
	'8' => 'HAK/HASCH (HAK)',
	'9' => 'HUM',
	'0' => 'BAfEP'
);*/

global $bezirkList;
$bezirkList = array(
	'4' => array(    // for Oberösterreich
		'01' => 'Linz-Stadt',
		'02' => 'Steyr-Stadt',
		'03' => 'Wels-Stadt',
		'04' => 'Braunau',
		'05' => 'Eferding',
		'06' => 'Freistadt',
		'07' => 'Gmunden',
		'08' => 'Grieskirchen',
		'09' => 'Kirchdorf',
		'10' => 'Linz-Land',
		'11' => 'Perg',
		'12' => 'Ried',
		'13' => 'Rohrbach',
		'14' => 'Schärding',
		'15' => 'Steyr-Land',
		'16' => 'Urfahr',
		'17' => 'Vöcklabruck',
		'18' => 'Wels-Land'
	)
);

// min count of users for showing report to director
global $minUsersForReport;
$minUsersForReport = 5;
//$minUsersForReport = 1;
// min count of Answered users for showing report to director
global $minAnsweredUsersForReport;
$minAnsweredUsersForReport = 3;
//$minAnsweredUsersForReport = 1;

require_once __DIR__."/../../config.php";
require_once __DIR__.'/lib/lib.php';
require_once __DIR__.'/lib/quiz_lib.php';

require_once __DIR__.'/../../mod/quiz/attemptlib.php';
require_once __DIR__.'/../../mod/quiz/accessmanager.php';
