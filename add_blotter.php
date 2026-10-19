<?php
require_once __DIR__ . '/../classes/auth_guard.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?tab=blotter');
    exit;
}
verifyCsrfToken();

include_once __DIR__ . '/../classes/database.php';
include_once __DIR__ . '/../classes/Blotter.php';

$database = new Database();
$db = $database->getConnection();
$blotter = new Blotter($db);
$blotter->createTable();
$added = $blotter->addRecord(
    $_POST['complainant_name'] ?? '',
    $_POST['respondent_name'] ?? '',
    $_POST['incident_type'] ?? '',
    $_POST['details'] ?? '',
    $_POST['status'] ?? 'Pending'
);

$message = $added ? 'Blotter record successfully logged!' : 'Failed to log blotter case.';
$type = $added ? 'success' : 'error';
header('Location: ../index.php?tab=blotter&message=' . urlencode($message) . '&messageType=' . $type);
exit;
