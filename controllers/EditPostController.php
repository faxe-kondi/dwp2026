<?php
class EditPostController
{
    public static function edit(): void
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();
        Auth::requireLogin();
        require_once dirname(__DIR__) . '/support/Csrf.php';
        require_once dirname(__DIR__) . '/models/Post.php';
        require_once dirname(__DIR__) . '/services/PostService.php';
        require_once dirname(__DIR__) . '/helpers/bootstrap.php';

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $userId = (int) Auth::user()['id'];
        $row = $id > 0 ? Post::findById($id) : null;

        if (!$row || (int) $row['user_id'] !== $userId) {
            http_response_code(404);
            exit('Post not found.');
        }

        $errors = [];
        $title = $row['title'];
        $caption = $row['caption'] ?? '';
        $notice = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::verifyOrFail();
            $submittedTitle = $_POST['title'] ?? '';
            $title = is_string($submittedTitle) ? trim($submittedTitle) : '';
            $submittedCaption = $_POST['caption'] ?? '';
            $caption = is_string($submittedCaption) ? trim($submittedCaption) : '';

            try {
                $result = PostService::updatePost(
                    $id,
                    $userId,
                    $row['filename'],
                    $title,
                    $caption,
                    $_FILES['photo'] ?? null
                );
                if (!$result['updated']) {
                    http_response_code(404);
                    exit('Post not found.');
                }

                $notice = $result['notice'];
                if ($notice === '') {
                    header('Location: index.php?route=post&id=' . $id);
                    exit;
                }
                $row = Post::findById($id);
            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            } catch (PDOException $e) {
                error_log('Post edit failed: ' . $e->getMessage());
                $errors[] = 'Something went wrong saving your changes. Please try again.';
            } catch (RateLimitException $e) {
                $errors[] = $e->getMessage();
            } catch (RuntimeException $e) {
                error_log('Post edit failed: ' . $e->getMessage());
                $errors[] = 'Something went wrong saving your changes. Please try again.';
            }
        }

        require_once dirname(__DIR__) . '/support/View.php';
        View::renderPage('posts/edit', compact('id', 'row', 'errors', 'title', 'caption', 'notice'));
    }
}
