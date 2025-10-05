<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\JpegEncoder;

class ImageOptimizationService
{
    protected ImageManager $manager;
    
    protected array $config = [
        'question_image' => [
            'max_width' => 800,
            'max_height' => 800,
            'quality' => 85,
            'format' => 'webp',
            'max_size_kb' => 500,
        ],
        'logo' => [
            'max_width' => 400,
            'max_height' => 200,
            'quality' => 90,
            'format' => 'png',
            'max_size_kb' => 200,
        ],
        'favicon' => [
            'max_width' => 64,
            'max_height' => 64,
            'quality' => 90,
            'format' => 'png',
            'max_size_kb' => 50,
        ],
        'login_background' => [
            'max_width' => 1920,
            'max_height' => 1080,
            'quality' => 85,
            'format' => 'webp',
            'max_size_kb' => 800,
        ],
        'profile_picture' => [
            'max_width' => 300,
            'max_height' => 300,
            'quality' => 85,
            'format' => 'webp',
            'max_size_kb' => 150,
        ],
        'skill_image' => [ 
            'max_width' => 600,
            'max_height' => 400,
            'quality' => 85,
            'format' => 'webp',
            'max_size_kb' => 500,
        ],
        'track_image' => [
            'max_width' => 600,
            'max_height' => 400,
            'quality' => 85,
            'format' => 'webp',
            'max_size_kb' => 500,
        ],
        'default' => [
            'max_width' => 1200,
            'max_height' => 1200,
            'quality' => 85,
            'format' => 'webp',
            'max_size_kb' => 500,
        ],
    ];

    public function __construct()
    {
        // Explicitly create ImageManager with GD driver
        $this->manager = new ImageManager(new GdDriver());
    }

    public function optimize(UploadedFile $file, string $type = 'default', ?string $path = null): array
    {
        $config = $this->config[$type] ?? $this->config['default'];
        
        $maxBytes = $config['max_size_kb'] * 1024;
        if ($file->getSize() > $maxBytes) {
            throw new \Exception("File too large. Maximum size: {$config['max_size_kb']}KB");
        }

        try {
            // Use the manager instance instead of facade
            $image = $this->manager->read($file->getRealPath());
        } catch (\Exception $e) {
            throw new \Exception("Invalid image file: " . $e->getMessage());
        }
        
        $originalWidth = $image->width();
        $originalHeight = $image->height();
        $originalSize = $file->getSize();
        
        if ($originalWidth > $config['max_width'] || $originalHeight > $config['max_height']) {
            $image->scaleDown(
                width: $config['max_width'],
                height: $config['max_height']
            );
        }
        
        $directory = $path ?? $this->getStorageDirectory($type);
        $filename = $this->generateFilename($file, $config['format']);
        $fullPath = "{$directory}/{$filename}";
        
        $encoder = $this->getEncoder($config['format'], $config['quality']);
        $encoded = $image->encode($encoder);
        
        Storage::disk('public')->put($fullPath, (string) $encoded);
        
        $storedSize = Storage::disk('public')->size($fullPath);
        $url = Storage::disk('public')->url($fullPath);
        
        return [
            'success' => true,
            'path' => $fullPath,
            'url' => $url,
            'size' => $storedSize,
            'original_size' => $originalSize,
            'saved_bytes' => max(0, $originalSize - $storedSize),
            'dimensions' => [
                'width' => $image->width(),
                'height' => $image->height(),
            ],
            'original_dimensions' => [
                'width' => $originalWidth,
                'height' => $originalHeight,
            ],
        ];
    }

    protected function getEncoder(string $format, int $quality)
    {
        return match($format) {
            'webp' => new WebpEncoder(quality: $quality),
            'png' => new PngEncoder(),
            'jpg', 'jpeg' => new JpegEncoder(quality: $quality),
            default => new WebpEncoder(quality: $quality),
        };
    }

    public function optimizeBatch(array $files, string $type = 'default'): array
    {
        $results = [];
        foreach ($files as $file) {
            try {
                $results[] = $this->optimize($file, $type);
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'filename' => $file->getClientOriginalName(),
                ];
            }
        }
        return $results;
    }

    protected function getStorageDirectory(string $type): string
    {
        return match($type) {
            'question_image' => 'questions',
            'logo' => 'logos',
            'favicon' => 'favicons',
            'login_background' => 'backgrounds',
            'profile_picture' => 'profiles',
            'skill_image' => 'skills',
            'track_image' => 'tracks',
            default => 'images',
        };
    }

    protected function generateFilename(UploadedFile $file, string $format): string
    {
        $hash = md5($file->getClientOriginalName() . time() . uniqid());
        return substr($hash, 0, 16) . '.' . $format;
    }

    public function getConfig(string $type): array
    {
        return $this->config[$type] ?? $this->config['default'];
    }
}