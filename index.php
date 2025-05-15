<?php
/**
 * XFile Manager - Modern File Manager for XAMPP
 * A modern, professional file manager to replace the default Apache directory listing
 */

// Security settings
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Configuration
$config = [
    'hide_files' => ['.', '..', '.htaccess', '.git', '.gitignore'],
    'ignored_extensions' => [],
    'date_format' => 'M d, Y h:i A',
    'theme' => [
        'primary_color' => '#00509a',
        'secondary_color' => '#484A72',
        'text_color' => '#333333',
        'background_color' => '#f8f9fa',
        'card_background' => '#ffffff'
    ],
    'view_mode' => isset($_GET['view']) ? $_GET['view'] : 'grid', // grid or list
    'index_files' => ['index.php', 'index.html', 'index.htm'], // Files to navigate to instead of downloading
];

/**
 * Directory handler class
 */
class DirectoryManager {
    private $config;
    private $current_dir;
    private $base_dir;
    private $relative_path;
    private $items = [];
    private $breadcrumbs = [];

    public function __construct($config) {
        $this->config = $config;
        $this->initialize();
    }

    /**
     * Initialize directory parsing
     */
    private function initialize() {
        // Get base directory (server document root)
        $this->base_dir = $_SERVER['DOCUMENT_ROOT'];
        
        // Detect requested path from URL (default to current directory)
        $requested_path = isset($_GET['path']) ? $_GET['path'] : '';
        
        // Security: Clean and validate the path (prevent directory traversal)
        $this->relative_path = $this->validatePath($requested_path);
        
        // Set current directory
        $this->current_dir = $this->base_dir . DIRECTORY_SEPARATOR . $this->relative_path;
        
        // Security: Make sure we're still within document root
        if (!$this->isWithinDocumentRoot($this->current_dir)) {
            $this->relative_path = '';
            $this->current_dir = $this->base_dir;
        }
        
        // Generate breadcrumbs
        $this->generateBreadcrumbs();
        
        // Scan directory
        $this->scanDirectory();
    }
    
    /**
     * Clean and validate a path to prevent directory traversal
     */
    private function validatePath($path) {
        // Remove any null bytes, backward slashes, and multiple forward slashes
        $path = str_replace(['\\', '../', '..\\', './'], '/', $path);
        $path = preg_replace('#/+#', '/', $path);
        $path = trim($path, '/');
        
        return $path;
    }
    
    /**
     * Ensure the path is within document root (security measure)
     */
    private function isWithinDocumentRoot($path) {
        $real_doc_root = realpath($this->base_dir);
        $real_path = realpath($path);
        
        if ($real_path === false) {
            return false;
        }
        
        return strpos($real_path, $real_doc_root) === 0;
    }
    
    /**
     * Generate breadcrumb navigation
     */
    private function generateBreadcrumbs() {
        $path_parts = explode('/', $this->relative_path);
        $current_path = '';
        
        // Add home
        $this->breadcrumbs[] = [
            'name' => 'Home',
            'path' => '',
            'active' => empty($this->relative_path)
        ];
        
        // Add path parts
        foreach ($path_parts as $key => $part) {
            if (!empty($part)) {
                $current_path .= (empty($current_path) ? '' : '/') . $part;
                $this->breadcrumbs[] = [
                    'name' => $part,
                    'path' => $current_path,
                    'active' => ($key == count($path_parts) - 1)
                ];
            }
        }
    }
    
