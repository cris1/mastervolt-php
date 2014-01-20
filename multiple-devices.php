<?php

include ('MasterVolt.php');

$MasterVolt = new MasterVolt("/dev/ttyUSB0", /* debug = */ true );

echo "*************************************\n";

$MasterVolt->discoverMulti();