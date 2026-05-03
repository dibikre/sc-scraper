<?php

// Désactiver la mise en tampon de sortie
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', 'off');

// Augmenter les limites pour les fichiers longs
set_time_limit(300);
ini_set('memory_limit', '256M');

// ─── Paramètres ──────────────────────────────────────────────────────────────

$urlPlaylist = isset($_GET['playlist']) ? trim($_GET['playlist']) : '';
$nomFichier    = isset($_GET['filename'])  ? trim($_GET['filename'])  : 'soundcloud_track';
$jeton       = isset($_GET['token'])     ? trim($_GET['token'])     : '';

// Nettoyage du nom de fichier
$nomFichier = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '_', $nomFichier);
$nomFichier = trim(preg_replace('/_+/', '_', $nomFichier), '_');
$nomFichier = $nomFichier ?: 'soundcloud_track';

// ─── Validation ──────────────────────────────────────────────────────────────

if (empty($urlPlaylist)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => "Paramètre 'playlist' manquant."]);
    exit;
}

if (!filter_var($urlPlaylist, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => "URL de playlist invalide."]);
    exit;
}

// ─── Vérification ffmpeg ─────────────────────────────────────────────────────

$cheminFfmpeg = trouverFfmpeg();

if (!$cheminFfmpeg) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => "ffmpeg n'est pas installé ou introuvable sur ce serveur."]);
    exit;
}

// ─── Détection du format de sortie ──────────────────────────────────────────

// Les flux SoundCloud sont AAC dans des conteneurs MP4/HLS
// On détecte si le preset contient des infos sur le format
$extension    = 'mp3'; // format de sortie : MP3 pour compatibilité maximale
$typeMime   = 'audio/mpeg';
$codecAudio  = 'libmp3lame';
$debitBinaire = '192k';

// Si le nom de fichier contient "opus", on utilise opus
if (stripos($nomFichier, 'opus') !== false) {
    $extension    = 'opus';
    $typeMime   = 'audio/ogg';
    $codecAudio  = 'libopus';
    $debitBinaire = '160k';
}

// Si le nom de fichier contient "aac", on copie directement le stream
if (stripos($nomFichier, 'aac') !== false) {
    $extension    = 'm4a';
    $typeMime   = 'audio/mp4';
    $codecAudio  = 'copy'; // copie directe sans re-encodage = plus rapide
    $debitBinaire = null;
}

$nomFichierSortie = $nomFichier . '.' . $extension;

// ─── Fichier temporaire de sortie ────────────────────────────────────────────

$fichierTemp = tempnam(sys_get_temp_dir(), 'sc_dl_') . '.' . $extension;

// ─── Construction de la commande ffmpeg ─────────────────────────────────────

/*
 * On passe l'URL M3U8 directement à ffmpeg qui gère :
 * - Le téléchargement des segments
 * - La reconstruction de l'audio
 * - L'éventuel déchiffrement CENC si les clés sont embarquées dans la playlist
 *
 * Pour le SAMPLE-AES SoundCloud utilise des clés inline dans le M3U8 (data:// URI),
 * ffmpeg les supporte nativement depuis la version 4.x.
 */

$commande = construireCommandeFfmpeg($cheminFfmpeg, $urlPlaylist, $fichierTemp, $codecAudio, $debitBinaire, $jeton);

// ─── Exécution ────────────────────────────────────────────────────────────────

$descripteurs = [
    0 => ['pipe', 'r'],   // stdin
    1 => ['pipe', 'w'],   // stdout
    2 => ['pipe', 'w'],   // stderr
];

$processus = proc_open($commande, $descripteurs, $tubes);

if (!is_resource($processus)) {
    @unlink($fichierTemp);
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => "Impossible de lancer ffmpeg."]);
    exit;
}

fclose($tubes[0]);
$erreurStd = stream_get_contents($tubes[2]);
fclose($tubes[1]);
fclose($tubes[2]);
$codeRetour = proc_close($processus);

