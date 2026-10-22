CREATE DATABASE IF NOT EXISTS deadstock
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE deadstock;

CREATE TABLE roles (
    id      INT         NOT NULL PRIMARY KEY,
    name    VARCHAR(20) NOT NULL UNIQUE
);
INSERT INTO roles (id, name) VALUES
    (1, 'user'),
    (2, 'moderator'),
    (3, 'owner');

CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(30)  NOT NULL UNIQUE,
    email           VARCHAR(190) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    bio             TEXT         NULL,
    avatar          VARCHAR(255) NOT NULL DEFAULT 'default.png',
    role_id         INT          NOT NULL DEFAULT 1,
    is_banned       BOOLEAN      NOT NULL DEFAULT 0,
    ban_reason      VARCHAR(255) NULL,
    banned_at       DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE posts (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT          NOT NULL,
    filename            VARCHAR(255) NOT NULL,
    caption             TEXT         NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    front_pinned_at     DATETIME     NULL,
    profile_pinned_at   DATETIME     NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE likes (
    user_id     INT      NOT NULL,
    post_id     INT      NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

CREATE TABLE comments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT          NOT NULL,
    user_id     INT          NOT NULL,
    content     VARCHAR(500) NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE follows (
    follower_id     INT      NOT NULL,
    following_id    INT      NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (follower_id, following_id),
    FOREIGN KEY (follower_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE site_settings (
    setting_key     VARCHAR(50) NOT NULL PRIMARY KEY,
    setting_value   TEXT        NOT NULL
);
INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('description',  ''),
    ('rules',        ''),
    ('contact',      ''),
    ('color_primary',     '#FF5A1F'),
    ('color_secondary',   '#D6FF3F');
