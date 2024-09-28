<?php
require_once __DIR__ . '/config.php';
function getComments($postSlug) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE post_slug = ? ORDER BY created_at DESC");
    $stmt->execute([$postSlug]);
    return $stmt->fetchAll();
}

function addComment($postSlug, $author, $content) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO comments (post_slug, author, content) VALUES (?, ?, ?)");
    return $stmt->execute([$postSlug, $author, $content]);
}