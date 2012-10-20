<?php

include ('MasterVolt.php');

$MasterVolt = new MasterVolt("/dev/ttyUSB0", /* debug = */ false );

$data = $MasterVolt->getCurrentStatus();

echo "getCurrentStatus() returned: ";
var_export($data);
echo "\n";

