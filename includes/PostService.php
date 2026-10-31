<?php
require_once __DIR__ . '/Post.php';
require_once __DIR__ . '/Image.php';

class PostService
{
    private static function validateCaption(string $caption): void
    {
        if (mb_strlen($caption, 'UTF-8') > 750) {
            throw new InvalidArgumentException('Caption must be 750 characters or fewer.');
        }
    }

    private static function validateUpload(?array $upload, bool $required): ?array
    {
        if ($upload === null || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                throw new InvalidArgumentException('Choose a photo to post.');
            }
            return null;
        }

        if (($upload['error'] ?? null) !== UPLOAD_ERR_OK) {
            if (in_array($upload['error'] ?? null, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                throw new InvalidArgumentException('That image is too large for the server to accept. Try a smaller file.');
            }
            throw new InvalidArgumentException('There was a problem uploading that file. Please try again.');
        }

        if (empty($upload['tmp_name']) || !is_string($upload['tmp_name'])) {
            throw new InvalidArgumentException('There was a problem uploading that file. Please try again.');
        }

        return $upload;
    }

    private static function stageImage(array $upload): array
    {
        $filename = bin2hex(random_bytes(16)) . '.webp';
        $directory = dirname(__DIR__) . '/uploads/posts/';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create the uploads directory.');
        }

        $path = $directory . $filename;
        try {
            PostImage::saveSquare($upload['tmp_name'], $path);
        } catch (Throwable $e) {
            self::removeStagedImage($path, 'image processing');
            throw $e;
        }
        return [$filename, $path];
    }

    private static function removeStagedImage(?string $path, string $context): void
    {
        if ($path !== null && is_file($path) && !unlink($path)) {
            error_log('Could not remove a staged post image after ' . $context . ' failed.');
        }
    }

    private static function removePreviousImage(string $filename, int $postId): bool
    {
        $path = dirname(__DIR__) . '/uploads/posts/' . basename($filename);
        if (basename($filename) !== $filename || (is_file($path) && !unlink($path))) {
            error_log('Post image cleanup failed after editing post #' . $postId . '.');
            return false;
        }
        return true;
    }

    public static function createPost(int $userId, string $caption, ?array $upload): int
    {
        self::validateCaption($caption);
        $upload = self::validateUpload($upload, true);
        $stagedPath = null;

        try {
            [$filename, $stagedPath] = self::stageImage($upload);
            return Post::create($userId, $filename, $caption !== '' ? $caption : null);
        } catch (Throwable $e) {
            self::removeStagedImage($stagedPath, 'post creation');
            throw $e;
        }
    }

    public static function updatePost(
        int $postId,
        int $userId,
        string $previousFilename,
        string $caption,
        ?array $upload
    ): array {
        self::validateCaption($caption);
        $upload = self::validateUpload($upload, false);
        $newFilename = null;
        $stagedPath = null;

        try {
            if ($upload !== null) {
                [$newFilename, $stagedPath] = self::stageImage($upload);
            }

            if (!Post::updateOwned($postId, $userId, $newFilename, $caption !== '' ? $caption : null)) {
                self::removeStagedImage($stagedPath, 'post editing');
                return ['updated' => false, 'notice' => ''];
            }

            $notice = '';
            if ($newFilename !== null && !self::removePreviousImage($previousFilename, $postId)) {
                $notice = 'Post updated, but the previous image could not be removed.';
            }

            return ['updated' => true, 'notice' => $notice];
        } catch (Throwable $e) {
            self::removeStagedImage($stagedPath, 'post editing');
            throw $e;
        }
    }
}
