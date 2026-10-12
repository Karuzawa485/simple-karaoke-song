const audio = document.getElementById('audio-player');
const currentTitle = document.getElementById('current-title');
const currentArtist = document.getElementById('current-artist');
const lyricsLines = document.getElementById('lyrics-lines');
const lyricsScreen = document.getElementById('lyrics-screen');
const queueList = document.getElementById('queue-list');
const queueCount = document.getElementById('queue-count');
const playStatus = document.getElementById('play-status');

let queue = [];
let currentSong = null;
let parsedLyrics = [];

try {
    const saved = JSON.parse(localStorage.getItem('singspaceQueue') || '[]');
    if (Array.isArray(saved)) queue = saved;
} catch (_) {
    queue = [];
}

function songFromButton(button) {
    return {
        id: Number(button.dataset.id),
        title: button.dataset.title || 'Untitled',
        artist: button.dataset.artist || 'Unknown artist',
        src: button.dataset.src,
        lyrics: button.dataset.lyrics || ''
    };
}

function saveQueue() {
    localStorage.setItem('singspaceQueue', JSON.stringify(queue));
    renderQueue();
}

function renderQueue() {
    queueCount.textContent = queue.length;
    queueList.replaceChildren();

    if (!queue.length) {
        const li = document.createElement('li');
        li.className = 'queue-empty';
        li.textContent = 'Add a song from the library.';
        queueList.appendChild(li);
        return;
    }

    queue.forEach((song, index) => {
        const li = document.createElement('li');
        li.className = 'queue-item';

        const text = document.createElement('div');
        text.className = 'queue-item-text';
        const title = document.createElement('strong');
        title.textContent = song.title;
        const artist = document.createElement('span');
        artist.textContent = song.artist;
        text.append(title, artist);

        const controls = document.createElement('div');
        controls.className = 'queue-controls';

        const play = document.createElement('button');
        play.className = 'tiny-button';
        play.type = 'button';
        play.textContent = '▶';
        play.title = 'Play this song';
        play.addEventListener('click', () => {
            queue.splice(index, 1);
            playSong(song);
            saveQueue();
        });

        const remove = document.createElement('button');
        remove.className = 'tiny-button remove-button';
        remove.type = 'button';
        remove.textContent = '×';
        remove.title = 'Remove from queue';
        remove.addEventListener('click', () => {
            queue.splice(index, 1);
            saveQueue();
        });

        controls.append(play, remove);
        li.append(text, controls);
        queueList.appendChild(li);
    });
}

function parseLrc(text) {
    const result = [];
    const lines = (text || '').split(/\r?\n/);
    const pattern = /^\[(\d+):([0-5]\d)(?:\.(\d{1,3}))?\](.*)$/;

    for (const line of lines) {
        const match = line.match(pattern);
        if (!match) continue;
        const fraction = match[3] || '0';
        const milliseconds = Number(fraction.padEnd(3, '0').slice(0, 3));
        const seconds = Number(match[1]) * 60 + Number(match[2]) + milliseconds / 1000;
        const textLine = match[4].trim();
        if (textLine) result.push({ time: seconds, text: textLine });
    }

    return result.sort((a, b) => a.time - b.time);
}

function showLyrics(song) {
    parsedLyrics = parseLrc(song.lyrics);
    lyricsLines.replaceChildren();

    if (!parsedLyrics.length) {
        const hint = document.createElement('p');
        hint.className = 'lyrics-hint';
        hint.textContent = 'No timed lyrics yet. Open “Lyrics” beside this song to add LRC timestamps.';
        lyricsLines.appendChild(hint);
        return;
    }

    parsedLyrics.forEach((line, index) => {
        const element = document.createElement('p');
        element.className = 'lyric-line';
        element.dataset.index = index;
        element.textContent = line.text;
        lyricsLines.appendChild(element);
    });
}

function syncLyrics() {
    if (!parsedLyrics.length) return;
    const time = audio.currentTime;
    let activeIndex = -1;

    for (let i = 0; i < parsedLyrics.length; i++) {
        if (time >= parsedLyrics[i].time) activeIndex = i;
        else break;
    }

    const elements = lyricsLines.querySelectorAll('.lyric-line');
    elements.forEach((element, index) => {
        element.classList.toggle('active', index === activeIndex);
        element.classList.toggle('past', activeIndex >= 0 && index < activeIndex);
    });

    const active = lyricsLines.querySelector('.lyric-line.active');
    if (active) active.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

function playSong(song) {
    if (!song || !song.src) return;
    currentSong = song;
    currentTitle.textContent = song.title;
    currentArtist.textContent = song.artist;
    audio.src = song.src;
    audio.load();
    showLyrics(song);
    playStatus.textContent = 'Loading song…';
    audio.play().then(() => {
        playStatus.textContent = 'Now singing';
    }).catch(() => {
        playStatus.textContent = 'Press play to start audio';
    });
}

function playNext() {
    if (queue.length) {
        const next = queue.shift();
        saveQueue();
        playSong(next);
    } else {
        playStatus.textContent = 'Queue finished';
    }
}

document.querySelectorAll('.play-song').forEach(button => {
    button.addEventListener('click', () => playSong(songFromButton(button)));
});

document.querySelectorAll('.queue-song').forEach(button => {
    button.addEventListener('click', () => {
        const song = songFromButton(button);
        queue.push(song);
        saveQueue();
        playStatus.textContent = `${song.title} added to queue`;
    });
});

document.getElementById('next-btn').addEventListener('click', playNext);
document.getElementById('prev-btn').addEventListener('click', () => {
    if (currentSong) {
        audio.currentTime = 0;
        audio.play().catch(() => {});
    }
});
document.getElementById('clear-queue').addEventListener('click', () => {
    queue = [];
    saveQueue();
});

audio.addEventListener('timeupdate', syncLyrics);
audio.addEventListener('ended', playNext);

renderQueue();
