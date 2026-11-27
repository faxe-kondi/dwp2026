<?php
class PostController
{
    public static function show(): void
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();
        require_once dirname(__DIR__) . '/support/Csrf.php';
        require dirname(__DIR__) . '/helpers/bootstrap.php';
        require_once dirname(__DIR__) . '/models/Post.php';
        require_once dirname(__DIR__) . '/models/Comment.php';
        require_once dirname(__DIR__) . '/models/User.php';
        require_once dirname(__DIR__) . '/support/View.php';

        $requestedId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $id = $requestedId !== false && $requestedId !== null && $requestedId > 0 ? $requestedId : 0;
        $currentUser = Auth::user();
        $realRow = Post::findById($id, $currentUser ? (int) $currentUser['id'] : null);

        if (!$realRow) {
            http_response_code(404);
            View::renderPage('post/unavailable', [
                'title' => 'Post not found',
                'message' => 'This post may have been deleted or is no longer available.',
                'actionUrl' => 'index.php?route=browse',
                'actionLabel' => 'Browse posts',
            ]);
            exit;
        }

        $isPostOwner = Auth::check() && (int) $realRow['user_id'] === (int) Auth::user()['id'];
        $blockedPostState = $currentUser
            ? User::getBlockState((int) $currentUser['id'], (int) $realRow['user_id'])
            : ['blocked_by_viewer' => false, 'blocked_viewer' => false];

        if (
            $blockedPostState['blocked_viewer']
            || ($blockedPostState['blocked_by_viewer'] && $_SERVER['REQUEST_METHOD'] === 'POST')
        ) {
            http_response_code(404);
            View::renderPage('post/unavailable', [
                'title' => 'Post unavailable',
                'message' => 'This post is unavailable.',
                'actionUrl' => 'index.php',
                'actionLabel' => 'Return to home',
            ]);
            exit;
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && in_array($_POST['action'] ?? null, ['pin_front', 'pin_profile'], true)
        ) {
            Auth::requireLogin();
            Csrf::verifyOrFail();

            $pinAction = $_POST['action'];
            if ($pinAction === 'pin_front' && !Auth::canModerate()) {
                http_response_code(403);
                exit('Only moderators and the site owner can pin posts to the homepage.');
            }
            if ($pinAction === 'pin_profile' && !$isPostOwner) {
                http_response_code(404);
                exit('Post not found.');
            }

            $pinColumn = $pinAction === 'pin_front' ? 'front_pinned_at' : 'profile_pinned_at';
            $shouldPin = empty($realRow[$pinColumn]);
            try {
                $updated = $pinAction === 'pin_front'
                    ? Post::setFrontPinned($id, $shouldPin)
                    : Post::setProfilePinnedOwned($id, (int) Auth::user()['id'], $shouldPin);
            } catch (PDOException $e) {
                error_log('Post pin update failed: ' . $e->getMessage());
                http_response_code(500);
                exit('Something went wrong updating this post. Please try again.');
            }

            if (!$updated) {
                http_response_code(404);
                exit('Post not found.');
            }

            $pinStatus = $pinAction === 'pin_front' ? 'homepage' : 'profile';
            $pinStatus .= $shouldPin ? '_pinned' : '_unpinned';
            header('Location: index.php?route=post&id=' . $id . '&pin=' . $pinStatus);
            exit;
        }

        $errors = [];
        $commentText = '';
        $editingCommentId = null;

