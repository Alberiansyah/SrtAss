<?php
session_start();
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['dictionary_file'])) {
    $file = $_FILES['dictionary_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $content = file_get_contents($file['tmp_name']);
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        $imported = 0;
        
        if ($extension === 'json') {
            $data = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $_SESSION['dictionary'] = array_merge($_SESSION['dictionary'] ?? [], $data);
                saveDictionaryToJson($_SESSION['dictionary']);
                $imported = count($data);
            } else {
                $_SESSION['import_error'] = 'Invalid JSON format';
            }
        } elseif ($extension === 'txt') {
            $lines = explode("\n", $content);
            $newEntries = [];
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '=') === false) continue;
                
                $parts = explode('=', $line, 2);
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                
                if (!empty($key) && !empty($value)) {
                    $newEntries[$key] = $value;
                }
            }
            
            $_SESSION['dictionary'] = array_merge($_SESSION['dictionary'] ?? [], $newEntries);
            saveDictionaryToJson($_SESSION['dictionary']);
            $imported = count($newEntries);
        }
        
        if ($imported > 0) {
            $_SESSION['import_success'] = "Successfully imported $imported entries";
        }
    }
    
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
    header("Location: $redirect");
    exit;
}
