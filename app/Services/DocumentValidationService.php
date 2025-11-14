<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service for validating uploaded documents
 */
class DocumentValidationService
{
    /**
     * Validate an uploaded document
     *
     * @param  UploadedFile  $file  The uploaded file
     * @param  array  $options  Validation options
     * @return array Validation result with errors and metadata
     */
    public function validateDocument(UploadedFile $file, array $options = []): array
    {
        $errors = [];
        $metadata = [
            'originalName' => $file->getClientOriginalName(),
            'mimeType' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
            'lastModified' => $file->getLastModified(),
        ];

        // Merge default options with provided options
        $options = array_merge([
            'maxSize' => 10 * 1024 * 1024, // 10MB
            'minSize' => 1024, // 1KB
            'allowedTypes' => ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'],
            'maxNameLength' => 100,
            'maxImageDimensions' => [5000, 5000],
            'minImageDimensions' => [100, 100],
            'maxPdfPages' => 50,
        ], $options);

        // Check file size
        if ($file->getSize() > $options['maxSize']) {
            $errors[] = 'File size exceeds '.($options['maxSize'] / 1024 / 1024).'MB limit';
        } elseif ($file->getSize() < $options['minSize']) {
            $errors[] = 'File is too small, may be corrupted';
        }

        // Check file type
        if (! in_array($file->getMimeType(), $options['allowedTypes'])) {
            $errors[] = 'Invalid file type. Only '.implode(', ', array_map(function ($type) {
                return strtoupper(str_replace('image/', '', str_replace('application/', '', $type)));
            }, $options['allowedTypes'])).' are allowed';
        }

        // Check file name length
        if (strlen($file->getClientOriginalName()) > $options['maxNameLength']) {
            $errors[] = 'File name is too long (max '.$options['maxNameLength'].' characters)';
        }

        // Check for special characters in filename
        if (preg_match('/[<>:"\/\\|?*]/', $file->getClientOriginalName())) {
            $errors[] = 'File name contains invalid characters';
        }

        // Deeper content validation based on file type
        try {
            $mimeType = $file->getMimeType();

            if (strpos($mimeType, 'image/') === 0) {
                // Validate image dimensions
                [$width, $height] = getimagesize($file->getPathname());
                $metadata['dimensions'] = ['width' => $width, 'height' => $height];

                // Check if image dimensions are reasonable
                if ($width < $options['minImageDimensions'][0] || $height < $options['minImageDimensions'][1]) {
                    $errors[] = 'Image is too small (minimum '.
                        $options['minImageDimensions'][0].'x'.$options['minImageDimensions'][1].' pixels)';
                }

                if ($width > $options['maxImageDimensions'][0] || $height > $options['maxImageDimensions'][1]) {
                    $errors[] = 'Image is too large (maximum '.
                        $options['maxImageDimensions'][0].'x'.$options['maxImageDimensions'][1].' pixels)';
                }

                // Check image integrity
                if (! $this->validateImageIntegrity($file)) {
                    $errors[] = 'Image file appears to be corrupted';
                }
            } elseif ($mimeType === 'application/pdf') {
                // Validate PDF content
                $pageCount = $this->getPdfPageCount($file);
                $metadata['pageCount'] = $pageCount;

                // Check if PDF has a reasonable number of pages
                if ($pageCount === 0) {
                    $errors[] = 'PDF file appears to be empty';
                }

                if ($pageCount > $options['maxPdfPages']) {
                    $errors[] = 'PDF has too many pages (maximum '.$options['maxPdfPages'].' pages)';
                }

                // Check PDF integrity
                if (! $this->validatePdfIntegrity($file)) {
                    $errors[] = 'PDF file appears to be corrupted';
                }
            }

            // Generate security hash for file integrity verification
            $metadata['securityHash'] = $this->generateFileHash($file);

        } catch (\Exception $e) {
            Log::error('Error during document validation', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $errors[] = 'Could not validate file content. The file may be corrupted.';
        }

        return [
            'isValid' => count($errors) === 0,
            'errors' => $errors,
            'metadata' => $metadata,
        ];
    }

    /**
     * Validate image file integrity
     *
     * @param  UploadedFile  $file  The uploaded file
     * @return bool True if the image is valid, false otherwise
     */
    private function validateImageIntegrity(UploadedFile $file): bool
    {
        try {
            $image = @imagecreatefromstring(file_get_contents($file->getPathname()));
            if ($image === false) {
                return false;
            }
            imagedestroy($image);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate PDF file integrity
     *
     * @param  UploadedFile  $file  The uploaded file
     * @return bool True if the PDF is valid, false otherwise
     */
    private function validatePdfIntegrity(UploadedFile $file): bool
    {
        try {
            // Check for PDF header
            $content = file_get_contents($file->getPathname(), false, null, 0, 1024);
            if (strpos($content, '%PDF-') !== 0) {
                return false;
            }

            // Check for EOF marker
            $content = file_get_contents($file->getPathname(), false, null, -1024);
            if (strpos($content, '%%EOF') === false) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the number of pages in a PDF file
     *
     * @param  UploadedFile  $file  The uploaded file
     * @return int Number of pages
     */
    private function getPdfPageCount(UploadedFile $file): int
    {
        try {
            $content = file_get_contents($file->getPathname());
            preg_match_all('/\/Type\s*\/Page[^s]/i', $content, $matches);
            $count = count($matches[0]);

            return $count > 0 ? $count : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Generate a hash for file integrity verification
     *
     * @param  UploadedFile  $file  The uploaded file
     * @return string Hash value
     */
    private function generateFileHash(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getPathname());
    }

    /**
     * Store a validated document
     *
     * @param  UploadedFile  $file  The uploaded file
     * @param  string  $documentType  The document type (e.g., 'national_id', 'proof_of_residence')
     * @param  string|null  $sessionId  Optional session ID for grouping documents
     * @return array Storage result with path and metadata
     */
    public function storeDocument(UploadedFile $file, string $documentType, ?string $sessionId = null): array
    {
        // Generate a unique filename
        $filename = $this->generateUniqueFilename($file, $documentType, $sessionId);

        // Store the file
        $path = $file->storeAs(
            "uploads/{$documentType}".($sessionId ? "/{$sessionId}" : ''),
            $filename,
            'public'
        );

        // For cPanel compatibility, also copy to public/storage if it exists and is a directory
        $this->ensurePublicStorageSync($path);

        // Generate metadata
        $metadata = [
            'originalName' => $file->getClientOriginalName(),
            'mimeType' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'storedAt' => now()->toISOString(),
            'securityHash' => $this->generateFileHash($file),
        ];

        // Log the storage event
        Log::info('Document stored', [
            'documentType' => $documentType,
            'sessionId' => $sessionId,
            'filename' => $filename,
            'path' => $path,
            'metadata' => $metadata,
        ]);

        return [
            'path' => $path,
            'metadata' => $metadata,
        ];
    }

    /**
     * Generate a unique filename for a document
     *
     * @param  UploadedFile  $file  The uploaded file
     * @param  string  $documentType  The document type
     * @param  string|null  $sessionId  Optional session ID
     * @return string Unique filename
     */
    private function generateUniqueFilename(UploadedFile $file, string $documentType, ?string $sessionId = null): string
    {
        $extension = $file->getClientOriginalExtension();
        $uuid = (string) Str::uuid();
        $timestamp = now()->format('YmdHis');

        return "{$documentType}_{$timestamp}_{$uuid}.{$extension}";
    }

    /**
     * Ensure file is accessible in public/storage for cPanel compatibility
     *
     * @param  string  $path  The storage path
     */
    private function ensurePublicStorageSync(string $path): void
    {
        try {
            $publicStorageDir = public_path('storage');
            $sourceFile = Storage::disk('public')->path($path);
            $targetFile = $publicStorageDir.DIRECTORY_SEPARATOR.$path;

            // Only sync if public/storage is a directory (not a symlink)
            if (is_dir($publicStorageDir) && ! is_link($publicStorageDir)) {
                // Create target directory if it doesn't exist
                $targetDir = dirname($targetFile);
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                // Copy file if source exists and target doesn't exist or is older
                if (file_exists($sourceFile) &&
                    (! file_exists($targetFile) || filemtime($sourceFile) > filemtime($targetFile))) {
                    copy($sourceFile, $targetFile);
                    chmod($targetFile, 0644);

                    Log::info('File synced to public storage', [
                        'source' => $sourceFile,
                        'target' => $targetFile,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log but don't fail the upload if sync fails
            Log::warning('Failed to sync file to public storage', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
