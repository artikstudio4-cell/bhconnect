<?php
/**
 * Test de connexion à InfinityFree
 * 
 * Vérifiez que la BD InfinityFree est accessible depuis Railway
 * Accédez à: https://your-app.railway.app/test_infinityfree_connection.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Connexion InfinityFree</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            border-bottom: 3px solid #2196F3;
            padding-bottom: 10px;
        }
        
        h2 {
            color: #555;
            margin-top: 25px;
        }
        
        .config {
            background: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #2196F3;
            margin: 15px 0;
            font-family: monospace;
        }
        
        .success {
            background: #e8f5e9;
            border-left-color: #4caf50;
            color: #2e7d32;
        }
        
        .error {
            background: #ffebee;
            border-left-color: #f44336;
            color: #c62828;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .table th {
            background: #2196F3;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        .table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .table tr:hover {
            background: #f5f5f5;
        }
        
        .status-ok {
            color: #4caf50;
            font-weight: bold;
        }
        
        .status-error {
            color: #f44336;
            font-weight: bold;
        }
        
        .status-warning {
            color: #ff9800;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🧪 Test de Connexion InfinityFree</h1>
    
    <h2>1. Configuration Détectée</h2>
    <div class="config success">
        <strong>DB_HOST:</strong> <?= getenv('DB_HOST') ?: 'NOT SET' ?><br>
        <strong>DB_PORT:</strong> <?= getenv('DB_PORT') ?: '3306' ?><br>
        <strong>DB_NAME:</strong> <?= getenv('DB_NAME') ?: 'NOT SET' ?><br>
        <strong>DB_USER:</strong> <?= getenv('DB_USER') ?: 'NOT SET' ?><br>
        <strong>DB_PASS:</strong> <?= getenv('DB_PASS') ? '***' . substr(getenv('DB_PASS'), -3) : 'NOT SET' ?><br>
        <strong>ENVIRONMENT:</strong> <?= getenv('ENVIRONMENT') ?: 'development' ?>
    </div>
    
    <h2>2. Test de Connexion</h2>
    <?php
    
    try {
        echo '<div class="config success">';
        echo '<strong class="status-ok">✅ Tentative de connexion...</strong><br><br>';
        
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        echo '<strong class="status-ok">✅ CONNEXION RÉUSSIE!</strong><br>';
        echo 'Connecté à: ' . getenv('DB_HOST') . '/' . getenv('DB_NAME') . '<br>';
        echo '</div>';
        
        // ========== VÉRIFICATION DES TABLES ==========
        echo '<h2>3. Vérification des Données</h2>';
        
        echo '<table class="table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Table</th>';
        echo '<th>Enregistrements</th>';
        echo '<th>Status</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $tables = [
            'utilisateurs' => 'SELECT COUNT(*) as count FROM utilisateurs',
            'clients' => 'SELECT COUNT(*) as count FROM clients',
            'agents' => 'SELECT COUNT(*) as count FROM agents',
            'dossiers' => 'SELECT COUNT(*) as count FROM dossiers',
            'rendez_vous' => 'SELECT COUNT(*) as count FROM rendez_vous',
            'factures' => 'SELECT COUNT(*) as count FROM factures',
            'documents' => 'SELECT COUNT(*) as count FROM documents',
            'messages' => 'SELECT COUNT(*) as count FROM messages',
            'notifications' => 'SELECT COUNT(*) as count FROM notifications',
            'quiz_participations' => 'SELECT COUNT(*) as count FROM quiz_participations',
        ];
        
        $totalRecords = 0;
        $successCount = 0;
        
        foreach ($tables as $table => $query) {
            try {
                $stmt = $conn->query($query);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $count = $result['count'] ?? 0;
                $totalRecords += $count;
                $successCount++;
                
                echo '<tr>';
                echo '<td><strong>' . $table . '</strong></td>';
                echo '<td>' . $count . '</td>';
                echo '<td><span class="status-ok">✅ OK</span></td>';
                echo '</tr>';
            } catch (Exception $e) {
                echo '<tr>';
                echo '<td><strong>' . $table . '</strong></td>';
                echo '<td>-</td>';
                echo '<td><span class="status-error">❌ Erreur</span><br>';
                echo '<small>' . htmlspecialchars($e->getMessage()) . '</small></td>';
                echo '</tr>';
            }
        }
        
        echo '</tbody>';
        echo '</table>';
        
        echo '<h2>4. Résumé</h2>';
        echo '<div class="config success">';
        echo '<span class="status-ok">✅ CONFIGURATION OK!</span><br>';
        echo 'Tables accessibles: ' . $successCount . '/' . count($tables) . '<br>';
        echo 'Total enregistrements: ' . $totalRecords . '<br>';
        echo '<br>';
        echo '<strong>Votre application peut se connecter à InfinityFree depuis Rails ✅</strong>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="config error">';
        echo '<span class="status-error">❌ ERREUR DE CONNEXION</span><br><br>';
        echo '<strong>Message d\'erreur:</strong><br>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<br>';
        echo '<strong>Vérifications à effectuer:</strong>';
        echo '<ul>';
        echo '<li>✓ DB_HOST = sql309.infinityfree.com</li>';
        echo '<li>✓ DB_NAME = if0_40862714_cabinet_immigration</li>';
        echo '<li>✓ DB_USER = if0_40862714</li>';
        echo '<li>✓ DB_PASS = koWEQ4akLhQ (ou votre password)</li>';
        echo '<li>✓ MySQL accepte les connexions distantes (vérifier dans phpMyAdmin InfinityFree)</li>';
        echo '<li>✓ Tester avec MySQLWorkbench en premier</li>';
        echo '</ul>';
        echo '<br>';
        echo '<strong>Solutions:</strong>';
        echo '<ul>';
        echo '<li>1. Vérifier credentials dans .env (local) ou Variables (Railway)</li>';
        echo '<li>2. Tester manuellement: mysql -h sql309.infinityfree.com -u if0_40862714 -p</li>';
        echo '<li>3. Vérifier que InfinityFree autorise connexions distantes</li>';
        echo '<li>4. Contacter support InfinityFree si problème persiste</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    ?>
    
    <div class="footer">
        <p>🔍 <strong>Ce fichier est un test diagnostic.</strong> À supprimer avant de mettre en production.</p>
        <p>Généré: <?= date('Y-m-d H:i:s') ?></p>
        <p>Environnement: <?= getenv('ENVIRONMENT') ?: 'development' ?></p>
    </div>
</div>

</body>
</html>
