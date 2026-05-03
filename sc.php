<?php

header('Content-Type: application/json; charset=utf-8');

// ─── Dispatch ────────────────────────────────────────────────────────────────

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

if ($action === 'resolve_stream') {
    resoudreFlux();
    exit;
}

if ($action === 'check_streams') {
    verifierFlux();
    exit;
}

// ─── Mode 1 : scrape ─────────────────────────────────────────────────────────

$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if (empty($url)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => "Paramètre 'url' manquant. Exemple : ?url=https://soundcloud.com/artiste/titre"
    ]);
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => "URL invalide : $url"]);
    exit;
}

// Nettoyer l'URL : garder uniquement scheme://host/path (sans query string ni fragment)
$urlParsee = parse_url($url);
$url = ($urlParsee['scheme'] ?? 'https') . '://'
     . ($urlParsee['host']   ?? '')
     . ($urlParsee['path']   ?? '');

// ─── Téléchargement du code source ──────────────────────────────────────────

$context = stream_context_create([
    'http' => [
        'method'          => 'GET',
        'timeout'         => 30,
        'follow_location' => true,
        'max_redirects'   => 5,
        'header'          => implode("\r\n", [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                . 'AppleWebKit/537.36 (KHTML, like Gecko) '
                . 'Chrome/124.0.0.0 Safari/537.36',
            'Accept-Language: fr-FR,fr;q=0.9,en;q=0.8',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ]),
    ],
]);

$html = @file_get_contents($url, false, $context);

if ($html === false) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error'   => "Impossible de récupérer la page. Vérifiez l'URL ou la connexion."
    ]);
    exit;
}

// ─── Extraction de window.__sc_hydration ─────────────────────────────────────

if (!preg_match('/window\.__sc_hydration\s*=\s*(\[.*?\]);/s', $html, $matches)) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error'   => "Données JSON introuvables dans le code source de la page."
    ]);
    exit;
}

$hydratation = json_decode($matches[1], true);

if ($hydratation === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => "JSON invalide : " . json_last_error_msg()]);
    exit;
}

// ─── Extraction ──────────────────────────────────────────────────────────────

$donnees = extraireDonnees($hydratation);

if ($donnees === null) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => "Aucune donnée importante n'a pu être extraite."]);
    exit;
}

// ─── Sauvegarde silencieuse ──────────────────────────────────────────────────

$nomFichier   = preg_replace('/[^a-z0-9_-]/', '_', strtolower(parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH)));
$nomFichier   = trim(preg_replace('/_+/', '_', $nomFichier), '_');
$nomFichier   = substr($nomFichier, 0, 80) . '_' . date('Ymd_His') . '.json';
$dossierSauvegarde = __DIR__ . DIRECTORY_SEPARATOR . 'MP3';
if (!is_dir($dossierSauvegarde)) @mkdir($dossierSauvegarde, 0755, true);
@file_put_contents($dossierSauvegarde . DIRECTORY_SEPARATOR . $nomFichier,
    json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

// ─── Réponse ─────────────────────────────────────────────────────────────────

echo json_encode([
    'success' => true,
    'url'     => $url,
    'data'    => $donnees
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

exit;


function verifierFlux(): void
{
    $streams   = isset($_GET['streams'])          ? trim($_GET['streams'])          : '';
    $idClient  = isset($_GET['client_id'])         ? trim($_GET['client_id'])         : '';
    $tokenPiste = isset($_GET['track_authorization'])? trim($_GET['track_authorization']): '';

    if (empty($streams) || empty($idClient)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Paramètres manquants.']);
        return;
    }

    $listeUrls = json_decode($streams, true);
    if (!is_array($listeUrls)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'streams doit être un tableau JSON.']);
        return;
    }

    $valides = [];

    foreach ($listeUrls as $element) {
        $urlFlux = $element['url'] ?? '';
        if (empty($urlFlux)) continue;

        $urlApi = $urlFlux
                . '?client_id='           . urlencode($idClient)
                . '&track_authorization=' . urlencode($tokenPiste);

        $contexte = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'timeout'         => 8,
                'follow_location' => true,
                'max_redirects'   => 3,
                'ignore_errors'   => true,
                'header'          => implode("\r\n", [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                        . 'AppleWebKit/537.36 (KHTML, like Gecko) '
                        . 'Chrome/124.0.0.0 Safari/537.36',
                    'Accept: application/json, */*',
                    'Origin: https://soundcloud.com',
                    'Referer: https://soundcloud.com/',
                ]),
            ],
        ]);

        $reponse = @file_get_contents($urlApi, false, $contexte);
        $codeHttp = 0;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $entete) {
                if (preg_match('#HTTP/\S+\s+(\d+)#', $entete, $correspondance)) {
                    $codeHttp = (int)$correspondance[1];
                }
            }
        }

        if ($reponse === false || $codeHttp === 404) continue;

        $json = json_decode($reponse, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url'])) continue;

        $valides[] = [
            'index'            => $element['index'],
            'url'              => $json['url'],
            'licenseAuthToken' => $json['licenseAuthToken'] ?? null,
        ];
    }

    echo json_encode(['success' => true, 'valid' => $valides]);
}


