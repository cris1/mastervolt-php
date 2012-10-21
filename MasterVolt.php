<?php

class MasterVolt
{
	const XS3200 = 0xDC;
	const XS2000 = 0xDB;

	private $types = array(
		self::XS3200 => 'XS3200',
		self::XS2000 => 'XS2000',
	);

	private $serial;
	private $identifier = 0;

	private $debug = false;
	private $readTimeout = 3;
	private $writeTimeout = 1;
	private $commandRetries = 5;

	public function __construct($device, $debug = false)
	{
		$this->debug = $debug;

		exec('stty --version', $output, $return_var);

		if ($return_var != 0) {
			echo "Cannot call stty binary\n";
			exit;
		}

		`stty -F $device 9600 -parenb cs8 -cstopb clocal -crtscts -ixon -ixoff`;

		if (!$this->serial = fopen($device, 'r+b')) {
			echo "Could not open serial port\n";
			exit;
		}
		stream_set_blocking($this->serial, 0);

		$type = $this->discover();

		if (!$type) {
			if (!$this->debug) {
				echo "Unknown device or no device at all\nRun debug mode for more information\n";
			}
			exit;
		}

		if ($this->debug) {
			echo "MasterVolt Device " . $this->types[$type] . " found\n";
		}
	}

	public function setReadTimeout($timeout = 3)
	{
		$this->readTimeout = $timeout;
	}

	public function setWriteTimeout($timeout = 1)
	{
		$this->writeTimeout = $timeout;
	}

	public function setCommandRetries($retries = 5)
	{
		$this->commandRetries = $retries;
	}

	private function calcChecksum($data)
	{
		$i = 0;
		for ($c = 0; $c < strlen($data); $c++ ) {
			$i += ord($data[$c]);
			if ($i > 255) {
				$i -= 256;
			}
		}

		return $i;
	}

	private function writeCommand($command)
	{
		$data = pack('nn', $this->identifier, 0xFFFF);

		$command = func_get_args();
		array_unshift($command, 'C*');
		$data .= call_user_func_array('pack', $command);

		$data .= chr($this->calcChecksum($data));

		// Flush data from read buffer
		$flush = fread($this->serial, 1024);
		if ($this->debug && $flush) {
			echo "< " . bin2hex($flush) . "\n";
		}

		if ($this->debug) {
			echo "> " . bin2hex($data) . "\n";
		}

		$time = microtime(true);
		while ($data) {
			$bytesWritten = fwrite($this->serial, $data);
			$data = substr($data, $bytesWritten);
			if (microtime(true) > $time + $this->writeTimeout) {
				if ($this->debug) {
					echo "Timeout on sending data\n";
				}
				return false;
			}
		}

		return true;
	}

	private function readData($retlen)
	{
		$retlen += 5; // addresses + checksum

		$read = '';
		$time = microtime(true);
		while (true) {
			// intentionally read all data that is available in the buffer,
			// even more than retlen suggests we should read.
			$read .= fread($this->serial, 1024);

			if (strlen($read) >= $retlen) {
				break;
			}

			usleep(100);

			if (microtime(true) > $time + $this->readTimeout) {
				if ($this->debug) {
					echo "Timeout on receiving data\n";
				}
				return false;
			}

		}

		$read = substr($read, 0, $retlen);

		if ($this->debug) {
			echo "< " . bin2hex($read) . "\n";
		}

		$ids = unpack('nto/nfrom', substr($read, 0, 4));
		if (!$this->identifier) {
			$this->identifier = $ids['from'] + 256;
		}

		$checksum = unpack('C', substr($read, -1));

		if ($this->calcChecksum(substr($read, 0, -1)) != $checksum[1]) {
			if ($this->debug) {
				echo "Checksum error on received data\n";
			}
			return false;   // checksum error
		}

		$read = array_values(unpack('C*', substr($read, 4, -1)));

		return $read;
	}

	private function doCommand($command, $retlen)
	{
		$tries = 0;
		while (true) {
			call_user_func_array(array($this, 'writeCommand'), $command);

			if ($read = $this->readData($retlen)) {

				if ($read[0] == $command[0]) {
					return $read;
				}

				if ($this->debug) {
					echo "Invalid response, expected " . dechex($command[0]) . " in first byte\n";
				}
			}

			$tries++;
			if ($tries > $this->commandRetries) {
				return;
			}
			if ($this->debug) {
				echo "Retrying\n";
			}
		}

	}

	private function discover()
	{
		if (!$read = $this->doCommand(array(0xC1, 0x00, 0x00, 0x00), 4)) {
			return false;
		}

		if (isset($this->types[$read[1]])) {
			return $read[1];
		}

		if ($this->debug) {
			echo "Unknown device type " . dechex($read[1]) . "\n";
		}

		return false;
	}

	public function getDeviceInfo()
	{
		if (!$read = $this->doCommand(array(0xB4, 0x00, 0x00, 0x00), 26)) {
			return false;
		}

		return array(
			'type' => dechex($read[1]),
			'typeName' => $this->types[$read[1]],
			'serial' => dechex($read[2]) . dechex($read[3]) . dechex($read[4]) . dechex($read[5]) . dechex($read[6]) . dechex($read[7]) . dechex($read[8]),
			'firmware1' => dechex($read[9]) . dechex($read[10]) . dechex($read[11]) . dechex($read[12]) . dechex($read[13]) . dechex($read[14]),
			'firmware2' => dechex($read[15]) . dechex($read[16]) . dechex($read[17]) . dechex($read[18]) . dechex($read[19]) . dechex($read[20]),
			'firmware3' => dechex($read[21]) . dechex($read[22]) . dechex($read[23]) . dechex($read[24]) . dechex($read[25]),
		);
	}

	public function getDayTotal($day)
	{
		if (!$read = $this->doCommand(array(0x9A, $day, 0x00, 0x00), 4)) {
			return false;
		}

		return array(
			'minutes' => $read[1]*5,
			'kWh' => ($read[3]*256 + $read[2]) / 100,
		);
	}

	public function getCurrentStatus()
	{
		if (!$read = $this->doCommand(array(0xB6, 0x00, 0x00, 0x00), 26)) {
			return false;
		}

		return array(
			'solarV'	=> $read[4] + $read[5]*256,
			'solarA'	=> ($read[6] + $read[7]*256)/100,
			'solarW'	=> ($read[4] + $read[5]*256) * ($read[6] + $read[7]*256) / 100,
			'netHz'		=> ($read[8] + $read[9]*256)/100,
			'netVac'	=> $read[10] + $read[11]*256,
			'netA'		=> ($read[12] + $read[13]*256)/100,
			'netW'		=> $read[14] + $read[15]*256,
			'kWh'		=> ($read[16] + $read[17]*256)/100,
			'C'			=> $read[19],
			'h'			=> ($read[20] + $read[21]*256 + $read[22]*65536 + $read[23]*16777216 + $read[24]*4294967296 + $read[25]*4294967296*256)/60,
		);

	}
}
