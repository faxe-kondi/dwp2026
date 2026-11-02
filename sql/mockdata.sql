-- Mock data for the `deadstock` database
-- Run AFTER your schema script, on a fresh database (drop and recreate it to reset).
-- Run this in ONE session (it uses the variables @u and @p).
-- NOTE: password_hash values are dummy bcrypt-shaped strings, so these accounts can't be
-- logged into. To make one usable, replace the hash with a real one (e.g. PHP password_hash()).

USE deadstock;

-- Offsets so the mock rows go after any existing users/posts (0 on a fresh DB)
SET @u = (SELECT COALESCE(MAX(id), 0) FROM users);
SET @p = (SELECT COALESCE(MAX(id), 0) FROM posts);

-- ---------------------------------------------------------------
-- Site settings (fill in the empty defaults)
-- ---------------------------------------------------------------
UPDATE site_settings SET setting_value = 'Deadstock is a small community for sharing photos of forgotten, unused and one-of-a-kind finds.' WHERE setting_key = 'description';
UPDATE site_settings SET setting_value = '1. Be kind.\n2. Only post your own photos.\n3. No spam or advertising.\n4. No hate speech or harassment.\n5. Moderators have the final say.' WHERE setting_key = 'rules';
UPDATE site_settings SET setting_value = 'hello@deadstock.example' WHERE setting_key = 'contact';

-- ---------------------------------------------------------------
-- Users  (1 owner, 2 moderators, 9 users, 2 banned)
-- ---------------------------------------------------------------
INSERT INTO users (id, username, email, password_hash, bio, avatar, role_id, is_banned, ban_reason, banned_at, created_at) VALUES
(@u + 1,  'deadstock_admin', 'admin@deadstock.example',   '$2y$10$MockHashOwner0000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', 'Owner of Deadstock. Collector of dusty things.',            'default.png',     3, 0, NULL, NULL, '2026-07-20 09:00:00'),
(@u + 2,  'mod_rust',        'rust@deadstock.example',    '$2y$10$MockHashMod00000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', 'Moderator. Rust is just patina with ambition.',           'default.png', 2, 0, NULL, NULL, '2026-07-22 10:15:00'),
(@u + 3,  'mod_vera',        'vera@deadstock.example',    '$2y$10$MockHashMod10000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', 'Moderator. Thrift stores are my second home.',           'default.png', 2, 0, NULL, NULL, '2026-07-22 11:40:00'),
(@u + 4,  'nightcrawler',    'night@example.com',         '$2y$10$MockHashUser0000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', 'Night photography and abandoned places.',                'default.png',     1, 0, NULL, NULL, '2026-08-01 18:30:00'),
(@u + 5,  'pixelpusher',     'pixel@example.com',         '$2y$10$MockHashUser1000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', NULL,                                                         'default.png',     1, 0, NULL, NULL, '2026-08-03 14:05:00'),
(@u + 6,  'lowfi_lena',      'lena@example.com',          '$2y$10$MockHashUser2000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', 'Cassettes, vinyl and anything that crackles.',            'default.png', 1, 0, NULL, NULL, '2026-08-05 20:12:00'),
(@u + 7,  'grain_goblin',    'grain@example.com',         '$2y$10$MockHashUser3000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', 'Shooting expired film since 2019.',                     'default.png',1, 0, NULL, NULL, '2026-08-09 08:50:00'),
(@u + 8,  'casper_k',        'casper@example.com',        '$2y$10$MockHashUser4000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', 'Copenhagen. Flea markets every weekend.',                'default.png',     1, 0, NULL, NULL, '2026-08-12 12:00:00'),
(@u + 9,  'tape_hiss',       'tape@example.com',          '$2y$10$MockHashUser5000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', NULL,                                                         'default.png',     1, 0, NULL, NULL, '2026-08-15 16:45:00'),
(@u + 10, 'mette_m',         'mette@example.com',         '$2y$10$MockHashUser6000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', 'Typewriters, stationery, old signage.',                 'default.png',1, 0, NULL, NULL, '2026-08-20 09:30:00'),
(@u + 11, 'spambot42',       'buy.now@spam.example',      '$2y$10$MockHashBan00000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', 'CHEAP WATCHES!!! click my link',                        'default.png',     1, 1, 'Spam / advertising',          '2026-09-14 15:20:00', '2026-09-13 22:10:00'),
(@u + 12, 'oskar_dk',        'oskar@example.com',         '$2y$10$MockHashUser7000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', 'Industrial ruins and brutalist concrete.',              'default.png',     1, 0, NULL, NULL, '2026-09-02 19:00:00'),
(@u + 13, 'neon_nomad',      'neon@example.com',          '$2y$10$MockHashUser8000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', 'Chasing dead neon signs across Europe.',                'default.png', 1, 0, NULL, NULL, '2026-09-10 21:25:00'),
(@u + 14, 'ghost_frame',     'ghost@example.com',         '$2y$10$MockHashBan10000000000uJ3kQ9wXzT5nR2vB7cD1eF4gH6iJ8kL', 'Reposting other people''s work.',                        'default.png',     1, 1, 'Posting photos that are not their own', '2026-10-05 11:00:00', '2026-09-25 13:35:00');

