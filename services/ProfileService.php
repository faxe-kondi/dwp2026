<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../support/Image.php';

class ProfileService
{
    private const MAX_AVATAR_BYTES = 5242880;

    private static function validateAvatarUpload(?array $upload): ?array
    {
        if ($upload === null) {
            return null;
        }

        $error = $upload['error'] ?? null;
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                throw new InvalidArgumentException('That image is too large for the server to accept. Try a smaller file.');
            }
            throw new InvalidArgumentException('There was a problem uploading that file. Please try again.');
        }

        $temporaryPath = $upload['tmp_name'] ?? null;
        if (!is_string($temporaryPath) || !is_file($temporaryPath)) {
            throw new InvalidArgumentException('There was a problem uploading that file. Please try again.');
        }

        $size = filesize($temporaryPath);
        if ($size === false) {
            throw new InvalidArgumentException('There was a problem uploading that file. Please try again.');
        }
        if ($size > self::MAX_AVATAR_BYTES) {
            throw new InvalidArgumentException('Profile picture must be 5MB or smaller.');
        }

        return $upload;
    }

    private static function removeStagedAvatar(?string $path): void
    {
        if ($path !== null && is_file($path) && !unlink($path)) {
            error_log('Could not remove a staged avatar after the profile update failed.');
        }
    }

    private static function removePreviousAvatar(string $filename, int $userId): string
    {
        if ($filename === '' || $filename === 'default.png') {
            return '';
        }

        if (basename($filename) !== $filename) {
            error_log('Avatar cleanup rejected an invalid filename for user #' . $userId . '.');
            return 'Profile saved, but the previous picture could not be removed.';
        }

        $path = dirname(__DIR__) . '/uploads/avatars/' . $filename;
        if (is_file($path) && !unlink($path)) {
            error_log('Avatar cleanup failed after updating user #' . $userId . '.');
            return 'Profile saved, but the previous picture could not be removed.';
        }

        return '';
    }

    public static function updateProfile(
        int $userId,
        string $username,
        ?string $bio,
        ?array $avatarUpload,
        string $previousAvatar
    ): array {
        $avatarUpload = self::validateAvatarUpload($avatarUpload);
        $newFilename = null;
        $stagedPath = null;

        try {
            if ($avatarUpload !== null) {
                $newFilename = bin2hex(random_bytes(16)) . '.webp';
                $directory = dirname(__DIR__) . '/uploads/avatars/';
                if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                    throw new RuntimeException('Could not create the uploads directory.');
                }

                $stagedPath = $directory . $newFilename;
                PostImage::saveSquare($avatarUpload['tmp_name'], $stagedPath);
            }

            User::updateProfile($userId, $username, $bio, $newFilename);
        } catch (Throwable $e) {
            self::removeStagedAvatar($stagedPath);
            throw $e;
        }

        $notice = $newFilename !== null
            ? self::removePreviousAvatar($previousAvatar, $userId)
            : '';

        return [
            'avatarFilename' => $newFilename,
            'notice' => $notice,
        ];
    }
}
