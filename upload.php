<?php
require __DIR__ . '/config.php';

function backWithError($message) {
    header('Location: index.php?error=' . rawurlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$title = trim($_POST['title'] ?? '');
$artist = trim($_POST['artist'] ?? '');

if ($title === '' || $artist === '') {
    backWithError('Please enter both the song title and artist.');
}
if (!isset($_FILES['mp3']) || $_FILES['mp3']['error'] !== UPLOAD_ERR_OK) {
    backWithError('Please choose an MP3 file. Check the upload size limit if the file is large.');
}

$file = $_FILES['mp3'];
$maxBytes = 25 * 1024 * 1024; // 25 MB
if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
    backWithError('The MP3 must be smaller than 25 MB.');
}

$originalName = $file['name'] ?? '';
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if ($extension !== 'mp3') {
    backWithError('Only .mp3 files are allowed.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!in_array($mime, ['audio/mpeg', 'audio/mp3', 'application/octet-stream'], true)) {
    backWithError('The selected file does not look like an MP3 audio file.');
}

$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    backWithError('Could not create the uploads folder.');
}

// Generate a safe random filename; never use the uploaded name as a server path.
$storedName = bin2hex(random_bytes(16)) . '.mp3';
$absolutePath = $uploadDir . '/' . $storedName;
$relativePath = 'uploads/' . $storedName;

if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
    backWithError('Could not save the uploaded file.');
}

try {
    $stmt = $pdo->prepare('INSERT INTO songs (title, artist, file_path) VALUES (?, ?, ?)');
    $stmt->execute([$title, $artist, $relativePath]);
} catch (Throwable $e) {
    if (is_file($absolutePath)) {
        unlink($absolutePath);
    }
    backWithError('Could not save the song to the database.');
}

header('Location: index.php?message=' . rawurlencode('Song uploaded successfully!'));
exit;
