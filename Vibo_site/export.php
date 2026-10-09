<?php
require_once __DIR__ . '/common.php';
require_admin();

$id = (int)($_GET['book'] ?? 0);
$q = db()->prepare('SELECT * FROM books WHERE id = ?');
$q->execute([$id]);
$book = $q->fetch();

if (!$book) { http_response_code(404); exit('Book not found.'); }

$q = db()->prepare('SELECT * FROM pages WHERE book_id = ? ORDER BY page_number, id');
$q->execute([$id]);
$pages = $q->fetchAll();

if (!$pages) exit('There are no videos in this book.');

if (!class_exists('ZipArchive')) exit('PHP ZIP extension is not enabled.');

$zipPath = tempnam(sys_get_temp_dir(), 'vibo_');
$zip = new ZipArchive();

if (!$zipPath || $zip->open($zipPath, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) exit('Cannot create ZIP.');

$manifest = ['book_id'=>$id, 'book_title'=>$book['title'], 'customer_name'=>$book['customer_name'], 'pages'=>[]];

foreach ($pages as $page) {
    $file = UPLOAD_DIR . '/' . $page['stored_filename'];
    
    if (!is_file($file)) { $zip->close(); @unlink($zipPath); exit('Video file missing.'); }
    
    $inside = 'videos/video_' . (int)$page['id'] . '.' . pathinfo($page['stored_filename'], PATHINFO_EXTENSION);
    
    if (!$zip->addFile($file, $inside)) { $zip->close(); @unlink($zipPath); exit('Cannot add video to ZIP.'); }
    
    $manifest['pages'][] = ['page_number'=>(int)$page['page_number'], 'title'=>$page['title'], 'input_file'=>$inside];
}

$zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="ViBo_Book_' . $id . '.zip"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
unlink($zipPath);
