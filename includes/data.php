<?php
/**
 * DEADSTOCK — hardcoded demo data.
 * Stand-in for the database tables this project will use later
 * (posts, users, comments). Swap these arrays for real queries
 * once MySQL / phpMyAdmin is wired up.
 */

$demo_posts = [
    [
        'id' => 1,
        'username' => 'ravens_closet',
        'avatar_seed' => 'ravens_closet',
        'img_seed' => 'ds-101',
        'caption' => "Cop or drop? Archive Number Nine cardigan, thrifted for e12 off a rack nobody was checking.",
        'tags' => ['archive', 'thrifted', 'knitwear'],
        'likes' => 842,
        'comment_count' => 36,
        'time' => '2h ago',
        'hot' => true,
        'pinned' => false,
    ],
    [
        'id' => 2,
        'username' => 'og.tenko',
        'avatar_seed' => 'og.tenko',
        'img_seed' => 'ds-102',
        'caption' => "Camped outside since 5am for this restock. Line wrapped the block twice. Worth it.",
        'tags' => ['restock', 'queue-culture'],
        'likes' => 1290,
        'comment_count' => 58,
        'time' => '4h ago',
        'hot' => true,
        'pinned' => true,
    ],
    [
        'id' => 3,
        'username' => 'mona.fits',
        'avatar_seed' => 'mona.fits',
        'img_seed' => 'ds-103',
        'caption' => "Layering test #3. Boiler jacket over a hand-me-down flannel, cuffs rolled twice.",
        'tags' => ['layering', 'workwear'],
        'likes' => 431,
        'comment_count' => 12,
        'time' => '6h ago',
        'hot' => false,
        'pinned' => false,
    ],
    [
        'id' => 4,
        'username' => 'kudzu',
        'avatar_seed' => 'kudzu',
        'img_seed' => 'ds-104',
        'caption' => "Hand-painted the back panel myself. Stencil took four passes to get the drip right.",
        'tags' => ['diy', 'custom'],
        'likes' => 967,
        'comment_count' => 44,
        'time' => '9h ago',
        'hot' => true,
        'pinned' => false,
    ],
    [
        'id' => 5,
        'username' => 'perry.does.laundry',
        'avatar_seed' => 'perry.does.laundry',
        'img_seed' => 'ds-105',
        'caption' => "Rotation check: five pairs, one closet, zero regrets.",
        'tags' => ['sneakers', 'rotation'],
        'likes' => 275,
        'comment_count' => 9,
        'time' => '11h ago',
        'hot' => false,
        'pinned' => true,
    ],
    [
        'id' => 6,
        'username' => 'ravens_closet',
        'avatar_seed' => 'ravens_closet',
        'img_seed' => 'ds-106',
        'caption' => "Found the tag still on. Deadstock, size run intact, never worn once.",
        'tags' => ['deadstock', 'grail'],
        'likes' => 1502,
        'comment_count' => 71,
        'time' => '13h ago',
        'hot' => true,
        'pinned' => false,
    ],
    [
        'id' => 7,
        'username' => 'nb.thrift',
        'avatar_seed' => 'nb.thrift',
        'img_seed' => 'ds-107',
        'caption' => "Bin day haul. Three bags in, one keeper out - that's the ratio.",
        'tags' => ['thrifted', 'haul'],
        'likes' => 198,
        'comment_count' => 6,
        'time' => '1d ago',
        'hot' => false,
        'pinned' => false,
    ],
    [
        'id' => 8,
        'username' => 'og.tenko',
        'avatar_seed' => 'og.tenko',
        'img_seed' => 'ds-108',
        'caption' => "Collab piece nobody asked for and everybody needs. Drops Friday, midnight.",
        'tags' => ['collab', 'upcoming'],
        'likes' => 723,
        'comment_count' => 28,
        'time' => '1d ago',
        'hot' => false,
        'pinned' => true,
    ],
];

$demo_comments = [
    [
        'username' => 'mona.fits',
        'avatar_seed' => 'mona.fits',
        'text' => "the cuff roll is doing so much work here",
        'post_id' => 1,
        'time' => '12m ago',
    ],
    [
        'username' => 'kudzu',
        'avatar_seed' => 'kudzu',
        'text' => "how did you find this in a size M?? unheard of",
        'post_id' => 6,
        'time' => '28m ago',
    ],
    [
        'username' => 'perry.does.laundry',
        'avatar_seed' => 'perry.does.laundry',
        'text' => "the line stories had me stressed for you honestly",
        'post_id' => 2,
        'time' => '41m ago',
    ],
    [
        'username' => 'nb.thrift',
        'avatar_seed' => 'nb.thrift',
        'text' => "stencil is clean, what did you seal it with",
        'post_id' => 4,
        'time' => '1h ago',
    ],
    [
        'username' => 'ravens_closet',
        'avatar_seed' => 'ravens_closet',
        'text' => "friday midnight, setting three alarms",
        'post_id' => 8,
        'time' => '2h ago',
    ],
    [
        'username' => 'og.tenko',
        'avatar_seed' => 'og.tenko',
        'text' => "the middle pair is criminally underrated",
        'post_id' => 5,
        'time' => '3h ago',
    ],
];

$demo_liked_post_ids = [];

// Kept empty while the app is backed by real user data instead of
// prototype-only social lists.
$demo_following = [];

// Kept empty while the app is backed by real user data instead of
// prototype-only social lists.
$demo_blocked_users = [];

function ds_avatar_url($seed, $size = 96) {
    return "https://picsum.photos/seed/" . urlencode($seed) . "/{$size}/{$size}";
}

function ds_photo_url($seed, $w = 800, $h = 1000) {
    return "https://picsum.photos/seed/" . urlencode($seed) . "/{$w}/{$h}";
}
