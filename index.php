<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SC Scraper</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400&family=Syne:wght@400;700;800&display=swap" rel="stylesheet" />
  <style>
    :root {
      --sc-orange:  #ff5500;
      --sc-clair:   #fff;
      --sc-panneau: #111111;
      --sc-bordure: #1f1f1f;
      --sc-attenue: #444444;
      --sc-texte:   #e8e8e8;
    }

    * { box-sizing: border-box; }

    body {
      background-color: var(--sc-clair);
      color: var(--sc-texte);
      font-family: 'Space Mono', monospace;
      min-height: 100vh;
    }

    h1, h2, h3, .syne { font-family: 'Syne', sans-serif; }

    #url-input {
      background: transparent;
      border: none;
      border-bottom: 2px solid var(--sc-attenue);
      color: #755c5c;
      font-family: 'Space Mono', monospace;
      font-size: 0.95rem;
      transition: border-color .25s;
      outline: none;
      width: 100%;
      padding: 0.5rem 0;
    }
    #url-input:focus { border-bottom-color: var(--sc-orange); }
    #url-input::placeholder { color: #555; }

    #launch-btn {
      background: var(--sc-orange);
      color: #fff;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      letter-spacing: .08em;
      border: none;
      cursor: pointer;
      padding: .55rem 1.8rem;
      transition: opacity .2s, transform .1s;
      white-space: nowrap;
    }
    #launch-btn:hover  { opacity: .85; }
    #launch-btn:active { transform: scale(.97); }
    #launch-btn:disabled { opacity: .45; cursor: not-allowed; }

    .spinner {
      width: 20px; height: 20px;
      border: 2px solid #333;
      border-top-color: var(--sc-orange);
      border-radius: 50%;
      animation: rotation .7s linear infinite;
      display: inline-block;
    }
    @keyframes rotation { to { transform: rotate(360deg); } }

    .carte {
      background: var(--sc-panneau);
      border: 1px solid var(--sc-bordure);
      padding: 1.5rem;
      margin-bottom: 1rem;
    }

    .carte-titre {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: .65rem;
      letter-spacing: .2em;
      text-transform: uppercase;
      color: var(--sc-orange);
      margin-bottom: 1rem;
      padding-bottom: .5rem;
      border-bottom: 1px solid var(--sc-bordure);
    }

    .bloc-stat {
      display: flex;
      flex-direction: column;
      gap: .15rem;
    }
    .etiquette-stat {
      font-size: .6rem;
      letter-spacing: .15em;
      text-transform: uppercase;
      color: var(--sc-attenue);
    }
    .valeur-stat {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 1.1rem;
      color: var(--sc-texte);
    }
    .valeur-stat.grande {
      font-size: 1.6rem;
    }

    .ligne-kv {
      display: flex;
      gap: .75rem;
      padding: .45rem 0;
      border-bottom: 1px solid var(--sc-bordure);
      align-items: flex-start;
      font-size: .82rem;
    }
    .ligne-kv:last-child { border-bottom: none; }
    .cle-kv {
      color: var(--sc-attenue);
      min-width: 160px;
      flex-shrink: 0;
      font-size: .75rem;
      padding-top: .05rem;
    }
    .val-kv { color: var(--sc-texte); word-break: break-all; }
    .val-kv a { color: var(--sc-orange); text-decoration: none; }
    .val-kv a:hover { text-decoration: underline; }

    .badge {
      display: inline-block;
      background: #1a1a1a;
      border: 1px solid var(--sc-bordure);
      font-size: .7rem;
      padding: .15rem .5rem;
      color: #aaa;
    }
    .badge.orange { border-color: var(--sc-orange); color: var(--sc-orange); }
    .badge.vert   { border-color: #22c55e; color: #22c55e; }

    #error-box {
      border-left: 3px solid #e53e3e;
      background: #1a0a0a;
      padding: 1rem 1.25rem;
      font-size: .85rem;
      color: #fc8181;
    }

    #artwork-img {
      width: 100%;
      aspect-ratio: 1;
      object-fit: cover;
      display: block;
    }
    #banner-img {
      width: 100%;
      height: 120px;
      object-fit: cover;
      display: block;
    }

    #avatar-img {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--sc-orange);
    }

    .point-verifie {
      display: inline-block;
      width: 8px; height: 8px;
      background: var(--sc-orange);
      border-radius: 50%;
    }

    .fade-in {
      animation: apparition .4s ease forwards;
    }
    @keyframes apparition { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }

    .btn-copier {
      font-size: .65rem;
      padding: .1rem .4rem;
      border: 1px solid var(--sc-attenue);
      background: transparent;
      color: var(--sc-attenue);
      cursor: pointer;
      font-family: 'Space Mono', monospace;
      transition: border-color .2s, color .2s;
    }
    .btn-copier:hover { border-color: var(--sc-orange); color: var(--sc-orange); }

    #barre-progression {
      height: 2px;
      background: var(--sc-orange);
      width: 0%;
      transition: width .3s;
    }

    .grille-sections {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
    }

    /* ── Bouton téléchargement & liste qualités ── */
    .btn-dl {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      background: var(--sc-orange);
      color: #fff;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .8rem;
      letter-spacing: .06em;
      border: none;
      cursor: pointer;
      padding: .55rem 1.4rem;
      transition: opacity .2s, transform .1s;
    }
    .btn-dl:hover  { opacity: .85; }
    .btn-dl:active { transform: scale(.97); }
    .btn-dl:disabled { opacity: .45; cursor: not-allowed; }

    .liste-qualites {
      margin-top: .75rem;
      border: 1px solid var(--sc-bordure);
      background: #0d0d0d;
      display: none;
    }
    .liste-qualites.open { display: block; }

    .quality-row {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: .75rem;
      padding: .65rem 1rem;
      border-bottom: 1px solid var(--sc-bordure);
      font-size: .8rem;
    }
    .quality-row:last-child { border-bottom: none; }
    .quality-row-info {
      display: flex;
      align-items: center;
      gap: .5rem;
      flex: 1;
    }

    .dl-quality-btn {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      background: transparent;
      border: 1px solid var(--sc-orange);
      color: var(--sc-orange);
      font-family: 'Space Mono', monospace;
      font-size: .7rem;
      padding: .25rem .75rem;
      cursor: pointer;
      white-space: nowrap;
      transition: background .15s, color .15s;
    }
    .dl-quality-btn:hover { background: var(--sc-orange); color: #fff; }
    .dl-quality-btn:disabled { opacity: .45; cursor: not-allowed; }

    .dl-quality-btn .btn-spinner {
      width: 12px; height: 12px;
      border: 1.5px solid rgba(255,255,255,.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: rotation .7s linear infinite;
      display: none;
    }
    .dl-quality-btn.loading .btn-spinner { display: inline-block; }
    .dl-quality-btn.loading .btn-label   { display: none; }

    .dl-progress-wrap {
      display: flex;
      align-items: center;
      gap: .5rem;
      margin-top: .5rem;
      width: 100%;
      grid-column: 1 / -1;
    }
    .dl-progress-bar-bg {
      flex: 1;
      height: 3px;
      background: var(--sc-bordure);
      overflow: hidden;
    }
    .dl-progress-bar {
      height: 100%;
      background: var(--sc-orange);
      transition: width .15s ease;
    }
    .dl-progress-label {
      font-size: .6rem;
      color: var(--sc-attenue);
      white-space: nowrap;
      min-width: 80px;
      text-align: right;
    }
  </style>
</head>
<body class="p-6 md:p-10">

  <!-- En-tête -->
  <header class="mb-10">
    <div class="flex items-center gap-3 mb-1">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="var(--sc-orange)">
        <path d="M1.175 12.225c-.056 0-.094.034-.1.087l-.328 2.209.328 2.229c.006.058.044.086.1.086.055 0 .094-.03.1-.086l.373-2.229-.373-2.21c-.006-.054-.045-.086-.1-.086zm1.49-.88c-.07 0-.12.05-.127.12l-.3 3.09.3 3.12c.007.07.057.12.127.12.07 0 .12-.05.127-.12l.34-3.12-.34-3.09c-.007-.07-.057-.12-.127-.12zm1.519-.284c-.085 0-.148.065-.153.152l-.27 3.374.27 3.404c.005.088.068.153.153.153.086 0 .148-.065.153-.153l.308-3.404-.308-3.374c-.005-.087-.067-.152-.153-.152zm1.547.018c-.1 0-.176.078-.18.178l-.24 3.356.24 3.392c.004.1.08.178.18.178.1 0 .176-.078.18-.178l.272-3.392-.272-3.356c-.004-.1-.08-.178-.18-.178zm1.574-.5c-.116 0-.207.093-.21.21l-.21 3.856.21 3.896c.003.117.094.21.21.21.117 0 .207-.093.21-.21l.237-3.896-.237-3.856c-.003-.117-.093-.21-.21-.21zm1.598-.123c-.13 0-.232.105-.234.235l-.18 3.979.18 4.02c.002.13.104.235.234.235.13 0 .232-.105.234-.235l.205-4.02-.205-3.979c-.002-.13-.104-.235-.234-.235zm1.624-.108c-.145 0-.26.118-.262.264l-.15 4.087.15 4.127c.002.146.117.264.262.264.145 0 .26-.118.262-.264l.17-4.127-.17-4.087c-.002-.146-.117-.264-.262-.264zm1.65-.013c-.16 0-.287.13-.288.29l-.12 4.1.12 4.14c.001.16.128.29.288.29.16 0 .287-.13.288-.29l.136-4.14-.136-4.1c-.001-.16-.128-.29-.288-.29zm1.674.016c-.174 0-.313.14-.314.315l-.09 4.084.09 4.124c.001.174.14.315.314.315.174 0 .313-.14.314-.315l.103-4.124-.103-4.084c-.001-.175-.14-.315-.314-.315zm1.698-.08c-.188 0-.337.15-.338.34l-.06 4.164.06 4.2c.001.19.15.34.338.34.188 0 .337-.15.338-.34l.068-4.2-.068-4.164c-.001-.19-.15-.34-.338-.34zm5.765 1.788c-.27 0-.528.05-.768.14-.158-1.822-1.7-3.246-3.578-3.246-.487 0-.954.1-1.368.277-.15.063-.19.127-.192.19v6.376c.002.065.048.116.112.127H22.86c.62 0 1.14-.52 1.14-1.154v-.004c0-1.5-1.21-2.716-2.706-2.716z"/>
      </svg>
      <h1 class="syne text-2xl font-extrabold tracking-tight" style="color:var(--sc-orange)">SC SCRAPER</h1>
    </div>
    <p class="text-xs" style="color:var(--sc-attenue)">Extracteur de métadonnées SoundCloud</p>
  </header>

  <!-- Barre de recherche -->
  <div class="mb-10 max-w-3xl mx-auto">
    <div class="flex gap-3 items-end">
      <div class="flex-1">
        <label class="block text-xs mb-2" style="color:var(--sc-attenue);letter-spacing:.15em;text-transform:uppercase">URL SoundCloud</label>
        <input id="url-input" type="url" placeholder="https://soundcloud.com/artiste/titre" autocomplete="off" spellcheck="false" />
      </div>
      <button id="launch-btn">LANCER</button>
    </div>
    <div class="mt-2" style="height:2px;background:#1a1a1a;">
      <div id="barre-progression"></div>
    </div>
  </div>

  <!-- Statut / erreur -->
  <div id="status-area" class="mb-6 hidden">
    <div id="loading-box" class="hidden flex items-center gap-3 text-sm" style="color:var(--sc-attenue)">
      <span class="spinner"></span>
      <span id="loading-msg">Récupération en cours…</span>
    </div>
    <div id="error-box" class="hidden"></div>
  </div>

  <!-- Résultats -->
  <div id="results" class="hidden fade-in"></div>

  <script>
  // ── Utilitaires ──────────────────────────────────────────────────────────
  const parId   = id => document.getElementById(id);
  const formater = n => (n == null ? '—' : Number(n).toLocaleString('fr-FR'));
  const echapper = s => s == null ? '' : String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

  function ligneKv(cle, valeur, estLien = false, copiable = false) {
    if (valeur == null || valeur === '') return '';
    let affichage = estLien
      ? `<a href="${echapper(valeur)}" target="_blank" rel="noopener">${echapper(valeur)}</a>`
      : `<span>${echapper(String(valeur))}</span>`;
    let boutonCopie = copiable
      ? `<button class="btn-copier" data-copy="${echapper(String(valeur))}">COPIER</button>`
      : '';
    return `<div class="ligne-kv">
      <span class="cle-kv">${echapper(cle)}</span>
      <span class="val-kv flex-1">${affichage}</span>
      ${boutonCopie}
    </div>`;
  }

  // ── Animation de progression ─────────────────────────────────────────────
  let minuteurProgression = null;
  function demarrerProgression() {
    const barre = parId('barre-progression');
    barre.style.width = '0%';
    let valeur = 0;
    minuteurProgression = setInterval(() => {
      valeur = Math.min(valeur + Math.random() * 8, 88);
      barre.style.width = valeur + '%';
    }, 250);
  }
  function terminerProgression(succes = true) {
    clearInterval(minuteurProgression);
    const barre = parId('barre-progression');
    barre.style.background = succes ? 'var(--sc-orange)' : '#e53e3e';
    barre.style.width = '100%';
    setTimeout(() => { barre.style.width = '0%'; barre.style.background = 'var(--sc-orange)'; }, 700);
  }

  // ── États de l'interface ─────────────────────────────────────────────────
  function afficherChargement(msg = 'Récupération en cours…') {
    parId('status-area').classList.remove('hidden');
    parId('loading-box').classList.remove('hidden');
    parId('error-box').classList.add('hidden');
    parId('loading-msg').textContent = msg;
    parId('results').classList.add('hidden');
    parId('launch-btn').disabled = true;
    demarrerProgression();
  }
  function afficherErreur(msg) {
    parId('status-area').classList.remove('hidden');
    parId('loading-box').classList.add('hidden');
    parId('error-box').classList.remove('hidden');
    parId('error-box').textContent = msg;
    parId('results').classList.add('hidden');
    parId('launch-btn').disabled = false;
    terminerProgression(false);
  }
  function masquerStatut() {
    parId('status-area').classList.add('hidden');
    parId('loading-box').classList.add('hidden');
    parId('error-box').classList.add('hidden');
  }

  // ── Requête principale ───────────────────────────────────────────────────
  let _donneesActuelles = null;

  async function lancer() {
    let url = parId('url-input').value.trim();
    if (!url) { afficherErreur('Veuillez entrer une URL SoundCloud.'); return; }

    // Nettoyer l'URL : garder uniquement scheme://host/path
    try {
      const u = new URL(url);
      url = u.origin + u.pathname;
    } catch (_) { /* URL invalide, laisse sc.php gérer l'erreur */ }

    afficherChargement('Récupération en cours…');

    try {
      const reponse  = await fetch('./sc.php?url=' + encodeURIComponent(url));
      const resultat = await reponse.json();

      if (!resultat.success) {
        throw new Error(resultat.error || 'Erreur inconnue du serveur');
      }

      _donneesActuelles = resultat.data;

      terminerProgression(true);
      masquerStatut();
      parId('launch-btn').disabled = false;
      afficherResultats(_donneesActuelles);

    } catch (e) {
      afficherErreur('Erreur : ' + e.message);
    }
  }

  // ── Téléchargement côté client : fetch M3U8 → segments → Blob → <a download>
  async function demarrerTelechargement(btn, urlM3u8, jetonLicence, preset, titreTrack) {
    btn.classList.add('loading');
    btn.disabled = true;

    // Créer ou réutiliser la barre de progression liée à ce bouton
    let enveloppeProg = btn.closest('.quality-row').querySelector('.dl-progress-wrap');
    if (!enveloppeProg) {
      enveloppeProg = document.createElement('div');
      enveloppeProg.className = 'dl-progress-wrap';
      enveloppeProg.innerHTML = `
        <div class="dl-progress-bar-bg">
          <div class="dl-progress-bar" style="width:0%"></div>
        </div>
        <span class="dl-progress-label">0%</span>`;
      btn.closest('.quality-row').appendChild(enveloppeProg);
    }
    const barre    = enveloppeProg.querySelector('.dl-progress-bar');
    const etiquette = enveloppeProg.querySelector('.dl-progress-label');

    function mettreAJourProgression(actuel, total, phase) {
      const pct = total > 0 ? Math.round((actuel / total) * 100) : 0;
      barre.style.width = pct + '%';
      etiquette.textContent = phase === 'init'  ? 'Init…'
                            : phase === 'done'  ? '✓ Enregistré'
                            : phase === 'blob'  ? 'Finalisation…'
                            : `${pct}% · seg ${actuel}/${total}`;
    }

    try {
      // Récupérer la playlist M3U8
      mettreAJourProgression(0, 1, 'init');
      const reponseM3u8 = await fetch(urlM3u8);
      if (!reponseM3u8.ok) throw new Error('Impossible de récupérer la playlist M3U8');
      const texteM3u8 = await reponseM3u8.text();

      // Extraire les URLs des segments (.m4s / .ts)
      const urlBase    = urlM3u8.substring(0, urlM3u8.lastIndexOf('/') + 1);
      const lignes     = texteM3u8.split('\n').map(l => l.trim()).filter(Boolean);
      const ligneInit  = lignes.find(l => l.startsWith('#EXT-X-MAP:URI='));
      const lignesSegs = lignes.filter(l => !l.startsWith('#') && l.length > 0);

      if (lignesSegs.length === 0) throw new Error('Aucun segment trouvé dans la playlist');

      const resoudre = u => u.startsWith('http') ? u : urlBase + u;
      const morceaux = [];
      const total    = lignesSegs.length;

      // Segment init
      if (ligneInit) {
        const urlInit = ligneInit.match(/URI="([^"]+)"/)?.[1];
        if (urlInit) {
          const r = await fetch(resoudre(urlInit));
          if (!r.ok) throw new Error('Échec du segment init');
          morceaux.push(await r.arrayBuffer());
        }
      }

      // Segments audio
      for (let i = 0; i < total; i++) {
        mettreAJourProgression(i + 1, total, 'seg');
        const r = await fetch(resoudre(lignesSegs[i]));
        if (!r.ok) throw new Error(`Échec segment ${i}`);
        morceaux.push(await r.arrayBuffer());
      }

      // Assemblage
      mettreAJourProgression(total, total, 'blob');
      const ext      = preset.includes('opus') ? 'opus' : 'm4a';
      const typeMime = ext === 'opus' ? 'audio/ogg' : 'audio/mp4';
      const blob     = new Blob(morceaux, { type: typeMime });
      const urlBlob  = URL.createObjectURL(blob);
      const lien     = document.createElement('a');
      lien.href      = urlBlob;
      lien.download  = (titreTrack || 'track').replace(/[^a-z0-9 \-_]/gi, '_')
                     + '_' + preset + '.' + ext;
      document.body.appendChild(lien);
      lien.click();
      document.body.removeChild(lien);
      setTimeout(() => URL.revokeObjectURL(urlBlob), 5000);
      mettreAJourProgression(total, total, 'done');

    } catch (e) {
      barre.style.background = '#e53e3e';
      etiquette.textContent = '✗ Erreur';
      alert('Erreur lors du téléchargement : ' + e.message);
    } finally {
      btn.classList.remove('loading');
      btn.disabled = false;
      btn.querySelector('.btn-label').textContent =
        '⬇ TÉLÉCHARGER ' + preset.toUpperCase();
    }
  }

  // ── Rendu des résultats ──────────────────────────────────────────────────
  function afficherResultats(d) {
    const zone = parId('results');
    zone.classList.remove('hidden');
    zone.classList.add('fade-in');

    let html = '';

    // ══ Artiste ═════════════════════════════════════════════════════════════
    if (d.artist) {
      const artiste = d.artist;
      const banniere = artiste.banner_url
        ? `<img id="banner-img" src="${echapper(artiste.banner_url)}" alt="bannière" class="mb-4" />`
        : '';
      const avatar = artiste.avatar_url
        ? `<img id="avatar-img" src="${echapper(artiste.avatar_url)}" alt="avatar" />`
        : `<div style="width:56px;height:56px;border-radius:50%;background:#222;border:2px solid var(--sc-orange)"></div>`;
      const verifie = artiste.verified
        ? `<span class="point-verifie" title="Vérifié"></span> <span style="color:var(--sc-orange);font-size:.7rem">VÉRIFIÉ</span>`
        : '';

      let htmlBadges = '';
      if (artiste.badges) {
        const listeBadges = [];
        if (artiste.badges.pro)              listeBadges.push('PRO');
        if (artiste.badges.pro_unlimited)    listeBadges.push('PRO UNLIMITED');
        if (artiste.badges.creator_mid_tier) listeBadges.push('CREATOR');
        if (artiste.badges.verified)         listeBadges.push('VERIFIED');
        if (listeBadges.length) {
          htmlBadges = `<div class="flex gap-2 mt-2 flex-wrap">` +
            listeBadges.map(b => `<span class="badge orange">${echapper(b)}</span>`).join('') +
            `</div>`;
        }
      }

      html += `<div class="carte">
        ${banniere}
        <div class="carte-titre">🎤 Artiste</div>
        <div class="flex gap-4 items-center mb-5">
          ${avatar}
          <div>
            <div class="syne font-extrabold text-lg">${echapper(artiste.full_name || artiste.username)}</div>
            <div class="text-xs" style="color:var(--sc-attenue)">@${echapper(artiste.username)}</div>
            <div class="flex gap-2 mt-1 items-center">${verifie}</div>
            ${htmlBadges}
          </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5 p-4" style="background:#0d0d0d;border:1px solid var(--sc-bordure)">
          <div class="bloc-stat">
            <span class="etiquette-stat">Abonnés</span>
            <span class="valeur-stat grande" style="color:var(--sc-orange)">${formater(artiste.followers_count)}</span>
          </div>
          <div class="bloc-stat">
            <span class="etiquette-stat">Abonnements</span>
            <span class="valeur-stat">${formater(artiste.followings_count)}</span>
          </div>
          <div class="bloc-stat">
            <span class="etiquette-stat">Titres</span>
            <span class="valeur-stat">${formater(artiste.track_count)}</span>
          </div>
          <div class="bloc-stat">
            <span class="etiquette-stat">Playlists</span>
            <span class="valeur-stat">${formater(artiste.playlist_count)}</span>
          </div>
          <div class="bloc-stat">
            <span class="etiquette-stat">Likes</span>
            <span class="valeur-stat">${formater(artiste.likes_count)}</span>
          </div>
          <div class="bloc-stat">
            <span class="etiquette-stat">Reposts</span>
            <span class="valeur-stat">${formater(artiste.reposts_count)}</span>
          </div>
          <div class="bloc-stat">
            <span class="etiquette-stat">Commentaires</span>
            <span class="valeur-stat">${formater(artiste.comments_count)}</span>
          </div>
          <div class="bloc-stat">
            <span class="etiquette-stat">Abonnement</span>
            <span class="valeur-stat text-sm">${echapper(artiste.subscription || '—')}</span>
          </div>
        </div>

        ${ligneKv('Profil URL', artiste.permalink_url, true)}
        ${ligneKv('Permalink', artiste.permalink)}
        ${ligneKv('Ville', artiste.city)}
        ${ligneKv('Pays', artiste.country_code)}
        ${ligneKv('Description', artiste.description)}
      </div>`;
    }

    // ══ Track ════════════════════════════════════════════════════════════════
    if (d.track) {
      const piste = d.track;
      const pochette = piste.artwork_url
        ? `<img id="artwork-img" src="${echapper(piste.artwork_url.replace('-large','-t500x500'))}" alt="pochette" />`
        : `<div style="aspect-ratio:1;background:#161616;display:flex;align-items:center;justify-content:center;color:#333;font-size:2rem">♫</div>`;

      html += `<div class="carte">
        <div class="carte-titre">🎵 Titre</div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <div class="md:col-span-1">
            ${pochette}
          </div>
          <div class="md:col-span-2 flex flex-col justify-between gap-4">
            <div>
              <div class="syne font-extrabold text-xl leading-tight mb-1">${echapper(piste.title)}</div>
              <div class="text-xs mb-3" style="color:var(--sc-attenue)">${echapper(piste.genre || 'Genre inconnu')}</div>
              ${piste.description ? `<p class="text-xs leading-relaxed" style="color:#888">${echapper(piste.description).substring(0,400)}${piste.description.length > 400 ? '…' : ''}</p>` : ''}
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="bloc-stat">
                <span class="etiquette-stat">Durée</span>
                <span class="valeur-stat" style="color:var(--sc-orange)">${echapper(piste.duration_human)}</span>
              </div>
              <div class="bloc-stat">
                <span class="etiquette-stat">Écoutes</span>
                <span class="valeur-stat">${formater(piste.playback_count)}</span>
              </div>
              <div class="bloc-stat">
                <span class="etiquette-stat">Likes</span>
                <span class="valeur-stat">${formater(piste.likes_count)}</span>
              </div>
              <div class="bloc-stat">
                <span class="etiquette-stat">Reposts</span>
                <span class="valeur-stat">${formater(piste.reposts_count)}</span>
              </div>
              <div class="bloc-stat">
                <span class="etiquette-stat">Commentaires</span>
                <span class="valeur-stat">${formater(piste.comment_count)}</span>
              </div>
              <div class="bloc-stat">
                <span class="etiquette-stat">Téléchargements</span>
                <span class="valeur-stat">${formater(piste.download_count)}</span>
              </div>
            </div>
          </div>
        </div>

        ${ligneKv('Permalink URL', piste.permalink_url, true)}
        ${ligneKv('Date de sortie', piste.release_date)}
        ${ligneKv('Label', piste.label_name)}
        ${ligneKv('Durée (ms)', piste.duration_ms)}
      </div>`;

      // ── Métadonnées éditeur ───────────────────────────────────────────────
      if (piste.publisher) {
        const editeur = piste.publisher;
        html += `<div class="carte">
          <div class="carte-titre">📀 Métadonnées Éditeur</div>
          ${ligneKv('Artiste', editeur.artist)}
          ${ligneKv('Album', editeur.album_title)}
          ${ligneKv('Titre release', editeur.release_title)}
          ${ligneKv('ISRC', editeur.isrc, false, true)}
          ${ligneKv('UPC / EAN', editeur.upc_or_ean, false, true)}
          ${ligneKv('Ligne P', editeur.p_line)}
          ${ligneKv('Ligne C', editeur.c_line)}
        </div>`;
      }

      // ── Flux audio → Bouton téléchargement ───────────────────────────────
      if (piste.audio_streams && piste.audio_streams.length && d.api_client) {
        const idClient  = d.api_client.id;
        const authPiste = piste.track_authorization;
        const titre     = piste.title || 'track';

        html += `<div class="carte" id="audio-card">
          <div class="carte-titre">🔊 Audio</div>
          <button class="btn-dl" id="toggle-dl-btn">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
            TÉLÉCHARGER
          </button>
          <div class="liste-qualites" id="quality-list">
            <div id="quality-loading" class="text-xs p-4" style="color:var(--sc-attenue)">
              <span class="spinner" style="width:12px;height:12px;border-width:1.5px;display:inline-block;vertical-align:middle;margin-right:6px"></span>
              Vérification des qualités disponibles…
            </div>
          </div>
        </div>`;

        // Stocker les infos pour la vérification différée
        window._fluxEnAttente = {
          flux:      piste.audio_streams,
          idClient,
          authPiste,
          titre,
        };
      }
    }

    // ══ Repli ════════════════════════════════════════════════════════════════
    if (!d.artist && !d.track && !d.api_client) {
      html += `<div class="carte">
        <div class="carte-titre">⚠️ Données brutes</div>
        <p class="text-sm" style="color:var(--sc-attenue)">Aucune donnée structurée trouvée.</p>
      </div>`;
    }

    zone.innerHTML = html;

    // ── Boutons copier ────────────────────────────────────────────────────
    zone.querySelectorAll('.btn-copier').forEach(btn => {
      btn.addEventListener('click', () => {
        navigator.clipboard.writeText(btn.dataset.copy || '').then(() => {
          const original = btn.textContent;
          btn.textContent = 'COPIÉ !';
          btn.style.borderColor = 'var(--sc-orange)';
          btn.style.color = 'var(--sc-orange)';
          setTimeout(() => {
            btn.textContent = original;
            btn.style.borderColor = '';
            btn.style.color = '';
          }, 1500);
        });
      });
    });

    // ── Basculer la liste de qualités (vérification au 1er clic) ─────────
    const btnBascule  = document.getElementById('toggle-dl-btn');
    const listeQual   = document.getElementById('quality-list');
    let   fluxVerifie = false;

    if (btnBascule && listeQual) {
      btnBascule.addEventListener('click', async () => {
        listeQual.classList.toggle('open');
        const estOuvert = listeQual.classList.contains('open');
        btnBascule.innerHTML = estOuvert
          ? '▲ MASQUER'
          : '<svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg> TÉLÉCHARGER';

        if (estOuvert && !fluxVerifie && window._fluxEnAttente) {
          fluxVerifie = true;
          const { flux, idClient, authPiste, titre } = window._fluxEnAttente;

          // Préparer le tableau index+url pour le proxy
          const donnees = flux.map((s, i) => ({ index: i, url: s.url }));

          try {
            const reponse = await fetch(
              './sc.php?action=check_streams'
              + '&client_id='           + encodeURIComponent(idClient)
              + '&track_authorization=' + encodeURIComponent(authPiste)
              + '&streams='             + encodeURIComponent(JSON.stringify(donnees))
            );
            const resultat = await reponse.json();

            const chargementEl = document.getElementById('quality-loading');
            if (chargementEl) chargementEl.remove();

            if (!resultat.success || !resultat.valid.length) {
              listeQual.innerHTML = '<div class="text-xs p-4" style="color:#fc8181">Aucune qualité disponible.</div>';
              return;
            }

            resultat.valid.forEach(v => {
              const s      = flux[v.index];
              const preset = s.preset   || '?';
              const mime   = s.mime     || '?';
              const proto  = s.protocol || '?';
              const qual   = s.quality  || '?';
              const estChiffre = proto.includes('encrypted');
              const badgeChiffre = estChiffre
                ? `<span class="badge" style="border-color:#e53e3e;color:#e53e3e;font-size:.6rem">🔒 CHIFFRÉ</span>`
                : `<span class="badge vert" style="font-size:.6rem">🔓 OUVERT</span>`;
              const badgeQual = `<span class="badge ${qual === 'hq' ? 'orange' : ''}" style="font-size:.6rem">${qual.toUpperCase()}</span>`;

              const ligne = document.createElement('div');
              ligne.className = 'quality-row';
              ligne.innerHTML = `
                <div class="quality-row-info">
                  <div>
                    <div class="text-xs font-bold" style="color:var(--sc-texte)">${echapper(preset)}</div>
                    <div class="text-xs" style="color:var(--sc-attenue)">${echapper(mime)} · ${echapper(proto).toUpperCase()}</div>
                  </div>
                  <div class="flex gap-2">${badgeQual}${badgeChiffre}</div>
                </div>
                <button class="dl-quality-btn" data-preset="${echapper(preset)}" data-title="${echapper(titre)}">
                  <span class="btn-spinner"></span>
                  <span class="btn-label">
                    <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor" style="display:inline;vertical-align:middle;margin-right:3px"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                    TÉLÉCHARGER ${echapper(preset).toUpperCase()}
                  </span>
                </button>`;

              // Attacher le handler avec l'URL M3U8 déjà résolue
              ligne.querySelector('.dl-quality-btn').addEventListener('click', function() {
                demarrerTelechargement(this, v.url, v.licenseAuthToken, preset, titre);
              });

              listeQual.appendChild(ligne);
            });

          } catch (e) {
            listeQual.innerHTML = `<div class="text-xs p-4" style="color:#fc8181">Erreur : ${echapper(e.message)}</div>`;
          }
        }
      });
    }
  }

  // ── Événements ────────────────────────────────────────────────────────────
  parId('launch-btn').addEventListener('click', lancer);
  parId('url-input').addEventListener('keydown', e => { if (e.key === 'Enter') lancer(); });
  </script>
</body>
</html>