<?php
require_once __DIR__ . '/../classes/auth_guard.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Location: ../index.php?tab=residents');
    exit;
}
verifyCsrfToken();

include_once __DIR__ . '/../classes/database.php';
include_once __DIR__ . '/../classes/Resident.php';

$database = new Database();
$resident = new Resident($database->getConnection());
$deleted = $resident->deleteResident($_POST['id'] ?? '');

$message = $deleted ? 'Resident record deleted successfully!' : 'Failed to delete record.';
$type = $deleted ? 'success' : 'error';
header('Location: ../index.php?tab=residents&message=' . urlencode($message) . '&messageType=' . $type);
exit;
