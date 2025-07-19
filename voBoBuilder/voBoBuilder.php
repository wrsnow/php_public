<?php

$ROOT = __DIR__;

$sqlPath = $argv[1] ?? null;

if(!file_exists($sqlPath)) {
    echo "SQL file does not exist: {$sqlPath}" . PHP_EOL;
    exit(1);
}

passthru("php {$ROOT}/src/voBuilder.php --sql={$sqlPath} --snakecase=1"); // --snakecase for snake_case parameters in VO class
echo PHP_EOL;
passthru("php {$ROOT}/src/boBuilder.php --sql={$sqlPath}");