-- ---------------------------------------------------------------
-- Posts  (30)
-- ---------------------------------------------------------------
INSERT INTO posts (id, user_id, filename, title, caption, created_at, front_pinned_at, profile_pinned_at) VALUES
(@p + 1, @u + 1,  'placeholder1.webp', 'Welcome to Deadstock', 'Welcome to Deadstock! Share your forgotten treasures.', '2026-07-25 12:00:00', '2026-07-25 12:05:00', '2026-07-25 12:05:00'),
(@p + 2, @u + 4,  'placeholder2.webp', 'The Old Cinema at 2am', 'Found this old cinema lobby at 2am. The carpet still smells like popcorn.', '2026-08-02 02:14:00', NULL, '2026-08-02 10:00:00'),
(@p + 3, @u + 4,  'placeholder3.webp', 'The Bike Left Behind', 'Somebody locked a bike here in 1994 and never came back.', '2026-08-06 23:40:00', NULL, NULL),
(@p + 4, @u + 5,  'placeholder1.webp', 'Green Phosphor Forever', 'CRT monitor, still works. Green phosphor glow forever.', '2026-08-08 15:22:00', NULL, NULL),
(@p + 5, @u + 6,  'placeholder2.webp', 'Mystery Tapes from the Flea Market', 'Box of unlabeled cassettes from a flea market. Playing them one by one.', '2026-08-10 19:05:00', '2026-08-11 08:00:00', '2026-08-10 20:00:00'),
(@p + 6, @u + 6,  'placeholder3.webp', 'Dust on the Needle', 'Dust on the platter, dust on the needle, perfect.', '2026-08-14 21:30:00', NULL, NULL),
(@p + 7, @u + 7,  'placeholder1.webp', 'Expired Kodak 400', 'Kodak 400, expired in 2003. Shot at 200 and the colours are wild.', '2026-08-16 10:10:00', NULL, '2026-08-16 12:00:00'),
(@p + 8, @u + 7,  'placeholder2.webp', 'Harbour Test Scan', 'Harbour test scan from the roll above.', '2026-08-17 09:45:00', NULL, NULL),
(@p + 9, @u + 8,  'placeholder3.webp', 'Sunday Haul', 'Sunday haul: 3 lamps, 1 clock, no regrets.', '2026-08-23 14:00:00', NULL, NULL),
(@p + 10, @u + 8, 'placeholder1.webp', '1950s Brass Clock', 'Brass clock, 1950s, ticks like new after a bit of oil.', '2026-08-24 11:30:00', NULL, '2026-08-24 12:00:00'),
(@p + 11, @u + 9, 'placeholder2.webp', 'Reel-to-Reel Recorder', 'Reel-to-reel recorder, no tapes though. Where do I find tapes?', '2026-08-27 18:20:00', NULL, NULL),
(@p + 12, @u + 10, 'placeholder3.webp', 'Olivetti Lettera 32', 'Olivetti Lettera 32. The keys have a satisfying clack.', '2026-09-01 13:15:00', NULL, '2026-09-01 14:00:00'),
(@p + 13, @u + 10, 'placeholder1.webp', 'Notebooks from a Closed Shop', 'Unused 1970s notebooks I found in a closed shop.', '2026-09-03 16:40:00', NULL, NULL),
(@p + 14, @u + 4,  'placeholder2.webp', 'Neon with Missing Letters', 'Neon sign with half the letters missing.', '2026-09-05 22:10:00', NULL, NULL),
(@p + 15, @u + 12, 'placeholder3.webp', 'Brutalist Stairwell at Noon', 'Brutalist stairwell, perfect light at noon.', '2026-09-07 12:20:00', '2026-09-08 08:00:00', '2026-09-07 13:00:00'),
(@p + 16, @u + 12, 'placeholder1.webp', 'Closed Textile Factory', 'Closed textile factory. The machines are still there.', '2026-09-12 15:55:00', NULL, NULL),
(@p + 17, @u + 11, 'placeholder2.webp', 'Cheap Watches', 'BUY CHEAP WATCHES HERE!!!', '2026-09-13 22:30:00', NULL, NULL),
(@p + 18, @u + 13, 'placeholder3.webp', 'The Flickering E', 'Hotel sign in Rotterdam. The "E" flickers every few seconds.', '2026-09-16 23:05:00', NULL, '2026-09-17 09:00:00'),
(@p + 19, @u + 13, 'placeholder1.webp', 'Pink Light, Wet Pavement', 'Alley behind a closed bar. Pink light, wet pavement.', '2026-09-20 22:45:00', NULL, NULL),
(@p + 20, @u + 5,  'placeholder2.webp', 'Box of Floppy Disks', 'Box of floppy disks. Anyone know how to read these in 2026?', '2026-09-22 17:30:00', NULL, NULL),
(@p + 21, @u + 14, 'placeholder3.webp', 'Harbour Sunset', 'Sunset over the harbour.', '2026-09-26 19:10:00', NULL, NULL),
(@p + 22, @u + 3,  'placeholder1.webp', 'A Teapot on the Thrift Store Shelf', 'Thrift store shelf of the week. Look at that teapot!', '2026-09-28 12:40:00', NULL, '2026-09-28 13:00:00'),
(@p + 23, @u + 2,  'placeholder2.webp', 'Patina on a Door Handle', 'Door handle with decades of patina.', '2026-10-01 09:50:00', NULL, '2026-10-01 10:00:00'),
(@p + 24, @u + 6,  'placeholder3.webp', 'First Pressing Found', 'Crate digging in a basement. Found a first pressing!', '2026-10-04 20:15:00', NULL, NULL),
(@p + 25, @u + 7,  'placeholder1.webp', 'Back in the Darkroom', 'Back in the darkroom after way too long.', '2026-10-09 21:00:00', NULL, NULL),
(@p + 26, @u + 8,  'placeholder2.webp', 'Bakelite Radio', 'Bakelite radio. Gets exactly one station and it is perfect.', '2026-10-12 14:25:00', NULL, NULL),
(@p + 27, @u + 12, 'placeholder3.webp', 'Grain Silo at Sunrise', 'Grain silo at sunrise.', '2026-10-16 07:30:00', NULL, NULL),
(@p + 28, @u + 13, 'placeholder1.webp', 'Neon After Rain', 'Neon + rain = good combination.', '2026-10-21 22:20:00', '2026-10-22 08:00:00', NULL),
(@p + 29, @u + 10, 'placeholder2.webp', 'Complete Letterpress Set', 'Letterpress wooden blocks, a complete set.', '2026-10-25 11:10:00', NULL, NULL),
(@p + 30, @u + 1,  'placeholder3.webp', 'Halloween Dusty Finds', 'Happy Halloween! Post your spookiest dusty finds this weekend.', '2026-10-31 08:00:00', '2026-10-31 08:05:00', NULL);