    /**
     * Scan the current directory and store file/folder info
     */
    private function scanDirectory() {
        $items = [];
        $folders = [];
        $files = [];
        
        if (is_dir($this->current_dir)) {
            $dir_contents = scandir($this->current_dir);
            
            if ($dir_contents) {
                foreach ($dir_contents as $item) {
                    // Skip hidden files (except explicitly showing index files)
                    if ((in_array($item, $this->config['hide_files']) || 
                        (substr($item, 0, 1) === '.' && $item !== '..')) &&
                        !in_array(strtolower($item), array_map('strtolower', $this->config['index_files']))) {
                        continue;
                    }
                    
                    $full_path = $this->current_dir . DIRECTORY_SEPARATOR . $item;
                    $relative_item_path = ltrim($this->relative_path . '/' . $item, '/');
                    
                    $is_index_file = in_array(strtolower($item), array_map('strtolower', $this->config['index_files']));
                    
                    $item_info = [
                        'name' => $item,
                        'path' => $relative_item_path,
                        'is_dir' => is_dir($full_path),
                        'size' => is_file($full_path) ? filesize($full_path) : 0,
                        'modified' => filemtime($full_path),
                        'ext' => pathinfo($item, PATHINFO_EXTENSION),
                        'is_index' => $is_index_file
                    ];
                    
                    // Separate folders and files
                    if ($item_info['is_dir']) {
                        $folders[] = $item_info;
                    } else {
                        $files[] = $item_info;
                    }
                }
                
                // Sort folders and files alphabetically
                usort($folders, function($a, $b) {
                    return strcasecmp($a['name'], $b['name']);
                });
                
                usort($files, function($a, $b) {
                    // Put index files first
                    if ($a['is_index'] && !$b['is_index']) return -1;
                    if (!$a['is_index'] && $b['is_index']) return 1;
                    return strcasecmp($a['name'], $b['name']);
                });
                
                // Combine folders and files (folders first)
                $this->items = array_merge($folders, $files);
            }
        }
    }
    
    /**
     * Get icon for a file type
     */
    public function getFileIcon($item) {
        if ($item['is_dir']) {
            return 'folder';
        }
        
        $ext = strtolower($item['ext']);
        $fileName = strtolower($item['name']);
        
        // Special case for index files
        if (in_array($fileName, array_map('strtolower', $this->config['index_files']))) {
            return 'home';
        }
        
        $icons = [
            // Images
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'bmp' => 'image',
            'svg' => 'image',
            'webp' => 'image',
            
            // Documents
            'pdf' => 'pdf',
            'doc' => 'word',
            'docx' => 'word',
            'xls' => 'excel',
            'xlsx' => 'excel',
            'ppt' => 'powerpoint',
            'pptx' => 'powerpoint',
            'txt' => 'text',
            'rtf' => 'text',
            'md' => 'text',
            
            // Web
            'html' => 'html',
            'htm' => 'html',
            'css' => 'css',
            'js' => 'javascript',
            'json' => 'javascript',
            'php' => 'php',
            'xml' => 'xml',
            
            // Archives
            'zip' => 'archive',
            'rar' => 'archive',
            'gz' => 'archive',
            'tar' => 'archive',
            '7z' => 'archive',
            
            // Audio/Video
            'mp3' => 'audio',
            'wav' => 'audio',
            'ogg' => 'audio',
            'mp4' => 'video',
            'avi' => 'video',
            'mov' => 'video',
            'wmv' => 'video',
            'flv' => 'video',
            'mkv' => 'video',
            
            // Other
            'exe' => 'application',
            'dll' => 'application',
            'bat' => 'script',
            'sh' => 'script',
            'py' => 'script',
            'sql' => 'database',
            'db' => 'database'
        ];
        
        return isset($icons[$ext]) ? $icons[$ext] : 'file';
    }
    
    /**
     * Format file size to human-readable format
     */
    public function formatFileSize($bytes) {
        if ($bytes == 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
    
    /**
     * Get current directory information
     */
    public function getCurrentDirectory() {
        return [
            'path' => $this->relative_path,
            'name' => basename($this->current_dir),
            'full_path' => $this->current_dir
        ];
    }
    
    /**
     * Get all items (files and folders)
     */
    public function getItems() {
        return $this->items;
    }
    
    /**
     * Get breadcrumb navigation
     */
    public function getBreadcrumbs() {
        return $this->breadcrumbs;
    }
}

// Initialize Directory Manager
$manager = new DirectoryManager($config);
$current_dir = $manager->getCurrentDirectory();
$breadcrumbs = $manager->getBreadcrumbs();
$items = $manager->getItems();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XFile Manager<?php echo (!empty($current_dir['path'])) ? ' - ' . $current_dir['name'] : ''; ?></title>
    
    <!-- Preconnect to CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: <?= $config['theme']['primary_color'] ?>;
            --secondary-color: <?= $config['theme']['secondary_color'] ?>;
            --text-color: <?= $config['theme']['text_color'] ?>;
            --background-color: <?= $config['theme']['background_color'] ?>;
            --card-background: <?= $config['theme']['card_background'] ?>;
            --border-color: rgba(0, 0, 0, 0.1);
            --hover-color: rgba(0, 0, 0, 0.04);
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --border-radius: 8px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --transition: all 0.2s ease-in-out;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            padding: var(--spacing);
            min-height: 100vh;
        }
        
