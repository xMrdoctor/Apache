# XFile Manager 📁✨

![XFile Manager](https://img.shields.io/badge/XFile-Manager-00509a?style=for-the-badge) 
![PHP Version](https://img.shields.io/badge/PHP-7.0+-777BB4?style=flat-square&logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)

A modern, professional, and beautifully designed file manager for XAMPP environments. This file manager replaces the default Apache directory listing with a sleek, responsive interface that delivers a superior local development experience.

<p align="center">
  <img src="https://via.placeholder.com/800x450.png?text=XFile+Manager+Screenshot" alt="XFile Manager Screenshot">
</p>

## ✨ Features

- 🌈 **Modern UI/UX** with grid and list views
- 🌐 **Responsive design** for desktop and mobile
- 📊 **File information** showing size, type and last modified date
- 🧭 **Breadcrumb navigation** for easy directory traversal
- 🔍 **File type icons** for quick visual identification
- 📥 **Direct download** of files with a single click
- 🔒 **Secure design** with protection against directory traversal
- 📱 **Mobile-friendly** interface that adapts to screen size
- 🚀 **Lightweight & fast** with minimal dependencies
- 📑 **Index file detection** and direct access
- 💼 **Professional styling** with beautiful colors and typography

## 📥 Installation

### Quick Setup

1. Download these three files:
   - `index.php` - The main file manager code
   - `.htaccess` - Apache configuration
   - `xfile_manager.php` - Compatibility helper file

2. Copy all three files to any directory in your XAMPP installation where you want the file manager to be available.

3. Access the directory through your browser:
   ```
   http://localhost/your-directory/
   ```

### Step-by-Step Instructions

#### 1. Download the Files

Clone this repository or download the files individually:

```bash
git clone https://github.com/yourusername/xfile-manager.git
cd xfile-manager
```

#### 2. Place the Files in Your XAMPP Directory

Copy the three files to any directory where you want the file manager:

```bash
cp index.php .htaccess xfile_manager.php /path/to/xampp/htdocs/your-directory/
```

#### 3. Navigate to the Directory

Open your browser and navigate to:

```
http://localhost/your-directory/
```

## 🎮 Usage Guide

### Basic Navigation

- **Browse directories**: Click on any folder to navigate into it
- **Download files**: Click on any file to download it
- **Open index files**: Click on files marked with the "INDEX" badge to open them directly
- **Navigate up**: Use the breadcrumb navigation at the top to go back up the directory structure

### View Modes

XFile Manager offers two view modes that you can switch between:

1. **Grid View**: Displays files and folders as cards in a grid layout (default)
   - To activate: Click the grid icon in the toolbar
   
2. **List View**: Shows files and folders in a detailed list with more information
   - To activate: Click the list icon in the toolbar

<p align="center">
  <img src="https://via.placeholder.com/400x250.png?text=Grid+View" alt="Grid View">
  <img src="https://via.placeholder.com/400x250.png?text=List+View" alt="List View">
</p>

### File Types and Icons

XFile Manager automatically assigns appropriate icons to different file types:

- 📁 **Folders**: Displayed with a folder icon
- 🏠 **Index Files**: Shown with a home icon and an "INDEX" badge
- 📄 **Documents**: Text files, PDFs, Word documents, etc.
- 🖼️ **Images**: JPG, PNG, GIF, SVG, etc.
- 🎵 **Audio**: MP3, WAV, OGG, etc.
- 🎬 **Video**: MP4, AVI, MOV, etc.
- 🗜️ **Archives**: ZIP, RAR, TAR, etc.
- 💻 **Code**: HTML, CSS, JS, PHP, etc.

### Special Features

- **Empty Directory Notice**: When a folder contains no files, a helpful message is displayed
- **Index File Priority**: Index files appear at the top of the list for easy access
- **Auto-Redirect**: If a directory contains an index file, it will open automatically

## ⚙️ Configuration

XFile Manager is designed to work out of the box, but you can customize its behavior by editing the configuration section at the top of the `index.php` file.

### Customizable Options

```php
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
    'view_mode' => isset($_GET['view']) ? $_GET['view'] : 'grid',
    'index_files' => ['index.php', 'index.html', 'index.htm']
];
```

### Configuration Options Explained

| Option | Description |
|--------|-------------|
| `hide_files` | Files and folders to hide from the display |
| `ignored_extensions` | File extensions to hide from the display |
| `date_format` | Format for displaying dates (using PHP's date function) |
| `theme.primary_color` | Main accent color for the interface |
| `theme.secondary_color` | Secondary color for certain UI elements |
| `view_mode` | Default view mode (grid or list) |
| `index_files` | Files that should be considered as index files |

## 🧩 How It Works

XFile Manager consists of three key components:

1. **index.php**: The main file containing the file manager code and UI
2. **.htaccess**: Apache configuration that handles how directories are accessed
3. **xfile_manager.php**: A helper file that ensures compatibility with actual index files

When you access a directory:

1. Apache first checks if there's an actual index file (like index.php or index.html)
2. If an index file exists, it's served directly
3. If no index file exists, our file manager is loaded, showing all files and folders

## 🛡️ Security Considerations

XFile Manager is designed for **local development only** and includes basic security measures:

- Protection against directory traversal attacks
- Hiding of sensitive files and directories
- No file upload or editing functionality

⚠️ **Important**: This file manager is not intended for production environments or publicly accessible servers. It's designed specifically for local development in XAMPP.

## 🔄 Browser Compatibility

XFile Manager is compatible with all modern browsers:

- Chrome (recommended)
- Firefox
- Safari
- Edge

## 🤝 Contributing

Contributions are welcome! If you'd like to improve XFile Manager:

1. Fork the repository
2. Create a new branch (`git checkout -b feature-branch`)
3. Make your changes
4. Commit your changes (`git commit -m 'Add some feature'`)
5. Push to the branch (`git push origin feature-branch`)
6. Open a Pull Request

## 📜 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgements

- [RemixIcon](https://remixicon.com/) - For the beautiful icons
- [Inter Font](https://rsms.me/inter/) - For the clean typography

---

<p align="center">
  Made with ❤️ for XAMPP developers
</p> 