# ViBo - Video Book

**Alpha v0.1.0** · School project · Software prototype

ViBo (Video Book) is a project for a physical book that can play a different video for each page. This repository contains the first **software-only alpha version**. The physical book and its electronics are still under development.

## Features

The prototype has three simple parts:

- **Guest form (PHP):** A customer enters their name, a book title, and uploads 1-3 videos.
- **Admin panel (PHP + MySQL):** An administrator signs in, views submissions, edits page numbers and titles, and downloads a book as a ZIP file.
- **ViBo CLI (Python):** A simple command-line application in the `app` folder that prepares exported files for video conversion and USB/folder output. It also creates `config.json`.

The guest form is a separate page and can later be embedded in an existing website.

The `materials` folder contains the **MySQL database import file** and a **test video** to try the application without creating your own media.

## Technologies

- PHP 8.2, HTML, CSS
- MySQL / MariaDB (via XAMPP)
- Python 3 (standard library)
- FFmpeg (optional, for converting non-MP4 videos)

## Project structure

```text
ViBo-VideoBook/
├── Vibo_site/                 # PHP website and admin panel
│   ├── index.php          # Guest form
│   ├── submit.php         # Upload processing
│   ├── login.php          # Admin login
│   ├── setup.php          # First admin setup
│   ├── admin.php          # Admin panel
│   ├── export.php         # Book ZIP export
│   ├── logout.php
│   ├── common.php
│   ├── config.php         # Local database settings
│   └── style.css
├── app/                       # Python app for file preparation/conversion
│   └── vibo_cli.py             # Command-line application
├── materials/                 # Materials for testing and setup
│   ├── database.sql           # MySQL database import file
│   └── (test video)           # Sample video for testing
└── README.md
```

## Installation (Windows + XAMPP)

1. Install **XAMPP** and start **Apache** and **MySQL**.
2. Copy the files *inside* the `Vibo_site` folder to `<xampp-folder>\htdocs\vibo\`.
   - Example: `C:\xampp\htdocs\vibo\`
   - If XAMPP is installed on another drive, use that drive instead.
3. Open [phpMyAdmin](http://localhost/phpmyadmin), select **Import**, and import `materials/database.sql`. This creates the `vibo` database and its tables.
4. If needed, change the database settings in `Vibo_site/config.php`.
5. Open http://localhost/vibo/setup.php to create the first administrator account (username: `admin`).
6. Open the guest form at http://localhost/vibo/ and the admin panel at http://localhost/vibo/admin.php.

### PHP settings

For larger uploads, edit `php.ini` from **XAMPP → Apache → Config → PHP (php.ini)**:

```ini
upload_max_filesize = 32M
post_max_size = 100M
extension=zip
```

Make sure the ZIP extension is enabled (no `;` before `extension=zip`). Restart Apache after changing the settings. The application accepts up to **3 videos**, maximum **30 MB each**.

## How to use

1. Open the **guest form**, enter a name and book title, and upload 1-3 videos (you can use the sample video from `materials`).
2. Open the **admin panel** and sign in.
3. Select the book, edit the page numbers and titles, and save.
4. Click **Download book ZIP**. The ZIP includes `manifest.json` and the original videos.
5. Open a terminal in the `app` directory and run:

   ```powershell
   python vibo_cli.py
   ```

6. Choose option **1**, enter the path to the exported ZIP, and enter an existing destination folder or USB drive.

Example output:

```text
ViBo_Book_1/
├── config.json
└── videos/
    ├── page_001.mp4
    └── page_002.mp4
```

**MP4 uploads** can be copied without FFmpeg. For MOV, AVI, MKV, or WebM, install FFmpeg and ensure `ffmpeg` is available in your system PATH.

## Current limitations

This is an **alpha prototype** for local demonstration. It does not yet include:

- A completed physical video book
- Raspberry Pi integration or NFC tag writing
- Customer accounts or online order tracking
- Automatic communication between Python and MySQL
- Production-ready deployment and security hardening

The physical components are available for prototyping, but the final internal assembly and hardware integration are still in progress.

## Security and data

The admin password is stored as a hash. PHP uses prepared SQL statements for user-supplied values, and uploaded video files are stored outside the web root in the default XAMPP folder structure.

**Do not publish a live installation as-is.** Before public deployment, review authentication, configuration, upload handling, permissions, and server security. Do not commit real customer videos, passwords, or private data.

## Version

**v0.1.0-alpha** - First working software prototype.

This version focuses on the essential workflow: **customer upload → admin page configuration → ZIP export → Python USB preparation**.
