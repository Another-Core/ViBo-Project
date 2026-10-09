<?php
    require_once __DIR__ . '/common.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

    // Guest uploads work even when the form is embedded via a cross-site iframe.
    page_start('Submission result');

    $filesOnDisk = [];

    try {
        $name = trim((string)($_POST['name'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));

        if ($name === '' || $title === '' || mb_strlen($name) > 100 || mb_strlen($title) > 150) {
            throw new RuntimeException('Enter a valid name and book title.');
        }

        $uploads = $_FILES['videos'] ?? null;
        $count = ($uploads && is_array($uploads['name'] ?? null)) ? count($uploads['name']) : 0;

        if ($count < 1 || $count > MAX_FILES) throw new RuntimeException('Choose 1 to ' . MAX_FILES . ' videos.');

        $allowed = [
            'mp4'=>['video/mp4','application/mp4'],
            'mov'=>['video/quicktime'],
            'avi'=>['video/x-msvideo','video/avi'],
            'mkv'=>['video/x-matroska'],
            'webm'=>['video/webm']
        ];

        $prepared = [];
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        for ($i = 0; $i < $count; $i++) {
            if ($uploads['error'][$i] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed. Check PHP upload limits.');
            if ($uploads['size'][$i] > MAX_FILE_BYTES || $uploads['size'][$i] === 0) throw new RuntimeException('Each video must be 1–30 MB.');

            $original = basename((string)$uploads['name'][$i]);
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $mime = $finfo->file($uploads['tmp_name'][$i]);

            if (!isset($allowed[$ext]) || !in_array($mime, $allowed[$ext], true)) {
                throw new RuntimeException('Unsupported video format: ' . $original);
            }

            $prepared[] = [ 'tmp'=>$uploads['tmp_name'][$i], 'original'=>$original, 'ext'=>$ext ];
        }
        if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0750, true) && !is_dir(UPLOAD_DIR)) {
            throw new RuntimeException('Cannot create upload directory.');
        }

        db()->beginTransaction();
        $stmt = db()->prepare('INSERT INTO books (customer_name, title) VALUES (?, ?)');
        $stmt->execute([$name, $title]);
        $bookId = (int)db()->lastInsertId();
        $insert = db()->prepare('INSERT INTO pages (book_id, page_number, title, stored_filename, original_filename) VALUES (?, ?, ?, ?, ?)');

        foreach ($prepared as $i => $file) {
            $stored = bin2hex(random_bytes(16)) . '.' . $file['ext'];
            $path = UPLOAD_DIR . '/' . $stored;
            if (!move_uploaded_file($file['tmp'], $path)) throw new RuntimeException('Failed to save an uploaded file.');
            $filesOnDisk[] = $path;
            $pageTitle = pathinfo($file['original'], PATHINFO_FILENAME);
            $insert->execute([$bookId, $i + 1, mb_substr($pageTitle, 0, 150), $stored, $file['original']]);
        }

        db()->commit();
        echo '<p class="notice">Thank you! Your book #'. $bookId .' was submitted with '. count($prepared) .' video(s).</p>';
        echo '<p><a href="index.php">Submit another book</a></p>';
    } catch (Throwable $e) {
        // Avoid throwing another exception when the database connection itself failed.
        try {
            if (db()->inTransaction()) db()->rollBack();
        } catch (Throwable $ignored) {}

        foreach ($filesOnDisk as $path) @unlink($path);

        echo '<p class="error">' . h($e instanceof PDOException ? 'Database error. Check your MySQL connection.' : $e->getMessage()) . '</p>';
        echo '<p><a href="index.php">Try again</a></p>';
    }
page_end();
