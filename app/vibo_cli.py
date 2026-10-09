"""ViBo Alpha v0.1.0 — export videos from an admin ZIP to a folder / USB."""
import json
import re
import shutil
import subprocess
import tempfile
import zipfile
from pathlib import Path


def export_book(archive: Path, destination: Path) -> Path:
    if not archive.is_file():
        raise ValueError("ZIP file does not exist.")
    with zipfile.ZipFile(archive) as z:
        manifest = json.loads(z.read("manifest.json").decode("utf-8"))
        book_id = int(manifest["book_id"])
        if book_id <= 0:
            raise ValueError("Invalid book ID.")
        pages = manifest["pages"]
        if not isinstance(pages, list) or not 1 <= len(pages) <= 3:
            raise ValueError("Expected 1–3 pages.")
        output = destination / f"ViBo_Book_{book_id}"
        if output.exists():
            raise ValueError(f"Folder already exists: {output}. Choose another destination.")
        # Validate all ZIP entries before touching the destination.
        used = set()
        for p in pages:
            num = int(p["page_number"])
            src = p["input_file"]
            if num not in range(1, 1000) or num in used:
                raise ValueError("Page numbers must be unique and between 1 and 999.")
            if not re.fullmatch(r"videos/video_\d+\.(mp4|mov|avi|mkv|webm)", src):
                raise ValueError("Invalid video path in manifest.")
            if z.getinfo(src).file_size > 30 * 1024 * 1024:
                raise ValueError("Video is too large.")
            used.add(num)
        ffmpeg = shutil.which("ffmpeg")
        if not ffmpeg and any(Path(p["input_file"]).suffix.lower() != ".mp4" for p in pages):
            raise RuntimeError("FFmpeg is not installed. Install FFmpeg or upload only MP4 videos.")
        output.mkdir(parents=True, exist_ok=False)
        (output / "videos").mkdir()
        configuration = {"book_id": book_id, "book_title": manifest["book_title"], "pages": []}
        try:
            with tempfile.TemporaryDirectory() as tmp:
                for p in sorted(pages, key=lambda p: int(p["page_number"])):
                    num = int(p["page_number"])
                    src = p["input_file"]
                    source = Path(tmp) / Path(src).name
                    with z.open(src) as input_file, source.open("wb") as dest_file:
                        shutil.copyfileobj(input_file, dest_file)
                    target = output / "videos" / f"page_{num:03d}.mp4"
                    print(f"Page {num}: {p['title']}")
                    if source.suffix.lower() == ".mp4":
                        shutil.copy2(source, target)
                    else:
                        command = [ffmpeg, "-y", "-i", str(source), "-c:v", "libx264",
                                   "-preset", "ultrafast", "-crf", "28", "-c:a", "aac", str(target)]
                        result = subprocess.run(command, capture_output=True, text=True)
                        if result.returncode != 0:
                            raise RuntimeError("FFmpeg error: " + result.stderr[-500:])
                    configuration["pages"].append({
                        "page": num, "title": p["title"], "video": f"videos/page_{num:03d}.mp4"
                    })
            (output / "config.json").write_text(json.dumps(configuration, ensure_ascii=False, indent=2), encoding="utf-8")
            return output
        except Exception:
            shutil.rmtree(output)  # Only the folder created by this run.
            raise


def main() -> None:
    print("=== ViBo CLI — ALPHA v0.1.0 ===")
    print("1. Export book ZIP to USB / folder")
    print("2. Exit")
    if input("Choice: ").strip() != "1":
        return
    archive = Path(input("Path to ZIP: ").strip().strip('"'))
    destination = Path(input("Destination folder or USB drive (e.g. E:\\): ").strip().strip('"'))
    if not destination.is_dir():
        print("ERROR: Destination folder / drive not found.")
        return
    try:
        print("SUCCESS:", export_book(archive, destination))
    except (OSError, ValueError, KeyError, zipfile.BadZipFile, RuntimeError) as error:
        print("ERROR:", error)


if __name__ == "__main__":
    main()