-- ---------------------------------------------------------------
-- Likes: random, but reproducible in structure.
-- Each non-banned user has ~40% chance to like each post that is not their own,
-- with a timestamp 0-72h after the post (capped at 2026-10-31 12:00).
-- INSERT IGNORE guards against duplicate (user_id, post_id) pairs.
-- ---------------------------------------------------------------
INSERT IGNORE INTO likes (user_id, post_id, created_at)
SELECT u.id,
       p.id,
       LEAST(p.created_at + INTERVAL FLOOR(RAND() * 72 * 3600) SECOND, '2026-10-31 12:00:00')
FROM users u
JOIN posts p ON p.user_id <> u.id
WHERE u.id > @u AND p.id > @p AND u.is_banned = 0
  AND p.created_at >= u.created_at
  AND RAND() < 0.40;

-- ---------------------------------------------------------------
-- Comments (45)
-- ---------------------------------------------------------------
INSERT INTO comments (post_id, user_id, content, created_at) VALUES
(@p + 1, @u + 4,  'Great idea for a community, thanks for building this!',                    '2026-07-25 13:10:00'),
(@p + 1, @u + 6,  'Excited to be here.',                                                      '2026-08-05 20:30:00'),
(@p + 2, @u + 7,  'This is incredible. How did you get in?',                                  '2026-08-02 08:20:00'),
(@p + 2, @u + 4,  'The back door was open, I swear. Please do not trespass.',                 '2026-08-02 09:00:00'),
(@p + 2, @u + 3,  'Love the lighting. Be careful out there!',                                 '2026-08-02 11:45:00'),
(@p + 3, @u + 12, 'That chain is more rust than metal.',                                      '2026-08-07 07:30:00'),
(@p + 4, @u + 9,  'CRTs have the best contrast, fight me.',                                   '2026-08-08 16:10:00'),
(@p + 4, @u + 8,  'What model is it?',                                                        '2026-08-08 18:00:00'),
(@p + 4, @u + 5,  'Philips, from the late 90s.',                                              '2026-08-08 18:42:00'),
(@p + 5, @u + 9,  'Please report back on what is on them!',                                   '2026-08-10 20:15:00'),
(@p + 5, @u + 6,  'Tape 3 is just someone''s birthday party. Wholesome.',                     '2026-08-12 21:00:00'),
(@p + 5, @u + 10, 'This is the best kind of treasure hunt.',                                  '2026-08-11 09:20:00'),
(@p + 6, @u + 8,  'Clean the needle with a bit of isopropyl, and you are set.',               '2026-08-15 10:05:00'),
(@p + 7, @u + 4,  'Expired film is the only film I trust now.',                               '2026-08-16 14:30:00'),
(@p + 7, @u + 13, 'The colour shift on this is gorgeous.',                                    '2026-08-17 08:00:00'),
(@p + 8, @u + 12, 'Which harbour?',                                                           '2026-08-17 12:00:00'),
(@p + 8, @u + 7,  'Nyhavn, early morning.',                                                   '2026-08-17 12:35:00'),
(@p + 9, @u + 10, 'That lamp on the left, is it for sale? Kidding. Mostly.',                  '2026-08-23 15:20:00'),
(@p + 10, @u + 3,  'Beautiful. 1950s brass is the best.',                                      '2026-08-24 12:45:00'),
(@p + 10, @u + 2,  'Love the patina on the face.',                                             '2026-08-24 17:10:00'),
(@p + 11, @u + 6,  'Check online auctions and local estate sales for blank tapes.',            '2026-08-27 20:00:00'),
(@p + 11, @u + 7,  'Seconding Lena, estate sales are gold.',                                   '2026-08-28 07:55:00'),
(@p + 12, @u + 5,  'The font on those keys. Swoon.',                                           '2026-09-01 14:50:00'),
(@p + 12, @u + 8,  'I have one just like it, they never die.',                                 '2026-09-02 09:00:00'),
(@p + 13, @u + 3,  'Unused paper from the 70s? That is a gold mine.',                          '2026-09-03 18:20:00'),
(@p + 14, @u + 13, 'I have been looking for this sign for ages!',                              '2026-09-06 08:30:00'),
(@p + 14, @u + 4,  'It is right behind the old bus depot.',                                    '2026-09-06 09:10:00'),
(@p + 15, @u + 4,  'The shadows here are perfect.',                                            '2026-09-07 15:00:00'),
(@p + 15, @u + 2,  'Pinned to the front page, great shot.',                                    '2026-09-08 08:05:00'),
(@p + 16, @u + 7,  'Do you know what they used to make there?',                                '2026-09-12 19:30:00'),
(@p + 16, @u + 12, 'Mostly wool coats, according to a neighbour.',                             '2026-09-12 20:05:00'),
(@p + 17, @u + 3,  'Reported and removed from the front page. Spam.',                          '2026-09-14 09:00:00'),
(@p + 18, @u + 4,  'Rotterdam never disappoints.',                                             '2026-09-17 07:45:00'),
(@p + 18, @u + 12, 'That flicker really sells the atmosphere.',                                '2026-09-17 12:10:00'),
(@p + 19, @u + 9,  'This looks like a movie still.',                                           '2026-09-21 10:15:00'),
(@p + 20, @u + 9,  'A USB floppy drive on eBay is about 15 euros.',                            '2026-09-22 18:40:00'),
(@p + 20, @u + 10, 'You might find your old school essays.',                                   '2026-09-23 08:20:00'),
(@p + 22, @u + 6,  'That teapot is begging to come home with me.',                             '2026-09-28 14:00:00'),
(@p + 22, @u + 8,  'Where is this shop?',                                                      '2026-09-28 16:30:00'),
(@p + 24, @u + 9,  'A first pressing?! Jealous.',                                              '2026-10-04 21:00:00'),
(@p + 25, @u + 13, 'The red safelight glow is so cozy.',                                       '2026-10-10 08:00:00'),
(@p + 26, @u + 10, 'One station is all you need.',                                             '2026-10-12 15:15:00'),
(@p + 27, @u + 4,  'Silos at sunrise are underrated.',                                         '2026-10-16 09:40:00'),
(@p + 28, @u + 7,  'Wet pavement reflections are the best part of neon.',                      '2026-10-22 08:30:00'),
(@p + 30, @u + 8,  'Challenge accepted, I have a very spooky doll head.',                      '2026-10-31 09:30:00');

