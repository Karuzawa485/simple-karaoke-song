<?php
require __DIR__ . '/config.php';

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare(
        'SELECT id, title, artist, file_path, lyrics FROM songs
         WHERE title LIKE ? OR artist LIKE ? ORDER BY title'
    );
    $term = '%' . $search . '%';
    $stmt->execute([$term, $term]);
} else {
    $stmt = $pdo->query('SELECT id, title, artist, file_path, lyrics FROM songs ORDER BY title');
}
$songs = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zumi's Spotify</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php"><img class="brand-logo" src="uploads/zumi.jpg" alt="">Zumi's Spotify</a>
    <span class="tagline">My room. My voice.</span>
</header>

<main class="layout">
    <section class="hero">
        <div>
            <p class="eyebrow">MY PLAYLISTS</p>
            <p class="subheading">Upload your tracks, queue your favorites, and sing along.</p>
        </div>
    </section>

    <?php if (!empty($_GET['message'])): ?>
        <div class="notice"><?= h($_GET['message']) ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
        <div class="error"><?= h($_GET['error']) ?></div>
    <?php endif; ?>

    <section class="player-panel">
        <div class="player-heading">
            <div>
                <p class="eyebrow">NOW PLAYING</p>
                <h2 id="current-title">Choose a song</h2>
                <p id="current-artist" class="muted">Your next favorite starts here.</p>
            </div>
            <div class="playing-mark" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
        </div>

        <audio id="audio-player" controls preload="metadata"></audio>
        <div class="player-actions">
            <button id="prev-btn" class="button button-quiet" type="button">↶ Previous</button>
            <button id="next-btn" class="button button-primary" type="button">Next song ↗</button>
            <span id="play-status" class="muted">Nothing queued</span>
        </div>
    </section>

    <div class="content-grid">
        <section class="panel library-panel">
            <div class="section-heading">
                <div><p class="eyebrow">MY COLLECTION</p><h2>Song library</h2></div>
                <span class="count"><?= count($songs) ?> song<?= count($songs) === 1 ? '' : 's' ?></span>
            </div>

            <form class="search-form" method="get" action="index.php">
                <span aria-hidden="true">⌕</span>
                <input type="search" name="q" placeholder="Search title or artist..." value="<?= h($search) ?>">
                <button class="button button-quiet" type="submit">Search</button>
                <?php if ($search !== ''): ?><a class="clear-link" href="index.php">Clear</a><?php endif; ?>
            </form>

            <?php if (!$songs): ?>
                <div class="empty-state">
                    <div class="empty-icon">♫</div>
                    <h3><?= $search !== '' ? 'No matching songs' : 'Your library is waiting' ?></h3>
                    <p><?= $search !== '' ? 'Try a different title or artist.' : 'Upload an MP3 using the form to add your first track.' ?></p>
                </div>
            <?php else: ?>
                <div class="song-list">
                <?php foreach ($songs as $song): ?>
                    <article class="song-row">
                        <div class="song-art">♫</div>
                        <div class="song-info">
                            <strong><?= h($song['title']) ?></strong>
                            <span><?= h($song['artist']) ?></span>
                        </div>
                        <button
                            class="icon-button play-song"
                            type="button"
                            title="Play <?= h($song['title']) ?>"
                            data-id="<?= (int)$song['id'] ?>"
                            data-title="<?= h($song['title']) ?>"
                            data-artist="<?= h($song['artist']) ?>"
                            data-src="<?= h($song['file_path']) ?>"
                            data-lyrics="<?= h($song['lyrics'] ?? '') ?>">▶</button>
                        <button class="button button-quiet queue-song" type="button"
                            data-id="<?= (int)$song['id'] ?>"
                            data-title="<?= h($song['title']) ?>"
                            data-artist="<?= h($song['artist']) ?>"
                            data-src="<?= h($song['file_path']) ?>"
                            data-lyrics="<?= h($song['lyrics'] ?? '') ?>">+ Queue</button>
                        <details class="lyrics-editor">
                            <summary>Lyrics</summary>
                            <form method="post" action="save_lyrics.php" class="lyrics-form">
                                <input type="hidden" name="song_id" value="<?= (int)$song['id'] ?>">
                                <label for="lyrics-<?= (int)$song['id'] ?>">Timed lyrics (LRC format)</label>
                                <textarea id="lyrics-<?= (int)$song['id'] ?>" name="lyrics" rows="5"
                                  placeholder="[00:05.00]First line&#10;[00:09.50]Second line"><?= h($song['lyrics'] ?? '') ?></textarea>
                                <p class="help">One line per timestamp. Example: [00:05.00]Hello there</p>
                                <button class="button button-primary" type="submit">Save lyrics</button>
                            </form>
                        </details>
                    </article>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <aside class="side-column">
            <section class="panel upload-panel">
                <p class="eyebrow">ADD SONGS</p>
                <h2>Upload a song</h2>
                <p class="muted small">MP3 audio files only. Add the song details, then save timed lyrics from the library.</p>
                <form method="post" action="upload.php" enctype="multipart/form-data" class="upload-form">
                    <label for="title">Song title</label>
                    <input id="title" name="title" maxlength="150" required placeholder="e.g. My Favorite Song">
                    <label for="artist">Artist</label>
                    <input id="artist" name="artist" maxlength="150" required placeholder="e.g. Sample Artist">
                    <label for="mp3">MP3 file</label>
                    <input id="mp3" name="mp3" type="file" accept=".mp3,audio/mpeg" required>
                    <button class="button button-primary full-width" type="submit">↑ Upload MP3</button>
                </form>
            </section>

            <section class="panel queue-panel">
                <div class="section-heading">
                    <div><p class="eyebrow">UP NEXT</p><h2>Song queue</h2></div>
                    <span class="count" id="queue-count">0</span>
                </div>
                <p class="muted small">Your queue is saved in this browser.</p>
                <ol id="queue-list" class="queue-list"><li class="queue-empty">Add a song from the library.</li></ol>
                <button id="clear-queue" class="button button-quiet full-width" type="button">Clear queue</button>
            </section>
        </aside>
    </div>

    <footer>SingSpace Karaoke · Built by Albert .</footer>
</main>

<script src="app.js"></script>
</body>
</html>
