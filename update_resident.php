<?php
require_once __DIR__ . '/../classes/auth_guard.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['resident_id'])) {
    header('Location: ../index.php?tab=residents');
    exit;
}
verifyCsrfToken();

include_once __DIR__ . '/../classes/database.php';
include_once __DIR__ . '/../classes/Resident.php';

$database = new Database();
$resident = new Resident($database->getConnection());
$data = [
    $_POST['resident_id'] ?? '',
    $_POST['first_name'] ?? '',
    $_POST['middle_name'] ?? '',
    $_POST['last_name'] ?? '',
    $_POST['age'] ?? '',
    $_POST['gender'] ?? '',
    $_POST['address'] ?? '',
    $_POST['contact_number'] ?? ''
];
$updated = $resident->updateResident(...$data);

$message = $updated ? 'Resident record updated successfully!' : 'Failed to update record.';
$type = $updated ? 'success' : 'error';
header('Location: ../index.php?tab=residents&message=' . urlencode($message) . '&messageType=' . $type);
exit;
