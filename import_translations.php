<?php
/**
 * Import Translations Script
 * This script imports translations from a JSON file to the database
 * Usage: Run this on your LOCAL machine to import translations
 */

// Database configuration - UPDATE THESE FOR YOUR LOCAL
$host = 'localhost';
$dbname = 'stocky';
$username = 'root';
$password = '';

// Check if file was uploaded
if (!isset($_FILES['translation_file'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Import Translations</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .form-group { margin-bottom: 20px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="file"] { width: 100%; padding: 10px; }
            button { background: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; }
            button:hover { background: #45a049; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 4px; }
        </style>
    </head>
    <body>
        <h1>Import Translations</h1>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Select Translation JSON File:</label>
                <input type="file" name="translation_file" accept=".json" required>
            </div>
            <button type="submit">Import Translations</button>
        </form>
        <hr>
        <h3>Instructions:</h3>
        <ol>
            <li>Export translations from server using <code>export_translations.php</code></li>
            <li>Download the JSON file</li>
            <li>Upload it here to import to local database</li>
        </ol>
    </body>
    </html>
    <?php
    exit;
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read uploaded file
    $jsonContent = file_get_contents($_FILES['translation_file']['tmp_name']);
    $translations = json_decode($jsonContent, true);
    
    if (!$translations) {
        throw new Exception("Invalid JSON file");
    }
    
    // Prepare insert statement
    $stmt = $pdo->prepare("
        INSERT INTO `translations` (`locale`, `key`, `value`) 
        VALUES (:locale, :key, :value)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
    ");
    
    // Import translations
    $imported = 0;
    $pdo->beginTransaction();
    
    foreach ($translations as $translation) {
        $stmt->execute([
            'locale' => $translation['locale'],
            'key' => $translation['key'],
            'value' => $translation['value']
        ]);
        $imported++;
    }
    
    $pdo->commit();
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Import Success</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
            a { color: #007bff; text-decoration: none; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class="success">
            <h2>✅ Import Successful!</h2>
            <p><strong><?php echo $imported; ?></strong> translations imported successfully.</p>
        </div>
        <p><a href="import_translations.php">← Import Another File</a></p>
        <hr>
        <h3>Next Steps:</h3>
        <ol>
            <li>Clear Laravel cache: <code>php artisan cache:clear</code></li>
            <li>Test your application</li>
        </ol>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Import Error</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 4px; }
            a { color: #007bff; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>❌ Import Failed</h2>
            <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
        </div>
        <p><a href="import_translations.php">← Try Again</a></p>
    </body>
    </html>
    <?php
}
