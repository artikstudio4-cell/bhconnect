<?php
/**
 * Télécharger un fichier audio gratuit pour les notifications
 */
header('Content-Type: application/json');

$outputFile = __DIR__ . '/notification.mp3';
$response = ['success' => false, 'message' => ''];

// Utiliser un son gratuit depuis Freesound ou Zapsplat
// Pour cet exemple, on va créer un simple son avec PHP pure (WAV -> MP3 avec conversion)

// Essayer plusieurs sources
$sources = [
    // Zapsplat API (gratuit, pas d'authentification nécessaire)
    'https://www.zapsplat.com/api/audio-files/?category_id=6&search=notification&limit=1',
];

// Télécharger depuis Zapsplat
foreach ($sources as $apiUrl) {
    try {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0'
        ]);
        
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode === 200) {
            $data = json_decode($result, true);
            
            if (isset($data['results'][0]['file_url'])) {
                $fileUrl = $data['results'][0]['file_url'];
                
                // Télécharger le fichier
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $fileUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_USERAGENT => 'Mozilla/5.0'
                ]);
                
                $audioData = curl_exec($curl);
                curl_close($curl);
                
                if (!empty($audioData) && file_put_contents($outputFile, $audioData)) {
                    $response['success'] = true;
                    $size = round(filesize($outputFile) / 1024, 2);
                    $response['message'] = "✅ Son téléchargé avec succès ($size KB)";
                    echo json_encode($response);
                    exit;
                }
            }
        }
    } catch (Exception $e) {
        // Continuer avec la source suivante
        continue;
    }
}

// Fallback: Créer un simple fichier audio
// Génération d'un WAV simple convertible
$response['message'] = 'Impossible de télécharger depuis les sources en ligne. Veuillez utiliser "Générer le son" ou "Uploader un fichier".';

echo json_encode($response);
?>
