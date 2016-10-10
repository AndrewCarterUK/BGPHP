<?php

echo <<<EOT
\033[34m
__________.____________                __                 __
\______   \__\______   \_______  _____/  |_  ____   _____/  |_
 |     ___/  ||     ___/\_  __ \/  _ \   __\/ __ \_/ ___\   __\
 |    |   |  ||    |     |  | \(  <_> )  | \  ___/\  \___|  |
 |____|   |__||____|     |__|   \____/|__|  \___  >\___  >__|
                                                \/     \/
v1.1 - (c) Andrew Carter - TOP SECRETZ


EOT;

// Obtain GPS object bound to read NMEA data from /dev/ttyUSB0

require_once __DIR__ . '/GPS.php';

$gps = new GPS('/dev/ttyUSB0');

// Define a home range of allowed latitude and longitude

$boundary = [
	'latitude'  => [51.387794, 51.387851],
	'longitude' => [-0.847726, -0.847589],
];

while (1) {
	$data = $gps->getFixData();

	if ($data['quality'] === 'invalid') {
		// Invalid fix, GPS still searching
		continue;
	}

	$inRange = function ($value, array $range) { return ($value >= $range[0] && $value <= $range[1]); };

	if (
		$inRange($data['latitude'],  $boundary['latitude']) &&
		$inRange($data['longitude'], $boundary['longitude'])
	) {
		// GPS is within home range
		continue;
	}

	// GPS outside of home range, exit loop
	echo 'GPS data has exceeded allowed limitations:' . PHP_EOL . PHP_EOL;
	break;
}

// Sound the alarm!

require_once 'Mailer.php';

$mailer = new Mailer('imap.gmail.com', 'andrew@gmail.com', 'I<3HannahMontana');

$message = <<<EOT
Dear The Feds,

This automated message is to notify you that a 35 USD micro-computer which was recently stolen
from me has been switched on at this location: {$data['latitude']}, {$data['longitude']}.

Please could you send a SWAT team around to recover my property at the earliest opportunity.

Kthnxbai
EOT;

$mailer->send('reports@fbi.gov', $message);
