<?php

// load configuration from Moodle, but without required/included parts
$mConfig =  __DIR__."/../../../config.php";
$mConfigContent = file($mConfig);
// add exastats config
$exastatsConfig = __DIR__."/../inc.php";
$mConfigContent = array_merge($mConfigContent, file($exastatsConfig));
// add exastats config
$lang = 'de';
$exastatsLang = __DIR__."/../lang/".$lang."/block_exastats.php";
$mConfigContent = array_merge($mConfigContent, file($exastatsLang));
// delete all require/include
foreach ($mConfigContent as $key => $line) {
	if (strpos($line, 'require') !== false
		|| strpos($line, 'include') !== false
		|| strpos($line, '<?php') !== false
		)
		unset($mConfigContent[$key]);
}
$mConfigContent = implode("\r\n", $mConfigContent);
eval($mConfigContent);
global $langString;
$langString = $string;

global $mysqli;
$mysqli = new mysqli($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname, ($CFG->dboptions['dbport'] ? intval($CFG->dboptions['dbport']) : null), $CFG->dboptions['dbsocket'])
		or die (' NO CONNECTING TO DB!!!! ');

//$mysqli = null;
//$mysqli = new mysqli('localhost', 'root', 'root', 'tempMoodle32')
//	or die ('!!!! NO LOCAL DB !!!!!');

$mysqli->set_charset('utf8');

$token = 'fj30rjfg03jgpdelgkgnt7ghwepo476306jgopdhg764594jg0rfjh';

require_once __DIR__.'/func.php';

error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set('memory_limit','1024M');
ini_set('max_execution_time', 7200);
set_time_limit(7200);

