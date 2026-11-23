<?php
require_once __DIR__ . '/../support/Auth.php';
require_once __DIR__ . '/../support/Csrf.php';
require_once __DIR__ . '/user.php';

function ds_time_ago(string $datetime): string {
  $diff = time() - strtotime($datetime);
  if ($diff < 60) { return 'just now'; }
  if ($diff < 3600) { return floor($diff / 60) . 'm ago'; }
  if ($diff < 86400) { return floor($diff / 3600) . 'h ago'; }
  if ($diff < 604800) { return floor($diff / 86400) . 'd ago'; }
  return date('M j', strtotime($datetime));
}

function ds_post_view(array $row): array {
  return [
    'id'            => (int) $row['id'],
    'user_id'       => (int) $row['user_id'],
    'username'      => $row['username'],
    'avatar_file'   => $row['avatar'] ?? 'default.png',
    'image_file'    => $row['filename'],
    'title'         => $row['title'] ?? ($row['caption'] ?? ''),
    'caption'       => $row['caption'] ?? '',
    'tags'          => [],
    'likes'         => (int) ($row['likes'] ?? 0),
    'liked_by_viewer' => !empty($row['liked_by_viewer']),
    'comment_count' => (int) ($row['comment_count'] ?? 0),
    'time'          => ds_time_ago($row['created_at']),
    'hot'           => !empty($row['hot_likes']),
    'front_pinned'  => !empty($row['front_pinned_at']),
    'profile_pinned' => !empty($row['profile_pinned_at']),
  ];
}

function ds_post_avatar_url(array $p, int $size = 96): string {
  return ds_user_avatar_url(['username' => $p['username'], 'avatar' => $p['avatar_file']], $size);
}

function ds_post_image_url(array $p): string {
  return 'uploads/posts/' . $p['image_file'];
}
