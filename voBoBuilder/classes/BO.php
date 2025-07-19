<?php

class BO
{
	public string $className;
	public string $tableName;

	public function __construct(string $className, string $tableName)
	{
		$this->className = $className;
		$this->tableName = $tableName;
	}

	public function getValidateMethod()
	{
		return '
			public function validate(' . $this->className . 'VO $vo): void
			{
				$parameters = $this->getParameters();

				foreach ($parameters as $parameter => $config) {
					$parameterPascalCase = array_reduce(explode("_", $parameter), fn($carry, $item) => $carry . ucfirst($item));

					if (!$config["nullable"] && $vo->{"get" . ucfirst($parameterPascalCase)}() === null) {
						throw new RuntimeException("O parâmetro `{$parameter}` não pode ser nulo");
					}
				}
			}
		';
	}

	public function getInsertMethods()
	{
		return 'public function insert(' . $this->className . 'VO $vo): int
		{
			$this->validate($vo);
			$columns = [
				1 => [],
				2 => []
			];

			$parameters = $this->getParameters();

			foreach ($parameters as $parameter => $config) {
				$columns[1][] = "`{$parameter}`";
				$columns[2][] = ":{$parameter}";
			}

			$stmt = $this->pdo->prepare(
				"INSERT INTO {$this->getTableName()} (" . implode(\', \', $columns[1]) . ")
							VALUES (" . implode(\', \', $columns[2]) . ")"
			);

			foreach ($parameters as $parameter => $config) {

				$parameterPascalCase = array_reduce(explode(\'_\', $parameter), function ($carry, $item) {
					return $carry . ucfirst($item);
				});

				if ($config[\'nullable\']) {
					$stmt->bindValue(":{$parameter}", null, PDO::PARAM_NULL);
				}

				if (null !== $vo->{"get" . ucfirst($parameterPascalCase)}()) {
					$stmt->bindValue(":{$parameter}", $vo->{"get" . ucfirst($parameterPascalCase)}(), $config[\'type\']);
				}
			}

			$stmt->execute();

			return $this->pdo->lastInsertId();
		}

		public function insertOnlyNecessaryFields(array $array): int
		{
			$colunas = [
				1 => [],
				2 => []
			];

			foreach ($array as $parameter => $value) {
				$colunas[1][] = "`{$parameter}`";
				$colunas[2][] = ":{$parameter}";
			}

			$query = "INSERT INTO {$this->getTableName()} (" . implode(\', \', $colunas[1]) . ") VALUES (" . implode(\', \', $colunas[2]) . ")";
			$stmt = $this->pdo->prepare($query);

			foreach ($array as $parametro => $valor) {
				$stmt->bindValue(":{$parametro}", $valor);
			}

			$stmt->execute();

			return $this->pdo->lastInsertId();
		}
		';
	}

	public function getUpdateMethods()
	{
		return '
				public function update(' . $this->className . 'VO $vo)
			{
				$this->validate($vo);
				$columns = [];

				$parameters = $this->getParameters();

				foreach ($parameters as $parameter => $config) {
					$columns[] = "`{$parameter}` = :{$parameter}";
				}

				$stmt = $this->pdo->prepare(
					"UPDATE {$this->getTableName()} SET " . implode(\', \', $columns) . "
								WHERE id = :id"
				);

				$stmt->bindValue(\':id\', $vo->getId(), PDO::PARAM_INT);

				foreach ($parameters as $parameter => $config) {

					$parameterPascalCase = array_reduce(explode(\'_\', $parameter), function ($carry, $item) {
						return $carry . ucfirst($item);
					});

					if ($config[\'nullable\']) {
						$stmt->bindValue(":{$parameter}", null, PDO::PARAM_NULL);
					}

					if (null !== $vo->{"get" . ucfirst($parameterPascalCase)}()) {
						$stmt->bindValue(":{$parameter}", $vo->{"get" . ucfirst($parameterPascalCase)}(), $config[\'type\']);
					}
				}

				return $stmt->execute();
			}

				/**
			 * Atualiza apenas os campos necessários, utilizando [key => value]
			 *
			 * @param array $array
			 * @return bool
			 */
			public function updateOnlyNecessaryFields(array $array): bool
			{
				$pdo = $this->pdo;
				$colunas = [];
				$configuracoes = [];
				$parametros = [];

				if (!$array[\'id\']) {
					throw new \Exception(\'Nenhum id foi informado\');
				};

				$itemId = $array[\'id\'];

				if (!$itemId) return false;

				foreach ($array as $key => $value) {

					if ($key === \'id\') continue;

					$configuracao = $this->getParameters()[$key];

					if (!$configuracao) continue;

					$colunas[] = "`{$key}` = :{$key}";
					$configuracoes[$key] = $configuracao;
					$parametros[] = $key;
				}

				if (empty($colunas)) {
					throw new \Exception(\'Nenhum campo foi informado\');
				};

				$stmt = $pdo->prepare("UPDATE " . $this->getTableName() . " SET " . implode(\', \', $colunas) . " WHERE id = :id");
				$stmt->bindValue(\':id\', $itemId, PDO::PARAM_INT);

				foreach ($parametros as $parametro) {

					if ($configuracao[\'nullable\']) {
						$stmt->bindValue(":{$parametro}", null, PDO::PARAM_NULL);
					}

					if (null !== $array[$parametro]) {
						$stmt->bindValue(":{$parametro}", $array[$parametro], $configuracoes[$parametro][\'type\']);
					}
				}

				return $stmt->execute();
			}
		';
	}

	public function getDeleteMethod()
	{
		return '
			/**
			 * Deleta um item por ID, realiza um SOFT-DELETE
			 *
			 * @param int $id Identificador
			 * @param int $usuarioId Identificador do usuario
			 *
			 * @return bool Retorna true se o item foi deletado, false caso contrario
			 */
			public function delete(int $id, int $usuarioId)
			{

				$item = $this->getOneById($id);

				if (!$item) {
					throw new \RuntimeException(\'Item não encontrado\');
				}

				$item->setDeletadoEm(Utils::getTimestamp());
				$item->setDeletadoPor($usuarioId);

				return $this->update($item);
			}

		';
	}

	public function getGetOneById()
	{
		return '
				public function getOneById(int $id, array $options = [])
			{
				$columnsToFetch = [
					"*"
				];

				if (isset($options[\'columns\']) && is_array($options[\'columns\'])) {
					$columnsToFetch = $options[\'columns\'];
				}

				$columnsToFetch = implode(\', \', $columnsToFetch);

				$query = "SELECT {$columnsToFetch} FROM {$this->getTableName()} WHERE `id` = :id";
				$stmt = $this->pdo->prepare($query);
				$stmt->bindValue(\':id\', $id, PDO::PARAM_INT);
				$stmt->execute();
				$result = $stmt->fetchObject(' . $this->className . 'VO::class);

				if (!$result) return null;

				/** @var ' . $this->className . 'VO $result */

				return $result;
			}

		';
	}
	public function getGetAllMethod()
	{
		return '
			public function getAll()
			{
				$query = "SELECT * FROM {$this->getTableName()}";
				$stmt = $this->pdo->prepare($query);
				$stmt->execute();
				$results = $stmt->fetchAll(PDO::FETCH_CLASS,' . $this->className . 'VO::class' . ');

				if (!$results) return [];

				/** @var ' . $this->className . 'VO[] $results */

				return $results;
			}
		';
	}

	public function getGetAllPaginatedMethod()
	{
		return '
			public function getAllPaginated(
				int $page = 1,
				int $limit = 10
			) {

				$execParams = [
					\':limit\' => [
						\'value\' => $limit,
						\'type\' => PDO::PARAM_INT
					],
					\':offset\' => [
						\'value\' => ($page - 1) * $limit,
						\'type\' => PDO::PARAM_INT
					]
				];
				$andExtraQueries = [];
				$andExtraQueries = implode(\' \', $andExtraQueries);

				$query = "
				SELECT * FROM {$this->getTableName()}
				WHERE 1 = 1
				{$andExtraQueries}
				LIMIT :limit
				OFFSET :offset
				";

				$stmt = $this->pdo->prepare($query);

				foreach ($execParams as $key => $value) {
					$stmt->bindValue($key, $value[\'value\'], $value[\'type\']);
				}

				$stmt->execute();

				$results = $stmt->fetchAll(PDO::FETCH_CLASS, ' . $this->className . 'VO::class' . ');

				// GET COUNT AND PAGES

				$query = "
				SELECT COUNT(*) as total FROM {$this->getTableName()}
				WHERE 1 = 1
				{$andExtraQueries}
				";

				$stmt = $this->pdo->prepare($query);

				unset($execParams[\':limit\'], $execParams[\':offset\']);

				foreach ($execParams as $key => $value) {
					$stmt->bindValue($key, $value[\'value\'], $value[\'type\']);
				}

				$stmt->execute();

				$count = $stmt->fetch(PDO::FETCH_ASSOC)[\'total\'];

				// return [
				// 	\'results\' => $results,
				// 	\'count\' => $count,
				// 	\'pages\' => ceil($count / $limit),
				// 	\'hasNext\' => $page < ceil($count / $limit)
				// ];

				// return new Paginator($results, ceil($count / $limit), $page);
			}
		';
	}

	public function getFullText()
	{
		return "<?php

  require_once __DIR__ . '/../vo/" . ucfirst($this->className) . "VO.php';
  require_once __DIR__ . '/../PdoBuilder.php';

  class {$this->className}BO
  {
    private static \$pdoStatic;\n
    private \$pdo;\n
    private static \$tableName = '{$this->tableName}';\n
    {{parameters}}\n
    public function __construct(?PDO \$pdo = null)
    {
      if (\$pdo) {
        \$this->pdo = \$pdo;
      } else {
        if (!self::\$pdoStatic) {
          self::\$pdoStatic = new PdoBuilder();
        }
        \$this->pdo = self::\$pdoStatic;
      }
    }
    public static function getTableName()
    {
      return self::\$tableName;
    }\n

    /**
   * Retorna os parâmetros do banco de dados
   *
   * @param bool \$includeSkippable Se deve incluir os parâmetros que devem ser ignorados
   *
   * @return array Retorna os parâmetros do banco de dados
   */
  public static function getParameters(bool \$includeSkippable = false)
  {

    if (\$includeSkippable) {
      return self::\$parameters;
    }

    return array_filter(self::\$parameters, function (\$p) {
      return !\$p['skipParam'];
    });
  }

    {$this->getValidateMethod()}

    {$this->getInsertMethods()}

		{$this->getUpdateMethods()}

		{$this->getDeleteMethod()}

		{$this->getGetOneById()}

		{$this->getGetAllMethod()}

		{$this->getGetAllPaginatedMethod()}

  }
";
	}
}
