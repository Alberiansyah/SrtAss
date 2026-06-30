<?php

namespace App\Models;

class BatchFile
{
    public string $uploadedFileName;
    public string $fileName;
    public string $extension;
    public SubtitleCollection $subtitles;
    public array $styles;
    public string $format;
    public string $scriptInfo;
    public string $projectGarbage;

    public function __construct(array $data = [])
    {
        $this->uploadedFileName = $data['uploaded_file_name'] ?? '';
        $this->fileName = $data['file_name'] ?? '';
        $this->extension = $data['extension'] ?? '';
        $this->format = $data['format'] ?? 'srt';
        $this->subtitles = new SubtitleCollection($data['subtitles'] ?? []);
        $this->styles = $data['styles'] ?? [];
        $this->scriptInfo = $data['scriptInfo'] ?? '';
        $this->projectGarbage = $data['projectGarbage'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'uploaded_file_name' => $this->uploadedFileName,
            'file_name' => $this->fileName,
            'extension' => $this->extension,
            'subtitles' => $this->subtitles->toArray(),
            'styles' => $this->styles,
            'scriptInfo' => $this->scriptInfo,
            'projectGarbage' => $this->projectGarbage,
            'format' => $this->format,
        ];
    }
}