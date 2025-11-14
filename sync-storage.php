<?php

/**
 * Storage Sync Script for cPanel hosting
 * Run this script after uploading files to sync storage/app/public with public/storage
 */
$sourceDir = __DIR__.'/storage/app/public';
$targetDir = __DIR__.'/public/storage';

function copyDirectory($source, $target)
{
    if (! is_dir($source)) {
        echo "Source directory does not exist: $source\n";

        return false;
    }

    if (! is_dir($target)) {
        if (! mkdir($target, 0755, true)) {
            echo "Failed to create target directory: $target\n";

            return false;
        }
    }

    $dir = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::SELF_FIRST);

    foreach ($files as $file) {
        $targetPath = $target.DIRECTORY_SEPARATOR.$files->getSubPathName();

        if ($file->isDir()) {
            if (! is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
        } else {
            // Ensure target directory exists
            $targetDir = dirname($targetPath);
            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            if (! copy($file->getRealPath(), $targetPath)) {
                echo 'Failed to copy: '.$file->getRealPath()." to $targetPath\n";

                return false;
            }
        }
    }

    return true;
}

// Remove existing symlink if it exists
if (is_link($targetDir)) {
    unlink($targetDir);
}

// Remove existing directory if it exists
if (is_dir($targetDir) && ! is_link($targetDir)) {
    function deleteDirectory($dir)
    {
        if (! is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            is_dir($path) ? deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
    deleteDirectory($targetDir);
}

// Copy files
if (copyDirectory($sourceDir, $targetDir)) {
    echo "✅ Storage sync completed successfully!\n";
    echo "Files copied from $sourceDir to $targetDir\n";
} else {
    echo "❌ Storage sync failed!\n";
}

// Set permissions
chmod($targetDir, 0755);

// Create .htaccess for security in uploads directory
$uploadsDir = $targetDir.'/uploads';
if (is_dir($uploadsDir)) {
    $htaccessContent = "# Prevent direct access to uploaded files\n";
    $htaccessContent .= "Options -Indexes\n";
    $htaccessContent .= "DirectoryIndex index.html index.php\n\n";
    $htaccessContent .= "# Allow only specific file types\n";
    $htaccessContent .= "<FilesMatch \"\\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$\">\n";
    $htaccessContent .= "    Order Allow,Deny\n";
    $htaccessContent .= "    Deny from all\n";
    $htaccessContent .= "</FilesMatch>\n";

    file_put_contents($uploadsDir.'/.htaccess', $htaccessContent);
    echo "✅ Security .htaccess created in uploads directory\n";
}

echo "Run this script whenever you upload new files to keep storage in sync.\n";
