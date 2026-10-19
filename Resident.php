<?php
require_once __DIR__ . '/database.php';

class Resident {
    private PDO $conn;
    private string $table = 'residents';

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function addResident($firstName, $middleName, $lastName, $age, $gender, $address, $contactNumber) {
        return Database::runTransaction($this->conn, function (PDO $connection) use ($firstName, $middleName, $lastName, $age, $gender, $address, $contactNumber): bool {
            $query = "INSERT INTO " . $this->table . " (first_name, middle_name, last_name, age, gender, address, contact_number) VALUES (:first_name, :middle_name, :last_name, :age, :gender, :address, :contact_number)";
            $stmt = $connection->prepare($query);
            return $stmt->execute([
                ':first_name' => trim($firstName), ':middle_name' => trim($middleName),
                ':last_name' => trim($lastName), ':age' => (int) $age,
                ':gender' => $gender, ':address' => trim($address),
                ':contact_number' => trim($contactNumber)
            ]);
        }, 'Resident insert error');
    }

    public function getResidents() {
        try {
            $query = "SELECT * FROM " . $this->table . " ORDER BY resident_id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log('Resident fetch error: ' . $e->getMessage());
            return [];
        }
    }

    public function getResidentById(int $id): ?array {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE resident_id = :id");
            $stmt->execute([':id' => $id]);
            $resident = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resident ?: null;
        } catch(PDOException $e) {
            error_log('Resident lookup error: ' . $e->getMessage());
            return null;
        }
    }

    public function updateResident($id, $firstName, $middleName, $lastName, $age, $gender, $address, $contactNumber) {
        return Database::runTransaction($this->conn, function (PDO $connection) use ($id, $firstName, $middleName, $lastName, $age, $gender, $address, $contactNumber): bool {
            $query = "UPDATE " . $this->table . " SET first_name = :first_name, middle_name = :middle_name, last_name = :last_name, age = :age, gender = :gender, address = :address, contact_number = :contact_number WHERE resident_id = :id";
            $stmt = $connection->prepare($query);
            return $stmt->execute([
                ':id' => (int) $id, ':first_name' => trim($firstName),
                ':middle_name' => trim($middleName), ':last_name' => trim($lastName),
                ':age' => (int) $age, ':gender' => $gender,
                ':address' => trim($address), ':contact_number' => trim($contactNumber)
            ]);
        }, 'Resident update error');
    }

    public function deleteResident($id) {
        return Database::runTransaction($this->conn, function (PDO $connection) use ($id): bool {
            $query = "DELETE FROM " . $this->table . " WHERE resident_id = :id";
            $stmt = $connection->prepare($query);
            return $stmt->execute([':id' => (int) $id]);
        }, 'Resident delete error');
    }

    public function searchResidents($keyword) {
        try {
            $query = "SELECT * FROM {$this->table}
                      WHERE first_name LIKE :first_name
                      OR middle_name LIKE :middle_name
                      OR last_name LIKE :last_name
                      OR address LIKE :address
                      OR contact_number LIKE :contact_number
                      ORDER BY resident_id DESC";
            $stmt = $this->conn->prepare($query);
            $searchTerm = '%' . trim($keyword) . '%';
            $stmt->execute([
                ':first_name' => $searchTerm,
                ':middle_name' => $searchTerm,
                ':last_name' => $searchTerm,
                ':address' => $searchTerm,
                ':contact_number' => $searchTerm
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log('Resident search error: ' . $e->getMessage());
            return [];
        }
    }
}
?>