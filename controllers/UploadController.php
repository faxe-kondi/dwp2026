<?php
class UploadController
{
    public static function show(): void
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();
        Auth::requireLogin();
        require_once dirname(__DIR__) . '/support/Csrf.php';
        require_once dirname(__DIR__) . '/services/PostService.php';

        $errors = [];
        $title = '';
        $caption = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::verifyOrFail();

            $submittedTitle = $_POST['title'] ?? '';
            $title = is_string($submittedTitle) ? trim($submittedTitle) : '';
            $submittedCaption = $_POST['caption'] ?? '';
            $caption = is_string($submittedCaption) ? trim($submittedCaption) : '';

            try {
                $postId = PostService::createPost(
                    (int) Auth::user()['id'],
                    $title,
                    $caption,
                    $_FILES['photo'] ?? null
                );
                header('Location: post.php?id=' . $postId);
                exit;
            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            } catch (RateLimitException $e) {
                $errors[] = $e->getMessage();
            } catch (RuntimeException $e) {
                error_log('Post upload failed: ' . $e->getMessage());
                $errors[] = 'Something went wrong saving your post. Please try again.';
            } catch (PDOException $e) {
                error_log('Post creation failed: ' . $e->getMessage());
                $errors[] = 'Something went wrong saving your post. Please try again.';
            }
        }

        require_once dirname(__DIR__) . '/support/View.php';
        View::renderPage('posts/upload', compact('errors', 'title', 'caption'));
    }
}
