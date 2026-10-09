<?php
    require_once __DIR__ . '/common.php';

    $count = (int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();

    if ($count > 0) { header('Location: login.php'); exit; }

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();

        $password = (string)($_POST['password'] ?? '');

        if (strlen($password) < 8) $error = 'Choose a password with at least 8 characters.';
        else {
            db()->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')
                ->execute(['admin', password_hash($password, PASSWORD_DEFAULT)]);
            header('Location: login.php'); exit;
        }
    }

    page_start('Create admin password');

    if ($error) echo '<p class="error">' . h($error) . '</p>';
?>
    <p>One-time setup. Username: <strong>admin</strong></p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
        <label>Password (min. 8 characters)</label>
        <input name="password" type="password" minlength="8" required>
        <button>Create admin account</button>
    </form>

<?php page_end(); ?>
