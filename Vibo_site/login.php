<?php
    require_once __DIR__ . '/common.php';
    if (!db()->query('SELECT COUNT(*) FROM admins')->fetchColumn()) { header('Location: setup.php'); exit; }
    if (!empty($_SESSION['admin_id'])) { header('Location: admin.php'); exit; }

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $q = db()->prepare('SELECT * FROM admins WHERE username = ?');
        $q->execute([(string)($_POST['username'] ?? '')]);
        $user = $q->fetch();
        if ($user && password_verify((string)($_POST['password'] ?? ''), $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $user['id'];
            header('Location: admin.php'); exit;
        }
        $error = 'Wrong username or password.';
    }

    page_start('Admin login');
    if ($error) echo '<p class="error">' . h($error) . '</p>';
?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
        <label>Username</label><input name="username" value="admin" required>
        <label>Password</label><input name="password" type="password" required>
        <button>Log in</button>
    </form>

<?php page_end(); ?>
