<?php

include ('MasterVolt.php');

$MasterVolt = new MasterVolt("/dev/ttyUSB0", /* debug = */ false );

echo "Calling getDayTotal(0 .. 29) \n";
$output = '';
for ($c = 0; $c <= 29; $c++) {
	$e = 0;
	do {
		$data = $MasterVolt->getDayTotal($c);
		sleep(1);
		$e++;
		if ($e > 5) {
			break;
		}
	} while ($data == false);
	echo $c . ' ' . $data['minutes'] . ' ' . $data['kWh'] . "\n";
}