function resoudreFlux(): void
{
    $urlFlux  = isset($_GET['stream_url'])         ? trim($_GET['stream_url'])         : '';
    $idClient   = isset($_GET['client_id'])           ? trim($_GET['client_id'])           : '';
    $tokenPiste  = isset($_GET['track_authorization']) ? trim($_GET['track_authorization']) : '';

    if (empty($urlFlux) || empty($idClient)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Paramètres stream_url et client_id requis."]);
        return;
    }

    // Construction de l'URL API SoundCloud
    $urlApi = $urlFlux
            . '?client_id='          . urlencode($idClient)
            . '&track_authorization='. urlencode($tokenPiste);

    // Appel depuis le serveur PHP (pas de restriction CORS ici)
    $contexte = stream_context_create([
        'http' => [
            'method'          => 'GET',
            'timeout'         => 15,
            'follow_location' => true,
            'max_redirects'   => 3,
            'header'          => implode("\r\n", [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                    . 'AppleWebKit/537.36 (KHTML, like Gecko) '
                    . 'Chrome/124.0.0.0 Safari/537.36',
                'Accept: application/json, */*',
                'Origin: https://soundcloud.com',
                'Referer: https://soundcloud.com/',
            ]),
        ],
        'ssl' => [
            'verify_peer'      => true,
            'verify_peer_name' => true,
        ],
    ]);

    $reponse = @file_get_contents($urlApi, false, $contexte);

    if ($reponse === false) {
        // Tenter de récupérer le code HTTP
        $codeHttp = 0;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $entete) {
                if (preg_match('#HTTP/\S+\s+(\d+)#', $entete, $correspondance)) {
                    $codeHttp = (int)$correspondance[1];
                }
            }
        }
        http_response_code(502);
        echo json_encode([
            'success'   => false,
            'error'     => "L'API SoundCloud n'a pas répondu (HTTP $codeHttp).",
            'api_url'   => $urlApi,
        ]);
        return;
    }

    $json = json_decode($reponse, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($json['url'])) {
        http_response_code(502);
        echo json_encode([
            'success' => false,
            'error'   => "Réponse inattendue de l'API SoundCloud.",
            'raw'     => substr($reponse, 0, 500),
        ]);
        return;
    }

    echo json_encode([
        'success'           => true,
        'url'               => $json['url'],
        'licenseAuthToken'  => $json['licenseAuthToken'] ?? null,
    ]);
}

// ═════════════════════════════════════════════════════════════════════════════
// Extraction des données
// ═════════════════════════════════════════════════════════════════════════════

