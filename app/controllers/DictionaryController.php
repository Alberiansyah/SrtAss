<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\Response;
use App\Models\Dictionary;

class DictionaryController
{
    public function getList(): void
    {
        $dict = new Dictionary();
        $page = intval($_GET['page'] ?? 1);
        $search = $_GET['search'] ?? '';

        if (!empty($search)) {
            $words = $dict->search($search);
            $total = count($words);
        } else {
            $total = $dict->count();
            $words = $dict->getPage($page);
        }

        $html = '';
        foreach ($words as $key => $value) {
            $html .= '<div class="dict-item" data-key="' . htmlspecialchars($key) . '">
                <span class="dict-original">' . htmlspecialchars($key) . '</span>
                <i class="fas fa-arrow-right"></i>
                <span class="dict-replacement">' . htmlspecialchars($value) . '</span>
                <button class="btn btn-sm btn-outline-danger dict-remove" onclick="removeFromDictionary(\'' . htmlspecialchars(addslashes($key)) . '\')">
                    <i class="fas fa-times"></i>
                </button>
            </div>';
        }

        Response::json([
            'html' => $html,
            'total' => $total,
            'page' => $page,
            'perPage' => 50,
        ]);
    }

    public function export(): void
    {
        $format = $_GET['format'] ?? 'json';
        $dict = new Dictionary();
        $words = $dict->all();

        if ($format === 'txt') {
            $content = '';
            foreach ($words as $key => $value) {
                $content .= "$key=$value\n";
            }
            Response::download($content, 'dictionary.txt', 'text/plain');
        } else {
            $content = json_encode($words, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Response::download($content, 'dictionary.json', 'application/json');
        }
    }

    public function import(): void
    {
        if (!isset($_FILES['dict_file']) || $_FILES['dict_file']['error'] != UPLOAD_ERR_OK) {
            Session::set('import_error', 'Upload failed');
            Response::redirect('index.php');
        }

        $content = file_get_contents($_FILES['dict_file']['tmp_name']);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            Session::set('import_error', 'Invalid JSON format');
            Response::redirect('index.php');
        }

        $dict = new Dictionary();
        foreach ($data as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $dict->set($key, $value);
            }
        }
        $dict->save();

        Response::redirect('index.php');
    }
}