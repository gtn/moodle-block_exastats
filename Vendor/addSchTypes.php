<?php

include 'config.php';
$csvFile = 'Schulen_OOE_17_18_Liste.csv';

$row = 0;
if (($handle = fopen($csvFile, "r")) !== FALSE) {
	while (($data = fgetcsv($handle, null, ";")) !== FALSE) {
		$row++;
		if ($row == 1)
			continue;
		//switch ($data[2]) {
		//	case 'VSsp':
		//		break;
		//}
		//echo $data[6].': '.$data[2].'<br>';
		$sql = 'UPDATE tx_eeducation_schulen SET schultyp = \''.$data[2].'\' WHERE schulkennzahl = '.intval($data[6]);
		echo $sql.'<br>';
		$mysqli->query($sql);
	}
	fclose($handle);
}

