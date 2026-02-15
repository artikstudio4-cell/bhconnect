<?php
/**
 * Manifest PWA dynamique
 * Génère le manifest.json avec les chemins corrects
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/manifest+json');

$baseUrl = rtrim(APP_URL . BASE_PATH, '/');

$manifest = [
    'name' => APP_NAME,
    'short_name' => 'BH CONNECT',
    'description' => 'Application de gestion BH CONNECT',
    'start_url' => $baseUrl . '/',
    'display' => 'standalone',
    'background_color' => '#0d6efd',
    'theme_color' => '#0d6efd',
    'orientation' => 'portrait-primary',
    'scope' => $baseUrl . '/',
    'icons' => [
        [
            'src' => $baseUrl . '/icons/icon-72x72.png',
            'sizes' => '72x72',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $baseUrl . '/icons/icon-96x96.png',
            'sizes' => '96x96',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $baseUrl . '/icons/icon-128x128.png',
            'sizes' => '128x128',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $baseUrl . '/icons/icon-144x144.png',
            'sizes' => '144x144',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $baseUrl . '/icons/icon-152x152.png',
            'sizes' => '152x152',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $baseUrl . '/icons/icon-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $baseUrl . '/icons/icon-384x384.png',
            'sizes' => '384x384',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $baseUrl . '/icons/icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ]
    ],
    'categories' => ['business', 'productivity'],
    'shortcuts' => [
        [
            'name' => 'Dashboard',
            'short_name' => 'Dashboard',
            'description' => 'Accéder au tableau de bord',
            'url' => $baseUrl . '/dashboard.php',
            'icons' => [
                [
                    'src' => $baseUrl . '/icons/icon-96x96.png',
                    'sizes' => '96x96'
                ]
            ]
        ],
        [
            'name' => 'Dossiers',
            'short_name' => 'Dossiers',
            'description' => 'Gérer les dossiers',
            'url' => $baseUrl . '/dossiers.php',
            'icons' => [
                [
                    'src' => $baseUrl . '/icons/icon-96x96.png',
                    'sizes' => '96x96'
                ]
            ]
        ],
        [
            'name' => 'Clients',
            'short_name' => 'Clients',
            'description' => 'Gérer les clients',
            'url' => $baseUrl . '/clients.php',
            'icons' => [
                [
                    'src' => $baseUrl . '/icons/icon-96x96.png',
                    'sizes' => '96x96'
                ]
            ]
        ]
    ]
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);









