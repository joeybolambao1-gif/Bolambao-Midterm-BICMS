<?php
require_once __DIR__ . '/database.php';

class Blotter {
    private PDO $conn;
    private string $table = 'blotter_records';

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function createTable(): void {
        $this->conn->exec("CREATE TABLE IF NOT EXISTS blotter_records (
            blotter_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            complainant_name VARCHAR(100) NOT NULL,
            respondent_name VARCHAR(100) NOT NULL,
            incident_type VARCHAR(100) NOT NULL,
            details TEXT NOT NULL,
            status ENUM('Pending', 'Settled') NOT NULL DEFAULT 'Pending',
            date_recorded TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function addRecord(string $complainantName, string $respondentName, string $incidentType, string $details, string $status): bool {
        return Database::runTransaction($this->conn, function (PDO $connection) use ($complainantName, $respondentName, $incidentType, $details, $status): bool {
            $query = "INSERT INTO {$this->table} (complainant_name, respondent_name, incident_type, details, status)
                      VALUES (:complainant_name, :respondent_name, :incident_type, :details, :status)";
            $stmt = $connection->prepare($query);
            return $stmt->execute([
                ':complainant_name' => trim($complainantName),
                ':respondent_name' => trim($respondentName),
                ':incident_type' => trim($incidentType),
                ':details' => trim($details),
                ':status' => $status === 'Settled' ? 'Settled' : 'Pending'
            ]);
        }, 'Blotter insert error');
    }

    public function getRecords(): array {
        $stmt = $this->conn->query("SELECT * FROM {$this->table} ORDER BY date_recorded DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
