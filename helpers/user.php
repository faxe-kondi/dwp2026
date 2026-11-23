<?php
function ds_user_avatar_url(array $user, int $size = 256): string {
  $avatar = $user['avatar'] ?? 'default.png';
  if ($avatar && $avatar !== 'default.png') {
    return 'uploads/avatars/' . $avatar;
  }
  $defaultAvatarPath = __DIR__ . '/../uploads/avatars/default.png';
  $version = is_file($defaultAvatarPath) ? filemtime($defaultAvatarPath) : false;
  return 'uploads/avatars/default.png' . ($version !== false ? '?v=' . $version : '');
}

function ds_profile_view(array $user, int $postCount): array {
  return [
    'display_name' => $user['username'],
    'username'      => $user['username'],
    'avatar_url'    => ds_user_avatar_url($user, 256),
    'bio'           => $user['bio'],
    'joined'        => 'Member since ' . date('M Y', strtotime($user['created_at'])),
    'stats' => [
      'posts'      => $postCount,
      'followers'  => (int) ($user['follower_count'] ?? 0),
      'following'  => (int) ($user['following_count'] ?? 0),
    ],
  ];
}

function ds_user_profile_url(string $username): string {
  return '@' . rawurlencode($username);
}
