<?php

$ROOT = dirname(__DIR__, 1);

require $ROOT . '/vendor/autoload.php';
require $ROOT . '/config/SqlTypeMapper.php';

use PhpMyAdmin\SqlParser\Parser;

$outputPath = './classes/vo/';

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

$parser = new Parser($ddl);

$tableName = $parser->statements[0]->name->table;

$className = implode('', array_map(function ($namePart) {
  return ucfirst($namePart);
}, explode('_', $tableName)));

$columns = [];

$selectStatement = $parser->statements[0];

foreach ($selectStatement->fields as $column) {
  if ($column->name === null) continue;

  $comment = '';

  if ($column->options->options[5]['name'] === 'COMMENT') {
    $comment = $column->options->options[5]['value'];
  }

  $commentExploded = [];

  if ($comment) {
    $commentExploded = array_map(function ($i) {
      return str_replace(['\n', '\r'], '', $i);
    }, explode(',', $comment) ?? []);
  }


  $columns[] = [
    'name' => $column->name,
    'type' => $column->type->name,
    'commentExploded' => $commentExploded
  ];
}

if (!$tableName || !$className || !$ddl) {
  throw new Error('`className` or `tableName` missing');
}

$fullText = "<?php

class " . $className . "VO implements JsonSerializable
{
    {{classKeys}}

    {{classConstants}}

    public function __construct(array \$data = [])
    {
        \$classProperties = \$this->getProperties();

        foreach (\$classProperties as \$property) {
            if (array_key_exists(\$property, \$data)) {
                \$value = \$data[\$property];

                if (!\$value && \$value !== 0 && \$value !== '0') {
                    \$value = null;
                } elseif (is_string(\$value)) {
                    \$value = trim(\$value);
                }

                \$this->\$property = \$value;
            }
        }
    }

    public function jsonSerialize() :array
    {
        return get_object_vars(\$this);
    }

    public function getProperties(): array
    {
        return array_keys(get_object_vars(\$this));
    }

    public function __clone()
    {
        foreach (get_object_vars(\$this) as \$key => \$value) {
            if (is_object(\$value)) {
                \$this->\$key = clone \$value;
            }
        }
    }

    {{classGettersAndSetters}}

}
";

$classKeys = '';
$classConstants = '';
$classConstructContent = '';
$classGettersAndSetters = '';

foreach ($columns as $column) {
  $propertyType = $typeMap[strtolower($column['type'])];

  if (!$propertyType) {
    echo '!$propertyType: ' . $column['name'] . PHP_EOL;
    continue;
  };

  $i = 0;
  $propertyName = implode('', array_map(function ($v) use (&$i) {
    if ($i === 0) {
      $i += 1;
      return $v;
    }
    $i += 1;
    return ucfirst($v);
  }, explode('_', $column['name'])));

  $upperFirstLetter = ucfirst($propertyName);

  if ($argsParsed['snakecase']) {
    $propertyName = $column['name'];
  };

  $classKeys .= "\nprivate \$$propertyName;";

  $constEnum = '';

  // if ($column['commentExploded']) {
  //   $constEnum = "const " . strtoupper($column['name']) . " = [ \n";
  //   $arr = [];

  //   foreach ($column['commentExploded'] as $key => $value) {
  //     list($commentVal, $commentLabel) = explode('=', $value);

  //     $classConstants .= 'const ' . strtoupper($column['name']) . '_' . trim(strtoupper($commentLabel)) . ' = ' . $commentVal . ";\n";

  //     $constEnum .= 'self::' . strtoupper($column['name']) . '_' . trim(strtoupper($commentLabel)) . ' => ' . '\'' . trim(ucfirst($commentLabel)) . '\'' . ",\n";
  //   }

  //   $constEnum .= "];";
  // }

  $classConstants .= $constEnum;

  $classGettersAndSetters .= "
    public function get$upperFirstLetter() :?$propertyType{
        return \$this->$propertyName;
    }

    public function set$upperFirstLetter(?$propertyType \$value) :self {
        \$this->$propertyName = \$value;
        return \$this;
    }
    ";
}

$fullText = preg_replace('/{{classKeys}}/', $classKeys, $fullText);
$fullText = preg_replace('/{{classConstants}}/', $classConstants, $fullText);
$fullText = preg_replace('/{{classConstructContent}}/', $classConstructContent, $fullText);
$fullText = preg_replace('/{{classGettersAndSetters}}/', $classGettersAndSetters, $fullText);

if (!is_dir($outputPath)) {
  mkdir($outputPath, 0755, true);
}

file_put_contents($outputPath . $className . "VO.php", $fullText);

echo "OUTPUT: " . $outputPath . $className . "VO.php";
