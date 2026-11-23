<?php
class PostImage
{
    private const SIZE = 1200;
    private const WEBP_QUALITY = 82;

    public static function saveSquare(string $sourcePath, string $destinationPath): void
    {
        if (!extension_loaded('gd') || !function_exists('imagewebp')) {
            throw new RuntimeException('GD with WebP support is required to process post images.');
        }

        $info = @getimagesize($sourcePath);
        if ($info === false || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new InvalidArgumentException('Photo must be a valid JPG, PNG, or WEBP image.');
        }

        $width = (int) $info[0];
        $height = (int) $info[1];
        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('The uploaded image dimensions are invalid.');
        }

        self::ensureMemoryAvailable($width, $height);

        $decoder = [
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
        ][$info['mime']];
        if (!function_exists($decoder)) {
            throw new RuntimeException('This server cannot process the uploaded image format.');
        }

        $source = @$decoder($sourcePath);
        if ($source === false) {
            throw new InvalidArgumentException('The uploaded image could not be decoded.');
        }

        try {
            self::applyExifOrientation($source, $sourcePath, $info['mime']);

            $width = imagesx($source);
            $height = imagesy($source);
            $cropSize = min($width, $height);
            $sourceX = (int) floor(($width - $cropSize) / 2);
            $sourceY = (int) floor(($height - $cropSize) / 2);

            $square = imagecreatetruecolor(self::SIZE, self::SIZE);
            if ($square === false) {
                throw new RuntimeException('Could not allocate space for the optimized image.');
            }

            try {
                $background = imagecolorallocate($square, 255, 255, 255);
                imagefill($square, 0, 0, $background);

                if (!imagecopyresampled(
                    $square,
                    $source,
                    0,
                    0,
                    $sourceX,
                    $sourceY,
                    self::SIZE,
                    self::SIZE,
                    $cropSize,
                    $cropSize
                )) {
                    throw new RuntimeException('Could not resize the uploaded image.');
                }

                if (!imagewebp($square, $destinationPath, self::WEBP_QUALITY)) {
                    throw new RuntimeException('Could not save the optimized image.');
                }
            } finally {
                imagedestroy($square);
            }
        } finally {
            imagedestroy($source);
        }
    }

    private static function applyExifOrientation(&$image, string $sourcePath, string $mime): void
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return;
        }

        $exif = @exif_read_data($sourcePath, 'IFD0');
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $flip = null;
        $rotation = 0;

        switch ($orientation) {
            case 2:
                $flip = IMG_FLIP_HORIZONTAL;
                break;
            case 3:
                $rotation = 180;
                break;
            case 4:
                $flip = IMG_FLIP_VERTICAL;
                break;
            case 5:
                $rotation = -90;
                $flip = IMG_FLIP_HORIZONTAL;
                break;
            case 6:
                $rotation = -90;
                break;
            case 7:
                $rotation = 90;
                $flip = IMG_FLIP_HORIZONTAL;
                break;
            case 8:
                $rotation = 90;
                break;
        }

        if ($rotation !== 0) {
            $rotated = imagerotate($image, $rotation, 0);
            if ($rotated === false) {
                throw new RuntimeException('Could not orient the uploaded image.');
            }
            imagedestroy($image);
            $image = $rotated;
        }

        if ($flip !== null && !imageflip($image, $flip)) {
            throw new RuntimeException('Could not orient the uploaded image.');
        }
    }

    private static function ensureMemoryAvailable(int $width, int $height): void
    {
        $limit = trim((string) ini_get('memory_limit'));
        if ($limit === '' || $limit === '-1') {
            return;
        }

        $unit = strtolower(substr($limit, -1));
        $value = (float) $limit;
        if ($unit === 'g') {
            $value *= 1024;
        }
        if ($unit === 'g' || $unit === 'm') {
            $value *= 1024;
        }
        if ($unit === 'g' || $unit === 'm' || $unit === 'k') {
            $value *= 1024;
        }

        $available = (int) $value - memory_get_usage(true);
        $estimated = ($width * $height * 5) + (64 * 1024 * 1024);
        if ($estimated > $available) {
            throw new RuntimeException('The image exceeds the memory available for processing on this server.');
        }
    }
}
