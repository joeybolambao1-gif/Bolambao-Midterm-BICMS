<?php
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
include_once 'classes/database.php';
include_once 'classes/UserAuth.php';

$db = (new Database())->getConnection();
if (!$db) {
    http_response_code(500);
    exit('Unable to connect to the database. Check the database settings and try again.');
}
$userAuth = new UserAuth($db);
$userAuth->createTable();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$authError = '';
$authMode = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $authError = 'Your session expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'logout') {
        $_SESSION = [];
        session_destroy();
        header('Location: index.php');
        exit;
    } elseif (($_POST['action'] ?? '') === 'register') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $address = trim($_POST['address'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if ($firstName === '' || $lastName === '' || $address === '') {
            $authError = 'Please complete your first name, last name, and address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $authError = 'Please enter a valid email address.';
        } elseif (!in_array($gender, ['Male', 'Female'], true)) {
            $authError = 'Please select a valid gender.';
        } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            $authError = 'Username must be 3–50 characters and use only letters, numbers, dots, dashes, or underscores.';
        } elseif (strlen($password) < 8) {
            $authError = 'Choose a password with at least 8 characters.';
        } elseif ($password !== $confirmPassword) {
            $authError = 'The passwords do not match.';
        } elseif ($userAuth->createAccount($firstName, $lastName, $email, $gender, $address, $username, $password)) {
            $user = $userAuth->authenticate($username, $password);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['display_name'] = $user['display_name'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: index.php');
            exit;
        } else {
            $authError = 'That username is already taken. Please choose another.';
        }
        $authMode = 'register';
    } elseif (($_POST['action'] ?? '') === 'login') {
        $user = $userAuth->authenticate(trim($_POST['username'] ?? ''), $_POST['password'] ?? '');
        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['display_name'] = $user['display_name'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: index.php');
            exit;
        }
        $authError = 'The username or password you entered is incorrect.';
    }
}

if (empty($_SESSION['user_id'])):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $authMode === 'register' ? 'Create an account | BICMS' : 'Sign in | BICMS'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo filemtime(__DIR__ . '/assets/style.css'); ?>">
</head>
<body class="auth-page <?php echo $authMode === 'register' ? 'auth-register-page' : ''; ?>">
    <main class="auth-card <?php echo $authMode === 'register' ? 'auth-card-register' : ''; ?>">
        <?php if ($authMode === 'register'): ?>
            <p class="auth-eyebrow auth-register-eyebrow">BARANGAY STAFF</p>
            <h1 class="auth-register-title">Create an account</h1>
            <p class="auth-intro auth-register-intro">Register as a barangay staff member</p>
        <?php else: ?>
            <div class="auth-mark"><i class="fa-solid fa-shield-halved"></i></div>
            <p class="auth-eyebrow">BARANGAY MANAGEMENT SYSTEM</p>
            <h1>Sign in to your dashboard</h1>
            <p class="auth-intro">Enter your details to open the barangay workspace.</p>
        <?php endif; ?>

        <?php if ($authError): ?>
            <div class="auth-alert" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($authError); ?></div>
        <?php endif; ?>

        <?php if ($authMode === 'register'): ?>
            <form class="auth-form auth-register-form" method="POST" action="index.php?mode=register">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="register">
                <p class="auth-section-title">YOUR DETAILS</p>
                <div class="auth-fields">
                    <div class="auth-field">
                        <label for="first_name">First Name</label>
                        <input id="first_name" name="first_name" type="text" autocomplete="given-name" maxlength="100" required>
                    </div>
                    <div class="auth-field">
                        <label for="last_name">Last Name</label>
                        <input id="last_name" name="last_name" type="text" autocomplete="family-name" maxlength="100" required>
                    </div>
                    <div class="auth-field auth-field-wide">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" autocomplete="email" maxlength="190" required>
                    </div>
                    <div class="auth-field">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="auth-field">
                        <label for="address">Address</label>
                        <input id="address" name="address" type="text" autocomplete="street-address" maxlength="255" required>
                    </div>
                </div>
                <p class="auth-section-title">LOGIN DETAILS</p>
                <div class="auth-fields">
                    <div class="auth-field auth-field-wide">
                        <label for="username">Username</label>
                        <input id="username" name="username" type="text" autocomplete="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9_.-]+" required>
                    </div>
                    <div class="auth-field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                    </div>
                    <div class="auth-field">
                        <label for="confirm_password">Confirm Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" required>
                    </div>
                    <button class="auth-submit auth-register-submit" type="submit">Create Account</button>
                </div>
            </form>
            <p class="auth-switch">Already have an account? <a href="index.php">Sign in</a></p>
        <?php else: ?>
            <form class="auth-form" method="POST" action="index.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="login">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" autocomplete="username" required>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button class="auth-submit" type="submit">Sign in</button>
            </form>
            <p class="auth-switch">First time here? <a href="index.php?mode=register">Register / Create account</a></p>
        <?php endif; ?>
        <p class="auth-footer">BICMS <span>&bull;</span> Barangay Information &amp; Community Services</p>
    </main>
