<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/comments.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postSlug = $_POST['post_slug'] ?? '';
    $author = $_POST['author'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if ($postSlug && $author && $content) {
        if (addComment($postSlug, $author, $content)) {
            header("Location: index.php?post=" . urlencode($postSlug) . "#comments");
            exit;
        } else {
            $error = "Failed to add comment. Please try again.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

// If we get here, there was an error
$title = "Error Adding Comment";
ob_start();
echo "<h1>Error</h1>";
echo "<p>" . htmlspecialchars($error) . "</p>";
echo "<p><a href='javascript:history.back()'>Go back</a></p>";
$content = ob_get_clean();

include __DIR__ . '/../src/templates/layout.php';