// ─── Vérification du résultat ────────────────────────────────────────────────

if ($codeRetour !== 0 || !file_exists($fichierTemp) || filesize($fichierTemp) === 0) {
    @unlink($fichierTemp);
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error'   => "ffmpeg a échoué (code $codeRetour).",
        'details' => substr($erreurStd, -2000) // dernières lignes du log
    ]);
    exit;
}

$tailleFichier = filesize($fichierTemp);

// ─── Envoi du fichier au navigateur ──────────────────────────────────────────

header('Content-Type: ' . $typeMime);
header('Content-Disposition: attachment; filename="' . addslashes($nomFichierSortie) . '"');
header('Content-Length: ' . $tailleFichier);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

// Nettoyer le buffer de sortie existant
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Lecture par blocs pour les gros fichiers
$pointeur = fopen($fichierTemp, 'rb');
if ($pointeur) {
    while (!feof($pointeur) && !connection_aborted()) {
        echo fread($pointeur, 8192);
        flush();
    }
    fclose($pointeur);
}

// Nettoyage
@unlink($fichierTemp);
exit;

// ─── Fonctions ───────────────────────────────────────────────────────────────

/**
 * Cherche ffmpeg dans les emplacements habituels.
 */
function trouverFfmpeg(): ?string
{
    $candidats = [
        'ffmpeg',                          // dans le PATH
        '/usr/bin/ffmpeg',
        '/usr/local/bin/ffmpeg',
        '/opt/homebrew/bin/ffmpeg',        // macOS Homebrew ARM
        '/opt/local/bin/ffmpeg',           // macOS MacPorts
        '/snap/bin/ffmpeg',                // Ubuntu snap
        'C:\\ffmpeg\\bin\\ffmpeg.exe',     // Windows
    ];

    foreach ($candidats as $path) {
        $sortie = [];
        $code = 0;
        @exec(escapeshellcmd($path) . ' -version 2>&1', $sortie, $code);
        if ($code === 0) {
            return $path;
        }
    }

    // Essai via `which` sur Unix
    $cheminWhich = trim(@shell_exec('which ffmpeg 2>/dev/null'));
    if ($cheminWhich && file_exists($cheminWhich)) {
        return $cheminWhich;
    }

    return null;
}

/**
 * Construit la commande ffmpeg pour assembler le HLS.
 *
 * @param string      $ffmpeg      Chemin vers l'exécutable
 * @param string      $playlist    URL M3U8 signée
 * @param string      $sorties      Chemin du fichier de sortie temporaire
 * @param string      $codecAudio       Codec audio ffmpeg (libmp3lame | libopus | copy)
 * @param string|null $debitBinaire     Bitrate cible (ex: "192k"), null si copy
 * @param string      $jeton       licenseAuthToken (optionnel, non utilisé directement
 *                                 ici car ffmpeg lit les clés inline du M3U8)
 */
function construireCommandeFfmpeg(
    string $ffmpeg,
    string $playlist,
    string $sorties,
    string $codecAudio,
    ?string $debitBinaire,
    string $jeton
): string {
    $parties = [
        escapeshellcmd($ffmpeg),
        '-y',                                              // écraser sans demander
        '-loglevel', 'error',                              // log minimal
        '-allowed_extensions', 'ALL',                      // autorise les data: URIs dans le M3U8
        '-protocol_whitelist', 'file,http,https,tcp,tls,crypto,data', // protocoles autorisés
        '-i', escapeshellarg($playlist),                   // entrée : URL M3U8
        '-vn',                                             // pas de vidéo
        '-acodec', escapeshellarg($codecAudio),                 // codec audio
    ];

    if ($debitBinaire !== null && $codecAudio !== 'copy') {
        $parties[] = '-ab';
        $parties[] = escapeshellarg($debitBinaire);
    }

    // Métadonnées minimales
    $parties[] = '-map_metadata';
    $parties[] = '0';

    $parties[] = escapeshellarg($sorties);

    return implode(' ', $parties);
}