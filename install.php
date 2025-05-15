<?php
/**
 * XFile Manager Installer
 * 
 * This script helps install XFile Manager in multiple directories.
 * Run this from your XAMPP htdocs directory to install the file manager
 * in your selected directories.
 */

// Configuration
$config = [
    'files_to_copy' => [
        'index.php',
        '.htaccess',
        'xfile_manager.php'
    ],
    'install_message' => 'XFile Manager has been successfully installed in the selected directories!',
    'error_message' => 'There was an error installing XFile Manager. Please check the permissions and try again.',
    'skip_existing' => true  // Skip directories that already have index.php
];

// Security check - only run in local environment
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('This installer can only be run locally for security reasons.');
}

// Function to copy files to a directory
function copyFilesToDirectory($source_dir, $target_dir, $files, $skip_existing) {
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $result = true;
    
    // Check if we should skip this directory
    if ($skip_existing && file_exists($target_dir . '/index.php')) {
        return [
            'success' => true,
            'skipped' => true,
            'message' => 'Skipped - index.php already exists'
        ];
    }
    
    foreach ($files as $file) {
        if (file_exists($source_dir . '/' . $file)) {
            if (!copy($source_dir . '/' . $file, $target_dir . '/' . $file)) {
                $result = false;
                break;
            }
        }
    }
    
    return [
        'success' => $result,
        'skipped' => false,
        'message' => $result ? 'Installed successfully' : 'Failed to copy files'
    ];
}

// Process form submission
$status = [];
$current_dir = dirname(__FILE__);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $selected_dirs = isset($_POST['directories']) ? $_POST['directories'] : [];
    
    foreach ($selected_dirs as $dir) {
        $target_dir = rtrim($dir, '/');
        $result = copyFilesToDirectory($current_dir, $target_dir, $config['files_to_copy'], $config['skip_existing']);
        $status[$dir] = $result;
    }
}

// Scan for subdirectories
function scanDirectories($base_dir, $relative_path = '') {
    $directories = [];
    $items = scandir($base_dir . $relative_path);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $base_dir . $relative_path . '/' . $item;
        if (is_dir($path)) {
            $dir_path = $relative_path . '/' . $item;
            $directories[] = $dir_path;
            
            // Only go one level deep to avoid too many directories
            if (substr_count($relative_path, '/') < 1) {
                $sub_dirs = scanDirectories($base_dir, $dir_path);
                $directories = array_merge($directories, $sub_dirs);
            }
        }
    }
    
    return $directories;
}

$base_dir = realpath($_SERVER['DOCUMENT_ROOT']);
$subdirectories = scanDirectories($base_dir);
sort($subdirectories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XFile Manager Installer</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        h1, h2 {
            color: #00509a;
        }
        .container {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .logo {
            width: 40px;
            height: 40px;
            background-color: #00509a;
            border-radius: 8px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .directory-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
        }
        .directory-item {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .directory-item:last-child {
            border-bottom: none;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        button {
            background-color: #00509a;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        button:hover {
            background-color: #003b73;
        }
        .status {
            margin-top: 20px;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
        .skipped {
            color: #ffc107;
        }
        .select-actions {
            margin-bottom: 10px;
        }
        .select-actions button {
            padding: 5px 10px;
            margin-right: 5px;
            background-color: #6c757d;
        }
        .install-counter {
            margin-left: 10px;
            font-weight: normal;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">📁</div>
            <h1>XFile Manager Installer</h1>
        </div>
        
        <p>This installer will help you deploy XFile Manager to multiple directories in your XAMPP installation.</p>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="directories">
                    Select directories to install XFile Manager:
                    <span class="install-counter" id="counter">0 selected</span>
                </label>
                
                <div class="select-actions">
                    <button type="button" id="selectAll">Select All</button>
                    <button type="button" id="deselectAll">Deselect All</button>
                </div>
                
                <div class="directory-list">
                    <?php foreach ($subdirectories as $dir): ?>
                        <div class="directory-item">
                            <input type="checkbox" name="directories[]" id="dir_<?= md5($dir) ?>" 
                                   value="<?= htmlspecialchars($base_dir . $dir) ?>"
                                   <?= isset($status[$base_dir . $dir]) ? 'checked' : '' ?>>
                            <label for="dir_<?= md5($dir) ?>"><?= htmlspecialchars($dir) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" name="install">Install XFile Manager</button>
        </form>
        
        <?php if (!empty($status)): ?>
            <div class="status">
                <h2>Installation Status</h2>
                
                <?php
                $success_count = 0;
                $error_count = 0;
                $skipped_count = 0;
                
                foreach ($status as $dir => $result) {
                    if ($result['skipped']) {
                        $skipped_count++;
                    } elseif ($result['success']) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
                ?>
                
                <p>
                    <strong>Summary:</strong> 
                    <span class="success"><?= $success_count ?> installed</span>, 
                    <span class="skipped"><?= $skipped_count ?> skipped</span>, 
                    <span class="error"><?= $error_count ?> failed</span>
                </p>
                
                <div class="directory-list">
                    <?php foreach ($status as $dir => $result): ?>
                        <div class="directory-item">
                            <strong><?= htmlspecialchars($dir) ?></strong>: 
                            
                            <?php if ($result['skipped']): ?>
                                <span class="skipped">Skipped - index.php already exists</span>
                            <?php elseif ($result['success']): ?>
                                <span class="success">Installed successfully</span>
                            <?php else: ?>
                                <span class="error">Failed to install</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[name="directories[]"]');
            const counter = document.getElementById('counter');
            const selectAllBtn = document.getElementById('selectAll');
            const deselectAllBtn = document.getElementById('deselectAll');
            
            function updateCounter() {
                const selectedCount = document.querySelectorAll('input[name="directories[]"]:checked').length;
                counter.textContent = selectedCount + ' selected';
            }
            
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', updateCounter);
            });
            
            selectAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = true;
                });
                updateCounter();
            });
            
            deselectAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = false;
                });
                updateCounter();
            });
            
            updateCounter();
        });
    </script>
</body>
</html> 