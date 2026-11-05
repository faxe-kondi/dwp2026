<?php
require_once __DIR__ . '/../db.php';

class SiteSettings
{
    private static ?array $settings = null;

    public static function get(string $key): string
    {
        if (self::$settings === null) {
            $stmt = Database::connect()->query(
                'SELECT setting_key, setting_value FROM site_settings'
            );
            self::$settings = [];
            foreach ($stmt->fetchAll() as $setting) {
                self::$settings[$setting['setting_key']] = $setting['setting_value'];
            }
        }

        return (string) (self::$settings[$key] ?? '');
    }

    public static function getColor(string $key, string $fallback): string
    {
        $color = self::get($key);
        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)
            ? $color
            : $fallback;
    }

    public static function getAboutStats(): array
    {
        $stmt = Database::connect()->query(
            'SELECT
                (SELECT COUNT(*)
                 FROM posts
                 JOIN users ON users.id = posts.user_id
                 WHERE users.is_banned = 0) AS posts,
                (SELECT COUNT(*) FROM users WHERE is_banned = 0) AS members,
                (SELECT COUNT(*)
                 FROM comments
                 JOIN users AS commenters ON commenters.id = comments.user_id
                 JOIN posts ON posts.id = comments.post_id
                 JOIN users AS post_owners ON post_owners.id = posts.user_id
                 WHERE commenters.is_banned = 0 AND post_owners.is_banned = 0) AS comments'
        );
        $stats = $stmt->fetch();

        return [
            'posts' => (int) $stats['posts'],
            'members' => (int) $stats['members'],
            'comments' => (int) $stats['comments'],
        ];
    }
}
