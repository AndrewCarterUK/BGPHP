<?php

class GPS
{
    private $serialFile;

    public function __construct($serialFile)
    {
        $this->serialFile = $serialFile;
    }

    public function getFixData()
    {
        $parts = $this->getLineParts('GGA');

        list($hour, $minute, $second) = str_split((string) $parts[0], 2);

        return [
            'time' => $hour . ':' . $minute . ':' . $second,
            'latitude' => $this->coordinatesToDecimal($parts[1], $parts[2]),
            'longitude' => $this->coordinatesToDecimal($parts[3], $parts[4]),
            'quality' => $this->getQuality((int) $parts[5]),
            'satellites' => (int) $parts[6],
            'altitude' => (int) $parts[8],
        ];
    }

    private function coordinatesToDecimal($coordinate, $direction)
    {
        $breakPoint = strpos($coordinate, '.') - 2;

        $minutes = (float) substr($coordinate, $breakPoint);
        $degrees = (int) substr($coordinate, 0, $breakPoint);

        $decimal = $degrees + ($minutes / 60);

        return in_array($direction, ['S', 'W']) ? -$decimal : $decimal;
    }

    private function getQuality($code)
    {
        switch ($code) {
            case 0: return 'invalid';
            case 1: return 'gps-fix';
            case 2: return 'dgps-fix';
            case 3: return 'pps-fix';
            case 4: return 'real-time-kinematic';
            case 5: return 'float-rtk';
            case 6: return 'estimated';
            case 7: return 'manual-input';
            case 8: return 'simulation';
        }

        return 'unknown';
    }

    private function getLineParts($type)
    {
        $stream = fopen($this->serialFile, 'r');

        if (false === $stream) {
            throw new RuntimeException('Could not open file for reading: ' . $serialFile);
        }

        $prefix = '$GP' . $type;

        while (!feof($stream)) {
            $line = fgets($stream);

            if (strncmp($line, $prefix, strlen($prefix)) !== 0) {
                continue;
            }

            $parts = explode(',', $line);
            array_shift($parts);

            fclose($stream);

            return $parts;
        }

        fclose($stream);

        throw new RuntimeException('Could not find line type: ' . $type);
    }
}

