# Qamera

A small PHP site for sharing photos from a party. Someone makes an event, prints the QR code, and everyone who scans it can drop their photos into the same gallery. No app, no account.

I built it as a hobby project after being asked for something like this. The site itself is in Dutch.

<img width="1897" height="895" alt="image" src="https://github.com/user-attachments/assets/c21bd462-b42f-493f-846d-9b372f97c379" />


## How it works

You create an event on `maak.php`: pick a name and one of four themes. You land on the event page with a link that contains a secret key. That link is the organiser link. Open it and you get the QR code, a print button, a zip download and a delete button on every photo.

Guests use the same page without the key. The QR code only contains that plain link, so scanning it never gives anyone organiser rights. If you lose the organiser link you lose control of the event, since there are no accounts. Keep it somewhere safe.

Uploads are checked on the real file content (not the file name), limited to 10 MB each, and shrunk to 1280 px wide before saving. The event page shows the newest 40 photos with a "show more" link.

## Running it locally

You need PHP 8 with PDO MySQL, GD and ZipArchive, plus MySQL or MariaDB. I use USBWebserver on Windows.

1. Create a database and import `database.sql`.
2. Copy `db.example.php` to `db.php` and fill in your own details. `db.php` is in `.gitignore`, so it won't be committed.
3. Make sure PHP can write to `uploads/`.
4. Raise `upload_max_filesize` to `10M` and `post_max_size` to `40M` in `php.ini`.
5. Open `index.php`.

## Not done yet

## Not done yet

- Videos. The database already has a column for it, the upload code doesn't.
- Thumbnails. A gallery with hundreds of photos is still heavy on a phone.
- Accounts. Right now the organiser link is the only key.
- Languages. The site is Dutch only. I'd like to add English and French.

## Files

- `index.php` is the home page
- `maak.php` creates an event
- `event.php` is the event page: upload, gallery, organiser tools
- `zip.php` builds the zip download
- `db.example.php` is a template for the database connection
- `database.sql` creates the two tables
