<?php

require_once __DIR__ . '/vendor/autoload.php';

use PiPHP\GPIO\GPIO;
use PiPHP\GPIO\Pin\InputPinInterface;
use PiPHP\GPIO\Pin\PinInterface;

// Define the GPIO pin numbers
$pumpPinNumbers = [18, 17];
$flowPinNumbers = [2,  3];

// Define our pulse counter variables
$pulseCount = 0;
$pulseLimit = 100;

// This callback will keep the loop running until the limit
$pulseCallback = function ($pin, $value) use (&$pulseCount, &$pulseLimit) {
    return ++$pulseCount < $pulseLimit;
};

// Use PiPHP to create a GPIO object and event loop
$gpio = new GPIO();
$interruptWatcher = $gpio->createWatcher();

// Retrieve the pump pin objects
$pumpPins = [];

foreach ($pumpPinNumbers as $pinNumber) {
    $pumpPins[] = $gpio->getOutputPin($pinNumber);
}

// Retrieve the flow sensor pin objects
$flowPins = [];

foreach ($flowPinNumbers as $pinNumber) {
    $pin = $gpio->getInputPin($pinNumber);

    // We're interested in the voltage rising
    $pin->setEdge(InputPinInterface::EDGE_RISING);

    // Register the pulse counter callback
    $interruptWatcher->register($pin, $pulseCallback);

    $flowPins[] = $pin;
}

// A "pump" function
$pump = function($number, $counts, $timeout) use ($pumpPins, $interruptWatcher, &$pulseCount, &$pulseLimit) {
    // Reset the pulse counter
    $pulseLimit = $counts;
    $pulseCount = 0;

    // This is when we stop
    $end = microtime(true) + $timeout;

    // Turn the pump on
    $pumpPins[$number]->setValue(PinInterface::VALUE_HIGH);

    // While the timeout and pulse limits haven't occured
    while (
        $end > microtime(true) &&
        $interruptWatcher->watch(($end - microtime(true)) * 1000)
    );

    // Turn the pump off
    $pumpPins[$number]->setValue(PinInterface::VALUE_LOW);
};

// Make the drink

if ($argc < 2) {
    die("You must specify a pump (0 or 1)\n");
}

$pumpNumber = intval($argv[1]);

if ($pumpNumber !== 0 && $pumpNumber !== 1) {
    die("Pump must be 0 or 1\n");
}

echo "Starting pump...\n";

$pump($pumpNumber, 2000, 10);

echo "Done\n";
