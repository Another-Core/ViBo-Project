<?php
require_once __DIR__ . '/common.php';
require_admin();

$id = (int)($_GET['book'] ?? 0);
$error = '';
$message = '';

if ($id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $pages = db()->prepare('SELECT id FROM pages WHERE book_id = ? ORDER BY id');
    $pages->execute([$id]);
    $pageIds = $pages->fetchAll(PDO::FETCH_COLUMN);
    $updates = [];
    $usedNumbers = [];

    foreach ($pageIds as $pid) {
        $number = filter_var($_POST['number'][$pid] ?? '', FILTER_VALIDATE_INT);
        $title = trim((string)($_POST['title'][$pid] ?? ''));

        if (!$number || $number < 1 || $number > 999 || $title === '' || mb_strlen($title) > 150 || in_array($number, $usedNumbers, true)) {
            $error = 'Use unique page numbers (1–999) and non-empty titles.';
            break;
        }

        $usedNumbers[] = $number;
        $updates[] = [$number, $title, $pid, $id];
    }
    if (!$error) {

        $update = db()->prepare('UPDATE pages SET page_number=?, title=? WHERE id=? AND book_id=?');
        
        foreach ($updates as $values) $update->execute($values);
        
        $message = 'Saved!';
    }
}

page_start('Admin panel');
echo '<p><a href="logout.php">Log out</a></p>';

if ($error) echo '<p class="error">' . h($error) . '</p>';
if ($message) echo '<p class="notice">' . h($message) . '</p>';
if (!$id) {
    $books = db()->query('SELECT b.*, COUNT(p.id) AS total_pages FROM books b LEFT JOIN pages p ON p.book_id=b.id GROUP BY b.id ORDER BY b.id DESC')->fetchAll();
    
    if (!$books) echo '<p>No books yet. <a href="index.php">Open the guest form</a>.</p>';
    
    foreach ($books as $b) {
        echo '<p><a href="admin.php?book=' . (int)$b['id'] . '">#' . (int)$b['id'] . ' - ' . h($b['title']) . '</a> '
            . '(' . h($b['customer_name']) . ', ' . (int)$b['total_pages'] . ' videos)</p>';
    
    }
} else {
    $q = db()->prepare('SELECT * FROM books WHERE id = ?');
    $q->execute([$id]);
    $book = $q->fetch();
    
    if (!$book) {
        http_response_code(404);
        echo '<p>Book not found.</p>';
        page_end();
        exit;
    }
    
    $q = db()->prepare('SELECT * FROM pages WHERE book_id = ? ORDER BY page_number, id');
    $q->execute([$id]);
    $pages = $q->fetchAll();
    
    echo '<p><a href="admin.php">← All books</a></p>';
    echo '<h2>Book #' . $id . ': ' . h($book['title']) . '</h2><p>Customer: ' . h($book['customer_name']) . '</p>';
    echo '<form method="post"><input type="hidden" name="csrf" value="' . h(csrf()) . '">';
    echo '<table><tr><th>Page #</th><th>Title</th><th>Original video</th></tr>';
    
    foreach ($pages as $p) {
        $pid = (int)$p['id'];
    
        echo '<tr><td><input type="number" min="1" max="999" required name="number[' . $pid . ']" value="' . (int)$p['page_number'] . '"></td>'
            . '<td><input maxlength="150" required name="title[' . $pid . ']" value="' . h($p['title']) . '"></td>'
            . '<td>' . h($p['original_filename']) . '</td></tr>';
    }
    echo '</table><button>Save page settings</button></form>';
    echo '<p><a class="btn" href="export.php?book=' . $id . '">Download book ZIP</a></p>';
}

page_end();