        /* Header styles */
        .header {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-lg);
        }
        
        .app-title {
            display: flex;
            align-items: center;
            margin-right: auto;
        }
        
        .app-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-left: var(--spacing-sm);
            color: var(--primary-color);
        }
        
        .app-logo {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius);
            font-size: 1.2rem;
        }
        
        /* Breadcrumbs */
        .breadcrumbs {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            background-color: var(--card-background);
            padding: var(--spacing);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: var(--spacing-lg);
        }
        
        .breadcrumb-item {
            display: flex;
            align-items: center;
        }
        
        .breadcrumb-separator {
            margin: 0 var(--spacing-sm);
            color: #999;
        }
        
        .breadcrumb-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .breadcrumb-link:hover {
            background-color: var(--hover-color);
        }
        
        .breadcrumb-link.active {
            color: var(--text-color);
            font-weight: 600;
            cursor: default;
        }
        
        /* Toolbar */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing);
        }
        
        .directory-info {
            font-size: 0.9rem;
            color: #666;
        }
        
        .view-options {
            display: flex;
        }
        
        .view-option {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: var(--border-radius);
            background-color: var(--card-background);
            color: #666;
            margin-left: var(--spacing-xs);
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }
        
        .view-option:hover {
            background-color: var(--hover-color);
        }
        
        .view-option.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* File/Folder Container */
        .items-container {
            transition: var(--transition);
        }
        
        /* Grid view */
        .grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: var(--spacing);
        }
        
        .item-card {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        
        .item-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }
        
        .item-icon {
            padding: var(--spacing);
            background-color: rgba(0, 0, 0, 0.02);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2rem;
            color: var(--primary-color);
        }
        
        .folder-icon {
            color: var(--secondary-color);
        }
        
        .item-details {
            padding: var(--spacing);
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        
        .item-name {
            font-weight: 500;
            margin-bottom: var(--spacing-xs);
            word-break: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .item-meta {
            font-size: 0.8rem;
            color: #666;
            margin-top: auto;
        }
        
        .item-link {
            position: absolute;
            inset: 0;
            z-index: 1;
        }
        
        /* List view */
        .list-view {
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 var(--spacing-xs);
        }
        
        .list-item {
            display: table-row;
            background-color: var(--card-background);
            transition: var(--transition);
        }
        
        .list-item:hover {
            background-color: var(--hover-color);
        }
        
        .list-item > div {
            display: table-cell;
            padding: var(--spacing);
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }
        
        .list-item:first-child > div:first-child {
            border-top-left-radius: var(--border-radius);
        }
        
        .list-item:first-child > div:last-child {
            border-top-right-radius: var(--border-radius);
        }
        
        .list-item:last-child > div:first-child {
            border-bottom-left-radius: var(--border-radius);
        }
        
        .list-item:last-child > div:last-child {
            border-bottom-right-radius: var(--border-radius);
        }
        
        .list-icon {
            width: 40px;
            font-size: 1.2rem;
            text-align: center;
            color: var(--primary-color);
        }
        
        .list-name {
            font-weight: 500;
            position: relative;
        }
        
        .list-size, .list-modified {
            width: 140px;
            font-size: 0.85rem;
            color: #666;
            text-align: right;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: var(--spacing-xl);
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }
        
        .empty-icon {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: var(--spacing);
        }
        
        .empty-text {
            font-size: 1.2rem;
            color: #999;
            margin-bottom: var(--spacing-sm);
        }
        
        .empty-subtext {
            color: #999;
            font-size: 0.9rem;
        }
        
        /* Footer */
        .footer {
            margin-top: var(--spacing-xl);
            text-align: center;
            font-size: 0.8rem;
            color: #999;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .list-size, .list-modified {
                display: none;
            }
            
            .grid-view {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .app-title {
                margin-bottom: var(--spacing);
            }
            
            .toolbar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .directory-info {
                margin-bottom: var(--spacing-sm);
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .items-container {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .index-file-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--primary-color);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: var(--border-radius);
            z-index: 2;
            font-weight: 600;
        }
        
        .list-item .index-file-indicator {
            position: static;
            display: inline-block;
            margin-left: 10px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="app-title">
            <div class="app-logo">
                <i class="ri-folder-fill"></i>
            </div>
            <h1>XFile Manager</h1>
        </div>
    </header>
    
    <!-- Breadcrumbs navigation -->
    <div class="breadcrumbs">
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <div class="breadcrumb-item">
                <?php if ($index > 0): ?>
                    <span class="breadcrumb-separator"><i class="ri-arrow-right-s-line"></i></span>
                <?php endif; ?>
                
                <?php if ($crumb['active']): ?>
                    <span class="breadcrumb-link active"><?= htmlspecialchars($crumb['name']) ?></span>
                <?php else: ?>
                    <a href="?path=<?= urlencode($crumb['path']) ?>" class="breadcrumb-link">
                        <?= htmlspecialchars($crumb['name']) ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Toolbar -->
    <div class="toolbar">
        <div class="directory-info">
            <?= count($items) ?> items
        </div>
        
        <div class="view-options">
            <a href="?path=<?= urlencode($current_dir['path']) ?>&view=grid" 
               class="view-option <?= $config['view_mode'] === 'grid' ? 'active' : '' ?>">
                <i class="ri-grid-fill"></i>
            </a>
            <a href="?path=<?= urlencode($current_dir['path']) ?>&view=list" 
               class="view-option <?= $config['view_mode'] === 'list' ? 'active' : '' ?>">
                <i class="ri-list-check"></i>
            </a>
        </div>
    </div>
    
    <!-- Files and folders container -->
    <div class="items-container <?= $config['view_mode'] === 'grid' ? 'grid-view' : 'list-view' ?>">
        <?php if (empty($items)): ?>
            <!-- Empty state -->
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="ri-folder-2-line"></i>
                </div>
                <div class="empty-text">This folder is empty</div>
                <div class="empty-subtext">There are no files or folders in this directory</div>
            </div>
        <?php else: ?>
            <?php if ($config['view_mode'] === 'grid'): ?>
                <!-- Grid view -->
                <?php foreach ($items as $item): ?>
                    <div class="item-card">
                        <div class="item-icon">
                            <i class="ri-<?= $item['is_dir'] ? 'folder-fill folder-icon' : $manager->getFileIcon($item) . '-fill' ?>"></i>
                        </div>
                        <div class="item-details">
                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="item-meta">
                                <?php if (!$item['is_dir']): ?>
                                    <?= $manager->formatFileSize($item['size']) ?> • 
                                <?php endif; ?>
                                <?= date($config['date_format'], $item['modified']) ?>
                            </div>
                        </div>
                        <?php if ($item['is_dir']): ?>
                            <a href="?path=<?= urlencode($item['path']) ?>" class="item-link"></a>
                        <?php elseif (isset($item['is_index']) && $item['is_index']): ?>
                            <a href="<?= htmlspecialchars($item['path']) ?>" class="item-link"></a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($item['path']) ?>" class="item-link" download></a>
                        <?php endif; ?>
                        <?php if (isset($item['is_index']) && $item['is_index']): ?>
                            <div class="index-file-indicator">INDEX</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- List view -->
                <?php foreach ($items as $item): ?>
                    <div class="list-item">
                        <div class="list-icon">
                            <i class="ri-<?= $item['is_dir'] ? 'folder-fill folder-icon' : $manager->getFileIcon($item) . '-fill' ?>"></i>
                        </div>
                        <div class="list-name">
                            <?= htmlspecialchars($item['name']) ?>
                            <?php if (isset($item['is_index']) && $item['is_index']): ?>
                                <div class="index-file-indicator">INDEX</div>
                            <?php endif; ?>
                            <?php if ($item['is_dir']): ?>
                                <a href="?path=<?= urlencode($item['path']) ?>" class="item-link"></a>
                            <?php elseif (isset($item['is_index']) && $item['is_index']): ?>
                                <a href="<?= htmlspecialchars($item['path']) ?>" class="item-link"></a>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($item['path']) ?>" class="item-link" download></a>
                            <?php endif; ?>
                        </div>
                        <div class="list-size">
                            <?= $item['is_dir'] ? 'Folder' : $manager->formatFileSize($item['size']) ?>
                        </div>
                        <div class="list-modified">
                            <?= date($config['date_format'], $item['modified']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <p>XFile Manager • XAMPP File Explorer • <?= date('Y') ?></p>
    </footer>
    
    <script>
        // Add client-side interactivity here (optional)
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight the current view mode button
            const viewModeButtons = document.querySelectorAll('.view-option');
            viewModeButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    viewModeButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Add click events for items if needed
        });
    </script>
</body>
</html> 