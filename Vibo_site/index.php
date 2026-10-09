<?php require_once __DIR__ . '/common.php'; page_start('Send your video book'); ?>
    <p>Enter your details and choose up to <?= MAX_FILES ?> videos. Each video will become one page.</p>
        <form action="submit.php" method="post" enctype="multipart/form-data">
            <label for="name">Your name</label>
            <input id="name" name="name" maxlength="100" required>
            <label for="title">Book title</label>
            <input id="title" name="title" maxlength="150" required>
            <label for="videos">Videos (MP4, MOV, AVI, MKV or WebM; max 30 MB each)</label>
            <input id="videos" name="videos[]" type="file" accept=".mp4,.mov,.avi,.mkv,.webm" multiple required>
            <button type="submit">Send videos</button>
        </form>
<?php page_end(); ?>
