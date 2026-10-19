<?php
class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $configuration = [
                'host' => getenv('BICMS_DB_HOST') ?: 'localhost',
                'database' => getenv('BICMS_DB_NAME') ?: 'bicms_db',
                'username' => getenv('BICMS_DB_USER') ?: 'root',
                'password' => getenv('BICMS_DB_PASSWORD') ?: ''
            ];
            $localConfigurationFile = __DIR__ . '/database.local.php';
            if (is_file($localConfigurationFile)) {
                $localConfiguration = require $localConfigurationFile;
                if (!is_array($localConfiguration)) {
                    throw new UnexpectedValueException('Database local configuration must return an array.');
                }
                $configuration = array_merge($configuration, $localConfiguration);
            }

            $dsn = "mysql:host={$configuration['host']};dbname={$configuration['database']};charset=utf8mb4";
            $this->conn = new PDO($dsn, $configuration['username'], $configuration['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch(PDOException $exception) {
            error_log('Database connection error: ' . $exception->getMessage());
        }
        return $this->conn;
    }

    public static function runTransaction(PDO $connection, callable $operation, string $context): bool {
        try {
            $connection->beginTransaction();
            $result = $operation($connection);

            if (!$result) {
                $connection->rollBack();
                return false;
            }

            $connection->commit();
            return true;
        } catch (PDOException $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log($context . ': ' . $exception->getMessage());
            return false;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }
}
?>