-- ---------------------------------------------------------------
-- Follows (40)
-- ---------------------------------------------------------------
INSERT INTO follows (follower_id, following_id, created_at) VALUES
(@u + 4, @u + 1,  '2026-08-01 19:00:00'),
(@u + 5, @u + 1,  '2026-08-03 14:10:00'),
(@u + 6, @u + 1,  '2026-08-05 20:20:00'),
(@u + 7, @u + 1,  '2026-08-09 09:00:00'),
(@u + 8, @u + 1,  '2026-08-12 12:05:00'),
(@u + 9, @u + 1,  '2026-08-15 16:50:00'),
(@u + 10, @u + 1,  '2026-08-20 09:35:00'),
(@u + 12, @u + 1,  '2026-09-02 19:05:00'),
(@u + 13, @u + 1,  '2026-09-10 21:30:00'),
(@u + 4, @u + 7,  '2026-08-10 10:00:00'),
(@u + 7, @u + 4,  '2026-08-10 10:30:00'),
(@u + 4, @u + 13, '2026-09-11 08:00:00'),
(@u + 13, @u + 4,  '2026-09-11 08:15:00'),
(@u + 6, @u + 9,  '2026-08-16 11:00:00'),
(@u + 9, @u + 6,  '2026-08-16 11:20:00'),
(@u + 6, @u + 8,  '2026-08-13 17:00:00'),
(@u + 8, @u + 6,  '2026-08-13 17:30:00'),
(@u + 8, @u + 10, '2026-08-21 12:00:00'),
(@u + 10, @u + 8,  '2026-08-21 12:40:00'),
(@u + 5, @u + 4,  '2026-08-04 09:00:00'),
(@u + 5, @u + 6,  '2026-08-06 10:00:00'),
(@u + 7, @u + 12, '2026-09-03 11:00:00'),
(@u + 12, @u + 7,  '2026-09-03 11:15:00'),
(@u + 12, @u + 4,  '2026-09-04 18:00:00'),
(@u + 13, @u + 12, '2026-09-12 20:00:00'),
(@u + 12, @u + 13, '2026-09-12 20:30:00'),
(@u + 9, @u + 10, '2026-08-28 10:00:00'),
(@u + 10, @u + 6,  '2026-08-29 14:00:00'),
(@u + 3, @u + 4,  '2026-08-02 12:00:00'),
(@u + 3, @u + 6,  '2026-08-11 12:00:00'),
(@u + 3, @u + 12, '2026-09-08 09:00:00'),
(@u + 2, @u + 7,  '2026-08-17 09:00:00'),
(@u + 2, @u + 13, '2026-09-17 10:00:00'),
(@u + 2, @u + 3,  '2026-07-23 08:00:00'),
(@u + 3, @u + 2,  '2026-07-23 08:10:00'),
(@u + 1, @u + 2,  '2026-07-22 10:20:00'),
(@u + 1, @u + 3,  '2026-07-22 11:45:00'),
(@u + 1, @u + 4,  '2026-08-02 10:00:00'),
(@u + 1, @u + 6,  '2026-08-11 08:30:00'),
(@u + 1, @u + 12, '2026-09-08 08:00:00');