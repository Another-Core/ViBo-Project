<?php
// XAMPP defaults. Change these values if your MySQL settings differ.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'vibo';
const DB_USER = 'root';
const DB_PASS = '';

// Files stay outside htdocs (when the project is installed at htdocs/vibo).
const UPLOAD_DIR = __DIR__ . '/../../vibo_uploads';
const MAX_FILES = 3;
const MAX_FILE_BYTES = 30 * 1024 * 1024;
