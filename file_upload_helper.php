<?php
/**
 * File Upload Helper for Serverless Environment
 * Supports local storage and cloud storage (Vercel Blob)
 */

class FileUploadHelper {
    private $storage_type;
    private $local_dir;
    private $blob_token;
    private $blob_url;

    public function __construct() {
        $this->storage_type = getenv('STORAGE_TYPE') ?: 'local';
        $this->local_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        $this->blob_token = getenv('BLOB_READ_WRITE_TOKEN');
        $this->blob_url = getenv('VERCEL_BLOB_URL') ?: 'https://blob.vercel-storage.com';
    }

    public function uploadFile($tmp_path, $filename, $mime_type = null) {
        if ($this->storage_type === 'blob') {
            return $this->uploadToBlob($tmp_path, $filename, $mime_type);
        } else {
            return $this->uploadToLocal($tmp_path, $filename);
        }
    }

    private function uploadToLocal($tmp_path, $filename) {
        $upload_dir = $this->local_dir;

        // Ensure directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }

        $filepath = $upload_dir . $filename;
        if (move_uploaded_file($tmp_path, $filepath)) {
            return $filename; // Return relative path
        }
        return false;
    }

    private function uploadToBlob($tmp_path, $filename, $mime_type = null) {
        if (!$this->blob_token) {
            // Fallback to local if no token
            return $this->uploadToLocal($tmp_path, $filename);
        }

        $url = $this->blob_url . '/upload';
        $file_content = file_get_contents($tmp_path);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->blob_token,
            'Content-Type: ' . ($mime_type ?: 'application/octet-stream'),
            'x-vercel-blob-filename: ' . $filename
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $file_content);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $data = json_decode($response, true);
            return $data['url'] ?? false;
        }

        return false;
    }

    public function getFileUrl($filename) {
        if ($this->storage_type === 'blob' && filter_var($filename, FILTER_VALIDATE_URL)) {
            return $filename; // Already a full URL
        } else {
            // Return local path relative to web root
            return '/uploads/' . $filename;
        }
    }

    public function deleteFile($filename) {
        if ($this->storage_type === 'blob' && filter_var($filename, FILTER_VALIDATE_URL)) {
            // For Blob, we could implement delete via API
            // For now, just return true
            return true;
        } else {
            $filepath = $this->local_dir . $filename;
            if (file_exists($filepath)) {
                return unlink($filepath);
            }
            return true;
        }
    }
}

// Global helper instance
$upload_helper = new FileUploadHelper();
?>