</body>
</html>
<?php
exit;
endif;

include_once 'classes/Resident.php';
include_once 'classes/Blotter.php';

$resident = new Resident($db);
$blotter = new Blotter($db);
$blotter->createTable();

$message = $_GET['message'] ?? '';
$messageType = $_GET['messageType'] ?? '';
$activeTab = $_GET['tab'] ?? 'residents';
$keyword = trim($_GET['search'] ?? '');
$editData = isset($_GET['edit']) ? $resident->getResidentById((int) $_GET['edit']) : null;
$isEditing = $editData !== null;
if ($isEditing) {
    $activeTab = 'residents';
}

$allResidents = $resident->getResidents();
$residentsList = $keyword ? $resident->searchResidents($keyword) : $allResidents;
$totalResidents = count($allResidents);
$maleCount = count(array_filter($allResidents, fn($row) => strtolower($row['gender']) === 'male'));
$femaleCount = count(array_filter($allResidents, fn($row) => strtolower($row['gender']) === 'female'));
$blotterList = $blotter->getRecords();
$totalBlotters = count($blotterList);
$pageTitles = [
    'residents' => 'Resident Records Management Module',
    'clearance' => 'Barangay Document Issuance Module',
    'blotter' => 'Blotter & Incident Monitoring Module',
    'analytics' => 'Demographic Analytics & Reports'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BICMS - Barangay Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <aside class="sidebar">
        <div>
            <div class="sidebar-brand">
                <i class="fa-solid fa-shield-halved"></i>
                <span>BICMS Core</span>
            </div>
            <ul class="sidebar-menu">
                <li class="<?php echo $activeTab === 'residents' ? 'active' : ''; ?>">
                    <a href="index.php?tab=residents"><i class="fa-solid fa-users-rectangle"></i> Residents Directory</a>
                </li>
                <li class="<?php echo $activeTab === 'clearance' ? 'active' : ''; ?>">
                    <a href="index.php?tab=clearance"><i class="fa-solid fa-file-shield"></i> Clearances & Permits</a>
                </li>
                <li class="<?php echo $activeTab === 'blotter' ? 'active' : ''; ?>">
                    <a href="index.php?tab=blotter"><i class="fa-solid fa-triangle-exclamation"></i> Blotter Monitoring</a>
                </li>
                <li class="<?php echo $activeTab === 'analytics' ? 'active' : ''; ?>">
                    <a href="index.php?tab=analytics"><i class="fa-solid fa-chart-pie"></i> Barangay Analytics</a>
                </li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <strong>Enterprise Architecture</strong>
            <p>PHP OOP &bull; PDO Database Layer</p>
        </div>
    </aside>

    <main class="main-content">
        <nav class="navbar no-print">
            <h2><i class="fa-solid fa-folder-open"></i> <?php echo $pageTitles[$activeTab] ?? $pageTitles['residents']; ?></h2>
            <div class="user-profile">
                <i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username']); ?>
                <form method="POST" action="index.php" class="logout-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="logout-button">Sign out</button>
                </form>
            </div>
        </nav>

        <section class="content-body">
            <?php if ($activeTab === 'residents'): ?>
                <div class="stats-grid no-print">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-info">
                            <h4>Total Registered Residents</h4>
                            <span><?php echo $totalResidents; ?></span>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="fa-solid fa-mars"></i></div>
                        <div class="stat-info">
                            <h4>Male Residents</h4>
                            <span><?php echo $maleCount; ?></span>
                        </div>
                    </div>
                    <div class="stat-card cyan">
                        <div class="stat-icon"><i class="fa-solid fa-venus"></i></div>
                        <div class="stat-info">
                            <h4>Female Residents</h4>
                            <span><?php echo $femaleCount; ?></span>
                        </div>
                    </div>
                </div>
                <?php if ($message): ?>
                    <div class="alert <?php echo htmlspecialchars($messageType); ?> no-print"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <div class="panel no-print">
                    <div class="panel-header">
                        <div class="panel-title"><i class="fa-solid fa-address-book"></i> Resident Directory</div>
                        <button class="btn-open-modal" onclick="document.getElementById('addModal').style.display='flex'"><i class="fa-solid fa-user-plus"></i> Add Resident</button>
                    </div>
                    <form class="search-row" method="GET" action="index.php">
                        <input type="hidden" name="tab" value="residents">
                        <label class="sr-only" for="resident-search">Search residents</label>
                        <input id="resident-search" type="text" name="search" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Search by name, address, or contact">
                        <button class="btn-search" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                        <?php if ($keyword): ?><a href="index.php?tab=residents" class="btn-reset">Reset</a><?php endif; ?>
                    </form>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Address</th>
                                    <th>Contact</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($residentsList): ?>
                                    <?php foreach ($residentsList as $row): ?>
                                        <tr>
                                            <td>#<?php echo $row['resident_id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'][0] . '. ' : '') . $row['last_name']); ?></strong></td>
                                            <td><?php echo $row['age']; ?></td>
                                            <td><span class="badge <?php echo strtolower($row['gender']); ?>"><?php echo $row['gender']; ?></span></td>
                                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                                            <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                            <td>
                                                <a href="index.php?edit=<?php echo $row['resident_id']; ?>" class="action-btn btn-edit">Edit</a>
                                                <form method="POST" action="backend/delete_resident.php" class="inline-form" onsubmit="return confirm('Delete this record?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $row['resident_id']; ?>">
                                                    <button type="submit" class="action-btn btn-del">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?><tr><td colspan="7">No records found.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($activeTab === 'clearance'): ?>
                <div class="panel no-print">
                    <div class="panel-title"><i class="fa-solid fa-file-circle-check"></i> Document Generator</div>
                    <form method="GET" action="index.php" class="form-grid">
                        <input type="hidden" name="tab" value="clearance">
                        <div class="field-group">
                            <label for="doc-type">Document Type</label>
                            <select id="doc-type" name="doc_type">
                                <option value="Clearance">Barangay Clearance</option>
                                <option value="Permit">Business Permit</option>
                            </select>
                        </div>
                        <div class="field-group">
                            <label for="resident-ref">Resident</label>
                            <select id="resident-ref" name="resident_ref" required>
                                <option value="">Choose Resident</option>
                                <?php foreach ($allResidents as $res): ?>
                                    <option value="<?php echo $res['resident_id']; ?>">
                                        <?php echo htmlspecialchars($res['first_name'] . ' ' . $res['last_name'] . ' - ' . $res['address']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn-main" type="submit">Generate Preview</button>
                    </form>
                </div>
                <?php if (!empty($_GET['resident_ref'])): $rec = $resident->getResidentById((int) $_GET['resident_ref']); ?>
                    <?php if ($rec): ?>
                        <div class="clearance-preview printable-area">
                            <div class="cert-header">Republic of the Philippines<br>Province of Cebu<br><strong>Office of the Barangay Captain</strong></div>
                            <hr>
                            <h2><?php echo ($_GET['doc_type'] ?? 'Clearance') === 'Clearance' ? 'Certificate of Barangay Clearance' : 'Barangay Business Permit'; ?></h2>
                            <div class="cert-body"><p><strong>TO WHOM IT MAY CONCERN:</strong></p><p>This certifies that <strong><?php echo strtoupper($rec['first_name'] . ' ' . $rec['last_name']); ?></strong>, <?php echo $rec['age']; ?> years old, is a resident of <strong><?php echo htmlspecialchars($rec['address']); ?></strong>.</p><p>Issued this <?php echo date('F j, Y'); ?>.</p></div>
                        </div>
                        <p class="no-print"><button class="btn-search" onclick="window.print()">Print Document</button></p>
                    <?php endif; ?>
                <?php endif; ?>

            <?php elseif ($activeTab === 'blotter'): ?>
                <?php if ($message): ?><div class="alert <?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <div class="panel">
                    <div class="panel-header"><div class="panel-title"><i class="fa-solid fa-triangle-exclamation"></i> Blotter Records</div><button class="btn-open-modal" onclick="document.getElementById('blotterModal').style.display='flex'">File New Blotter</button></div>
                    <div class="table-container"><table><thead><tr><th>Case</th><th>Complainant</th><th>Respondent</th><th>Incident</th><th>Details</th><th>Status</th><th>Date</th></tr></thead><tbody>
                        <?php if ($blotterList): foreach ($blotterList as $record): ?><tr><td>#<?php echo $record['blotter_id']; ?></td><td><?php echo htmlspecialchars($record['complainant_name']); ?></td><td><?php echo htmlspecialchars($record['respondent_name']); ?></td><td><?php echo htmlspecialchars($record['incident_type']); ?></td><td><?php echo htmlspecialchars($record['details']); ?></td><td><span class="badge <?php echo strtolower($record['status']); ?>"><?php echo $record['status']; ?></span></td><td><?php echo $record['date_recorded']; ?></td></tr><?php endforeach; else: ?><tr><td colspan="7">No blotter cases recorded.</td></tr><?php endif; ?>
                    </tbody></table></div>
                </div>

            <?php elseif ($activeTab === 'analytics'): ?>
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-chart-pie"></i> Barangay Analytics</div>
                    <div class="stats-grid">
                        <div class="stat-card blue"><div class="stat-info"><h4>Total Population</h4><span><?php echo $totalResidents; ?></span></div></div>
                        <div class="stat-card green"><div class="stat-info"><h4>Total Blotter Cases</h4><span><?php echo $totalBlotters; ?></span></div></div>
                        <div class="stat-card cyan"><div class="stat-info"><h4>Gender Ratio</h4><span><?php echo $totalResidents ? round($maleCount / $totalResidents * 100) . '% / ' . round($femaleCount / $totalResidents * 100) . '%' : '0%'; ?></span></div></div>
                    </div>
                    <p>System manages <?php echo $totalResidents; ?> residents and <?php echo $totalBlotters; ?> recorded complaints.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php include_once 'modals/modals.php'; ?>
</body>
</html>
