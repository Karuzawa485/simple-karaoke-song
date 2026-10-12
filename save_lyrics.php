<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'song_id', FILTER_VALIDATE_INT);
$lyrics = trim($_POST['lyrics'] ?? '');

if (!$id || strlen($lyrics) > 500000) {
    header('Location: index.php?error=' . rawurlencode('Invalid song or lyrics are too long.'));
    exit;
}

// Accept blank lyrics, or lines such as [00:05.00]First line
$lines = preg_split('/\r\n|\r|\n/', $lyrics);
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '') continue;
    if (!preg_match('/^\[(\d{2,}):([0-5]\d)(?:\.(\d{1,3}))?\].+$/u', $line)) {
        header('Location: index.php?error=' . rawurlencode('Lyrics format is not valid. Use [00:05.00]Text for each line.'));
        exit;
    }
}

$stmt = $pdo->prepare('UPDATE songs SET lyrics = ? WHERE id = ?');
$stmt->execute([$lyrics, $id]);

header('Location: index.php?message=' . rawurlencode('Timed lyrics saved.'));
exit;
