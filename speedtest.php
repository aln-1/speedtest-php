<?php

require 'vendor/autoload.php';

use Aln\Speedtest\Cli;
use Aln\Speedtest\SpeedtestException;

try {
    new Cli();
} catch (SpeedtestException $e) {
    echo $e->getMessage() . "\n";
} catch (Exception $e) {
    throw $e;
}
