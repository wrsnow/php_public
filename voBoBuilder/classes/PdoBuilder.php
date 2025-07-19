<?php

class PdoBuilder extends PDO
{
    public function __construct(?array $config = null)
    {
        if (null === $config) {
            $config = $this->getConfig();
        }

        $dsn = $this->buildDsn($config);

        try {
            parent::__construct($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $pdoException) {
            error_log($pdoException);

            throw new RuntimeException('Failed to build a PDO instance');
        }
    }

    private function buildDsn(array $config): string
    {
        $dsn = "{$config['driver']}:dbname={$config['dbname']}";

        if (false === empty($config['host'])) {
            $dsn .= ";host={$config['host']}";

            if (false === empty($config['port'])) {
                $dsn .= ";port={$config['port']}";
            }
        } elseif (false === empty($config['unix_socket'])) {
            $dsn .= ";unix_socket={$config['unix_socket']}";
        }

        if (false === empty($config['charset'])) {
            $dsn .= ";charset={$config['charset']}";
        }

        return $dsn;
    }

    private function getConfig(): array
    {
        $configFilePath = __DIR__ . '/../config/pdo.ini';

        if (false === file_exists($configFilePath)) {
            throw new RuntimeException('PDO configuration file not found');
        }

        $config = parse_ini_file($configFilePath);

        if (!$config) {
            throw new RuntimeException('Failed to parse PDO configuration file');
        }

        if (empty($config['driver'])) {
            throw new RuntimeException('Set PDO driver');
        }

        if (empty($config['dbname'])) {
            throw new RuntimeException('Set PDO dbname');
        }

        if (empty($config['username'])) {
            throw new RuntimeException('Set PDO username');
        }

        if (false === isset($config['password'])) {
            throw new RuntimeException('Set PDO password');
        }

        return $config;
    }
}