        $requestedEditCommentId = filter_var($_GET['edit_comment'] ?? null, FILTER_VALIDATE_INT);
        if ($requestedEditCommentId !== false && $requestedEditCommentId !== null && $requestedEditCommentId > 0) {
            $editingCommentId = $requestedEditCommentId;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
            Csrf::verifyOrFail();
            $isPostOwner = $currentUser && (int) $realRow['user_id'] === (int) $currentUser['id'];
            if (!$isPostOwner && !Auth::canModerate()) {
                http_response_code(404);
                exit('Post not found.');
            }

            try {
                $deletedFilename = $isPostOwner
                    ? Post::deleteOwned($id, (int) $currentUser['id'])
                    : Post::deleteByModerator($id);
            } catch (PDOException $e) {
                error_log('Post deletion failed: ' . $e->getMessage());
                http_response_code(500);
                exit('Something went wrong deleting your post. Please try again.');
            }
            if ($deletedFilename === null) {
                http_response_code(404);
                exit('Post not found.');
            }

            $imagePath = dirname(__DIR__) . '/uploads/posts/' . basename($deletedFilename);
            $profileUrl = ds_user_profile_url($realRow['username']) . '?post_deleted=1';
            if (basename($deletedFilename) !== $deletedFilename || (is_file($imagePath) && !unlink($imagePath))) {
                error_log('Post image cleanup failed after deleting post #' . $id . '.');
                header('Location: ' . $profileUrl . '&cleanup_failed=1');
                exit;
            }
            header('Location: ' . $profileUrl);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'comment') {
            Auth::requireLogin();
            Csrf::verifyOrFail();

            $submittedComment = $_POST['content'] ?? '';
            if (!is_string($submittedComment)) {
                $errors[] = 'Enter a valid comment.';
            } else {
                $commentText = trim($submittedComment);
                try {
                    Comment::create((int) $realRow['id'], (int) Auth::user()['id'], $commentText);
                    header('Location: index.php?route=post&id=' . (int) $realRow['id'] . '&comment=added#comments');
                    exit;
                } catch (InvalidArgumentException $e) {
                    $errors[] = $e->getMessage();
                } catch (RateLimitException $e) {
                    $errors[] = $e->getMessage();
                } catch (PDOException $e) {
                    error_log('Comment creation failed: ' . $e->getMessage());
                    $errors[] = 'Something went wrong posting your comment. Please try again.';
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_comment') {
            Auth::requireLogin();
            Csrf::verifyOrFail();

            $commentId = filter_var($_POST['comment_id'] ?? null, FILTER_VALIDATE_INT);
            if ($commentId === false || $commentId === null || $commentId < 1) {
                http_response_code(404);
                exit('Comment not found.');
            }

            $editingCommentId = $commentId;
            $submittedComment = $_POST['content'] ?? '';
            if (!is_string($submittedComment)) {
                $errors[] = 'Enter a valid comment.';
            } else {
                $commentText = trim($submittedComment);
                try {
                    if (!Comment::updateOwned($commentId, (int) $realRow['id'], (int) Auth::user()['id'], $commentText)) {
                        http_response_code(404);
                        exit('Comment not found.');
                    }
                    header('Location: index.php?route=post&id=' . (int) $realRow['id'] . '&comment=updated#comments');
                    exit;
                } catch (InvalidArgumentException $e) {
                    $errors[] = $e->getMessage();
                } catch (PDOException $e) {
                    error_log('Comment update failed: ' . $e->getMessage());
                    $errors[] = 'Something went wrong updating your comment. Please try again.';
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_comment') {
            Auth::requireLogin();
            Csrf::verifyOrFail();

            $commentId = filter_var($_POST['comment_id'] ?? null, FILTER_VALIDATE_INT);
            if ($commentId === false || $commentId === null || $commentId < 1) {
                http_response_code(404);
                exit('Comment not found.');
            }

            try {
                $deleted = Auth::canModerate()
                    ? Comment::deleteByModerator($commentId, (int) $realRow['id'])
                    : Comment::deleteOwned($commentId, (int) $realRow['id'], (int) $currentUser['id']);
                if (!$deleted) {
                    http_response_code(404);
                    exit('Comment not found.');
                }
            } catch (PDOException $e) {
                error_log('Comment deletion failed: ' . $e->getMessage());
                http_response_code(500);
                exit('Something went wrong deleting your comment. Please try again.');
            }

            header('Location: index.php?route=post&id=' . (int) $realRow['id'] . '&comment=deleted#comments');
            exit;
        }

        $post = ds_post_view($realRow);
        $isOwner = $isPostOwner;
        $canDeletePost = $isOwner || Auth::canModerate();
        $isFollowingPoster = $currentUser
            && !$isOwner
            && !($blockedPostState['blocked_by_viewer'] || $blockedPostState['blocked_viewer'])
            && User::isFollowing((int) $currentUser['id'], (int) $realRow['user_id']);

        $postBlockedForViewer = $blockedPostState['blocked_by_viewer'] || $blockedPostState['blocked_viewer'];
        $post_comments = $blockedPostState['blocked_by_viewer'] ? [] : array_map(
            static function (array $comment): array {
                return [
                    'id' => (int) $comment['id'],
                    'username' => $comment['username'],
                    'user_id' => (int) $comment['user_id'],
                    'avatar_url' => ds_user_avatar_url(['username' => $comment['username'], 'avatar' => $comment['avatar']], 38),
                    'time' => ds_time_ago($comment['created_at']),
                    'content' => $comment['content'],
                    'text' => $comment['content'],
                ];
            },
            Comment::findByPost((int) $post['id'])
        );

        $pinNotices = [
            'homepage_pinned' => 'Post pinned to the homepage.',
            'homepage_unpinned' => 'Post unpinned from the homepage.',
            'profile_pinned' => 'Post pinned to your profile.',
            'profile_unpinned' => 'Post unpinned from your profile.',
        ];
        $requestedPinNotice = $_GET['pin'] ?? '';
        $pinNotice = is_string($requestedPinNotice) ? ($pinNotices[$requestedPinNotice] ?? null) : null;

        View::renderPage('post/show', compact(
            'post',
            'realRow',
            'blockedPostState',
            'isOwner',
            'canDeletePost',
            'currentUser',
            'isFollowingPoster',
            'pinNotice',
            'errors',
            'post_comments',
            'editingCommentId',
            'commentText'
        ));
    }
}
