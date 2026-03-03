<!DOCTYPE html>
<html>
<head>
    <title>Laravel Storage Link Fix - cPanel</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border-left: 5px solid #28a745; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border-left: 5px solid #dc3545; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; border-left: 5px solid #ffc107; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; border-left: 5px solid #17a2b8; margin: 10px 0; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .file-list { background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto; }
        .file-list ul { margin: 0; padding-left: 20px; }
        .security-notice { background: #dc3545; color: white; padding: 20px; border-radius: 5px; margin-top: 20px; }
        .btn-delete { background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn-delete:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="container">
<?php
/**
 * Fix Storage Link for cPanel
 * 
 * This script creates the storage symlink for Laravel on cPanel
 * Upload this file to your project root and run it once via browser
 * then DELETE it immediately for security
 * 
 * Access: https://yourdomain.com/fix-storage-link.php
 */

echo "<h2>🔧 Laravel Storage Link Fix for cPanel</h2>";

$publicStorage = __DIR__ . '/public/storage';
$storagePublic = __DIR__ . '/storage/app/public';

echo "<div class='info'>";
echo "<strong>Project Root:</strong> " . __DIR__ . "<br>";
echo "<strong>Target Storage Path:</strong> storage/app/public<br>";
echo "<strong>Public Link Path:</strong> public/storage";
echo "</div>";

// Check if storage/app/public exists
if (!file_exists($storagePublic)) {
    echo "<div class='warning'>";
    echo "⚠️ Warning: storage/app/public directory does not exist!<br>";
    echo "Creating storage/app/public directory...";
    echo "</div>";
    mkdir($storagePublic, 0755, true);
    echo "<div class='success'>✅ Created storage/app/public</div>";
}

// Backup existing directory if it's not a symlink
$backupCreated = false;
if (is_link($publicStorage)) {
    echo "<div class='info'>Found existing symbolic link. Removing...</div>";
    unlink($publicStorage);
} elseif (file_exists($publicStorage)) {
    echo "<div class='warning'>";
    echo "⚠️ Warning: public/storage is a real DIRECTORY, not a symlink!<br>";
    echo "This will be backed up before creating the proper symlink.";
    echo "</div>";
    
    if (is_dir($publicStorage)) {
        // Create backup
        $backupDir = __DIR__ . '/public/storage_backup_' . date('Y-m-d_His');
        rename($publicStorage, $backupDir);
        $backupCreated = true;
        echo "<div class='success'>✅ Backed up to: " . basename($backupDir) . "</div>";
    }
}

// Create symbolic link
echo "<div class='info'>Creating symbolic link...</div>";

$success = false;
// Try relative path first (recommended for cPanel)
if (symlink('../storage/app/public', $publicStorage)) {
    $success = true;
    $method = "relative path symlink";
} elseif (symlink($storagePublic, $publicStorage)) {
    $success = true;
    $method = "absolute path symlink";
}

if ($success) {
    echo "<div class='success'>";
    echo "<h3>✅ SUCCESS! Storage Link Created</h3>";
    echo "Method used: $method<br>";
    echo "Link target: " . readlink($publicStorage);
    echo "</div>";
    
    // Show storage contents
    echo "<h3>📁 Files in storage/app/public:</h3>";
    if (is_dir($storagePublic)) {
        $items = scandir($storagePublic);
        $folders = array_filter($items, function($item) use ($storagePublic) {
            return $item != '.' && $item != '..' && is_dir($storagePublic . '/' . $item);
        });
        
        if (count($folders) > 0) {
            echo "<div class='file-list'><ul>";
            foreach ($folders as $folder) {
                $fileCount = count(glob($storagePublic . '/' . $folder . '/*'));
                echo "<li><strong>$folder/</strong> ($fileCount files)</li>";
            }
            echo "</ul></div>";
        } else {
            echo "<div class='warning'>No subdirectories found in storage/app/public</div>";
        }
    }
    
    echo "<div class='info'>";
    echo "<h3>📌 Your files are now accessible at:</h3>";
    echo "<ul>";
    echo "<li>Student pictures: <code>/storage/student_pictures/...</code></li>";
    echo "<li>School logos: <code>/storage/school_logos/...</code></li>";
    echo "<li>QR codes: <code>/storage/qr_codes/...</code></li>";
    echo "<li>Generated files: <code>/storage/generated/...</code></li>";
    echo "</ul>";
    echo "</div>";
    
    if ($backupCreated) {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Backup Notice</h3>";
        echo "Your old public/storage directory was backed up.<br>";
        echo "Check if files in the backup are needed, then you can safely delete it.";
        echo "</div>";
    }
    
} else {
    echo "<div class='error'>";
    echo "<h3>❌ Failed to Create Symbolic Link</h3>";
    echo "<p><strong>Manual Fix Required:</strong></p>";
    echo "<p>Connect to cPanel Terminal (or SSH) and run these commands:</p>";
    echo "</div>";
    echo "<pre>";
    echo "cd " . __DIR__ . "\n";
    echo "rm -rf public/storage\n";
    echo "ln -s ../storage/app/public public/storage\n";
    echo "chmod -R 755 storage\n";
    echo "chmod -R 755 bootstrap/cache";
    echo "</pre>";
    echo "<div class='info'>";
    echo "<p>Or try Laravel's Artisan command:</p>";
    echo "<pre>php artisan storage:link</pre>";
    echo "</div>";
}

// Security warning
echo "<div class='security-notice'>";
echo "<h3>🚨 CRITICAL SECURITY WARNING! 🚨</h3>";
echo "<p style='font-size: 18px; font-weight: bold;'>DELETE THIS FILE NOW!</p>";
echo "<p>This file (fix-storage-link.php) is a security risk if left on your server.</p>";
echo "<p>Delete it immediately using cPanel File Manager or FTP.</p>";
echo "</div>";

?>
    </div>
</body>
</html> 
