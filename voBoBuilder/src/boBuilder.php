<?php

$ROOT = dirname(__DIR__, 1);

require $ROOT . '/config/SqlTypeMapper.php';
require $ROOT . '/classes/BO.php';

$outputPath = './classes/bo/';

$regexCreateTable = '/CREATE TABLE `(.*?)`/i';
$regexPropertyLine = '/(\s{2}`\w.*?`.*)/';
$regexCapture = '/`(.*?)` (.*?)[\s|\W]/';

$sqlFilePath = null;

if ($argc > 1) {
  $argsParsed = [];
  $args = array_filter($argv, function ($arg) {
    if (strpos($arg, '--') === 0)  return $arg;
  });

  foreach ($args as $arg) {
    list($argName, $argVal) = explode('=', $arg);
    $argsParsed[preg_replace('/[^a-z]/i', '', $argName)] = $argVal;
  }

  if ($argsParsed['sql']) {
    $sqlFilePath = $argsParsed['sql'];
  }

  if ($argsParsed['className']) {
    $className = $argsParsed['className'];
  }

  if ($argsParsed['tableName']) {
    $tableName = $argsParsed['tableName'];
  }
}

if (!$sqlFilePath || !file_exists($sqlFilePath)) {
  throw new Error('SQL file path is not provided or does not exist.');
}

$ddl = file_get_contents($sqlFilePath);

if (preg_match($regexCreateTable, $ddl, $createTableMatch)) {
  $className = implode('', array_map(function ($namePart) {
    return ucfirst($namePart);
  }, explode('_', $createTableMatch[1])));;
  $tableName = $createTableMatch[1];
}

if (!$tableName || !$className || !$ddl) {
  throw new Error('`className` or `tableName` missing | filename: ' . $sqlFilePath);
}

$fullText = (new BO($className, $tableName))->getFullText();

//
$parameters = [];
$parametersText = '';

preg_match_all($regexPropertyLine, $ddl, $matches);

$matches = array_map(function ($e) {
  return trim($e);
}, $matches[0]);

foreach ($matches as $idx => $current) {
  preg_match($regexCapture, $current, $match);

  $key = $match[1];
  $type = preg_replace('/[^a-z]/i', '', $match[2]);
  $typeFromTypeMap = $typeMap[strtolower($type)];
  $pdoParam = $pdoParamEnum[strtolower($typeFromTypeMap)];

  if (!$pdoParam) {
    var_dump([
      $current,
      $match,
      $key,
    ]);
    throw new Error('$pdoParam invalid, key: ' . $key);
  }

  $skipParam = $idx === 0 ? ", 'skipParam' => true" : '';

  $nullable = 'true';

  if (strpos($current, 'NOT NULL')) {
    $nullable = 'false';
  }

  $parameters[] = "'$key' => ['nullable' => $nullable, 'type' => $pdoParam $skipParam]";
}

$parametersText = "
private static \$parameters = [\n" .
  implode(",\n", $parameters)
  . "\n];";

$fullText = str_replace('{{parameters}}', $parametersText, $fullText);

if (!is_dir($outputPath)) {
  mkdir($outputPath, 0777, true);
}

file_put_contents($outputPath . $className . "BO.php", $fullText);
echo "OUTPUT: " . $outputPath . $className . "BO.php";
