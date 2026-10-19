<?php
class UserAuth {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function createTable(): void {
        $this->conn->exec("CREATE TABLE IF NOT EXISTS bicms_users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(190) NOT NULL,
            gender VARCHAR(20) NOT NULL,
            address VARCHAR(255) NOT NULL,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $columnDefinitions = $this->conn->query("SHOW COLUMNS FROM bicms_users")->fetchAll(PDO::FETCH_ASSOC);
        $columns = array_column($columnDefinitions, null, 'Field');
        $newColumns = [
            'first_name' => 'VARCHAR(100) NULL',
            'last_name' => 'VARCHAR(100) NULL',
            'email' => 'VARCHAR(190) NULL',
            'gender' => 'VARCHAR(20) NULL',
            'address' => 'VARCHAR(255) NULL'
        ];
        foreach ($newColumns as $column => $definition) {
            if (!array_key_exists($column, $columns)) {
                $this->conn->exec("ALTER TABLE bicms_users ADD COLUMN {$column} {$definition}");
                $columns[$column] = ['Field' => $column, 'Null' => 'YES'];
            }
        }

        $this->conn->exec("UPDATE bicms_users
            SET first_name = COALESCE(NULLIF(TRIM(first_name), ''), 'Not provided'),
                last_name = COALESCE(NULLIF(TRIM(last_name), ''), 'Not provided'),
                email = COALESCE(NULLIF(TRIM(email), ''), CONCAT('not-provided+', user_id, '@example.invalid')),
                gender = COALESCE(NULLIF(TRIM(gender), ''), 'Not specified'),
                address = COALESCE(NULLIF(TRIM(address), ''), 'Not provided')
            WHERE first_name IS NULL OR TRIM(first_name) = ''
               OR last_name IS NULL OR TRIM(last_name) = ''
               OR email IS NULL OR TRIM(email) = ''
               OR gender IS NULL OR TRIM(gender) = ''
               OR address IS NULL OR TRIM(address) = ''");

        $requiredColumns = [
            'first_name' => 'VARCHAR(100) NOT NULL',
            'last_name' => 'VARCHAR(100) NOT NULL',
            'email' => 'VARCHAR(190) NOT NULL',
            'gender' => 'VARCHAR(20) NOT NULL',
            'address' => 'VARCHAR(255) NOT NULL'
        ];
        foreach ($requiredColumns as $column => $definition) {
            if (($columns[$column]['Null'] ?? 'NO') === 'YES') {
                $this->conn->exec("ALTER TABLE bicms_users MODIFY COLUMN {$column} {$definition}");
            }
        }
    }

    public function hasUsers(): bool {
        return (bool) $this->conn->query("SELECT 1 FROM bicms_users LIMIT 1")->fetchColumn();
    }

    public function createAccount(
        string $firstName,
        string $lastName,
        string $email,
        string $gender,
        string $address,
        string $username,
        string $password
    ): bool {
        $stmt = $this->conn->prepare(
            "INSERT INTO bicms_users (first_name, last_name, email, gender, address, username, password_hash)
             VALUES (:first_name, :last_name, :email, :gender, :address, :username, :password_hash)"
        );
        try {
            return $stmt->execute([
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':email' => $email,
                ':gender' => $gender,
                ':address' => $address,
                ':username' => $username,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT)
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return false;
            }
            throw $exception;
        }
    }

    public function authenticate(string $username, string $password): ?array {
        $stmt = $this->conn->prepare(
            "SELECT user_id, first_name, last_name, username, password_hash
             FROM bicms_users WHERE username = :username"
        );
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        if ($displayName === 'Not provided Not provided') {
            $displayName = $user['username'];
        }

        return [
            'user_id' => (int) $user['user_id'],
            'username' => $user['username'],
            'display_name' => $displayName !== '' ? $displayName : $user['username']
        ];
    }
}
?>
