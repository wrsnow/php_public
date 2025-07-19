<?php

$typeMap = [
  'bigint'      => 'int',
  'binary'      => 'string',
  'bit'         => 'bool',
  'blob'        => 'string',
  'bool'        => 'bool',
  'boolean'     => 'bool',
  'char'        => 'string',
  'date'        => 'string',
  'datetime'    => 'string',
  'decimal'     => 'float',
  'double'      => 'float',
  'enum'        => 'string',
  'float'       => 'float',
  'int'         => 'int',
  'json'        => 'string',
  'longblob'    => 'string',
  'longtext'    => 'string',
  'mediumblob'  => 'string',
  'mediumint'   => 'int',
  'mediumtext'  => 'string',
  'real'        => 'float',
  'set'         => 'string',
  'smallint'    => 'int',
  'text'        => 'string',
  'time'        => 'string',
  'timestamp'   => 'string',
  'tinyblob'    => 'string',
  'tinyint'     => 'int',
  'tinytext'    => 'string',
  'varchar'     => 'string',
  'year'        => 'int',
];

$pdoParamEnum = [
  'bool'   => 'PDO::PARAM_BOOL',
  'float'  => 'PDO::PARAM_STR',
  'int'    => 'PDO::PARAM_INT',
  'json'   => 'PDO::PARAM_LOB',
  'null'   => 'PDO::PARAM_NULL',
  'string' => 'PDO::PARAM_STR',
  'text'   => 'PDO::PARAM_LOB',
];

$defaultValuesToIgnore = [
  null,
  'NULL',
  'CURRENT_TIMESTAMP',
];