function extraireDonnees(?array $hydratation): ?array
{
    if (empty($hydratation)) return null;

    $blocs = [];
    foreach ($hydratation as $entree) {
        if (isset($entree['hydratable'])) {
            $blocs[$entree['hydratable']] = $entree['data'] ?? null;
        }
    }

    $resultat = [];

    // ── API Client ────────────────────────────────────────────────────────
    if (isset($blocs['apiClient'])) {
        $resultat['api_client'] = ['id' => $blocs['apiClient']['id'] ?? null];
    }

    // ── Artiste ───────────────────────────────────────────────────────────
    if (isset($blocs['user'])) {
        $artiste = $blocs['user'];
        $resultat['artist'] = [
            'id'               => $artiste['id']               ?? null,
            'username'         => $artiste['username']          ?? null,
            'full_name'        => $artiste['full_name']         ?? null,
            'permalink'        => $artiste['permalink']         ?? null,
            'permalink_url'    => $artiste['permalink_url']     ?? null,
            'description'      => $artiste['description']       ?? null,
            'city'             => $artiste['city']              ?? null,
            'country_code'     => $artiste['country_code']      ?? null,
            'followers_count'  => $artiste['followers_count']   ?? null,
            'followings_count' => $artiste['followings_count']  ?? null,
            'track_count'      => $artiste['track_count']       ?? null,
            'playlist_count'   => $artiste['playlist_count']    ?? null,
            'likes_count'      => $artiste['likes_count']       ?? null,
            'reposts_count'    => $artiste['reposts_count']     ?? null,
            'comments_count'   => $artiste['comments_count']    ?? null,
            'verified'         => $artiste['verified']          ?? null,
            'badges'           => $artiste['badges']            ?? null,
            'avatar_url'       => $artiste['avatar_url']        ?? null,
            'subscription'     => $artiste['creator_subscription']['product']['id'] ?? null,
            'last_modified'    => $artiste['last_modified']     ?? null,
            'urn'              => $artiste['urn']               ?? null,
            'station_urn'      => $artiste['station_urn']       ?? null,
        ];
        if (!empty($u['visuals']['visuals'][0]['visual_url'])) {
            $resultat['artist']['banner_url'] = $artiste['visuals']['visuals'][0]['visual_url'];
        }
    }

    // ── Track ─────────────────────────────────────────────────────────────
    if (isset($blocs['sound'])) {
        $piste = $blocs['sound'];
        $resultat['track'] = [
            'id'                  => $piste['id']                  ?? null,
            'title'               => $piste['title']               ?? null,
            'permalink_url'       => $piste['permalink_url']       ?? null,
            'genre'               => $piste['genre']               ?? null,
            'description'         => $piste['description']         ?? null,
            'duration_ms'         => $piste['duration']            ?? null,
            'duration_human'      => formaterDuree($piste['duration'] ?? 0),
            'created_at'          => $piste['created_at']          ?? null,
            'release_date'        => $piste['release_date']        ?? null,
            'label_name'          => $piste['label_name']          ?? null,
            'monetization'        => $piste['monetization_model']  ?? null,
            'playback_count'      => $piste['playback_count']      ?? null,
            'likes_count'         => $piste['likes_count']         ?? null,
            'reposts_count'       => $piste['reposts_count']       ?? null,
            'comment_count'       => $piste['comment_count']       ?? null,
            'download_count'      => $piste['download_count']      ?? null,
            'artwork_url'         => $piste['artwork_url']         ?? null,
            'waveform_url'        => $piste['waveform_url']        ?? null,
            'urn'                 => $piste['urn']                 ?? null,
            'user_id'             => $piste['user_id']             ?? null,
            'track_authorization' => $piste['track_authorization'] ?? null,
        ];

        if (!empty($piste['publisher_metadata'])) {
            $meta = $piste['publisher_metadata'];
            $resultat['track']['publisher'] = [
                'artist'        => $meta['artist']             ?? null,
                'album_title'   => $meta['album_title']        ?? null,
                'release_title' => $meta['release_title']      ?? null,
                'isrc'          => $meta['isrc']               ?? null,
                'upc_or_ean'    => $meta['upc_or_ean']         ?? null,
                'p_line'        => $meta['p_line_for_display'] ?? null,
                'c_line'        => $meta['c_line_for_display'] ?? null,
            ];
        }

        if (!empty($piste['media']['transcodings'])) {
            $resultat['track']['audio_streams'] = [];
            foreach ($piste['media']['transcodings'] as $transcodage) {
                $resultat['track']['audio_streams'][] = [
                    'url'      => $transcodage['url']                 ?? null,
                    'preset'   => $transcodage['preset']              ?? null,
                    'protocol' => $transcodage['format']['protocol']  ?? null,
                    'mime'     => $transcodage['format']['mime_type'] ?? null,
                    'quality'  => $transcodage['quality']             ?? null,
                ];
            }
        }
    }

    return (!empty($resultat)) ? $resultat : null;
}

function formaterDuree(int $ms): string
{
    $secondes = intdiv($ms, 1000);
    $min      = intdiv($secondes, 60);
    $sec      = $secondes % 60;
    return sprintf('%d:%02d', $min, $sec);
}