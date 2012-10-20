<?php

include ('MasterVolt.php');

$MasterVolt = new MasterVolt("/dev/ttyUSB0", /* debug = */ false );

$data = $MasterVolt->getDeviceInfo();

echo "getDeviceInfo() returned: ";
var_export($data);
echo "\n";

