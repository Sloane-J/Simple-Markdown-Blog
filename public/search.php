<?php
require_once __DIR__ . '/../vendor/autoload.php';

function generateSearchIndex() {
    $posts = getPosts();
    $searchIndex = [];

    foreach ($posts as $post) {
        $searchIndex[] = [
            'title' => $post['title'],
            'content' => strip_tags($post['content']),
            'slug' => $post['slug'],
            'date' => $post['date'],
            'tags' => $post['tags'] ?? [],
            'category' => $post['category'] ?? '',
        ];
    }

    $indexFile = __DIR__ . '/../cache/search_index.json';
    file_put_contents($indexFile, json_encode($searchIndex));

    return $searchIndex;
}

function loadSearchIndex() {
    $indexFile = __DIR__ . '/../cache/search_index.json';
    if (!file_exists($indexFile)) {
        return generateSearchIndex();
    }
    $indexContent = file_get_contents($indexFile);
    if ($indexContent === false) {
        return generateSearchIndex();
    }
    $searchIndex = json_decode($indexContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return generateSearchIndex();
    }
    return $searchIndex;
}

function searchPosts($query, $searchIndex) {
    if (empty($query)) {
        return [];
    }
    $query = strtolower($query);
    $results = array_filter($searchIndex, function($post) use ($query) {
        $titleMatch = stripos($post['title'], $query) !== false;
        $contentMatch = stripos($post['content'], $query) !== false;
        $tagMatch = !empty($post['tags']) && array_reduce($post['tags'], function($carry, $tag) use ($query) {
            return $carry || (stripos(strtolower($tag), $query) !== false);
        }, false);
        $categoryMatch = !empty($post['category']) && stripos($post['category'], $query) !== false;
        
        return $titleMatch || $contentMatch || $tagMatch || $categoryMatch;
    });
    return $results;
}

function getPosts() {
    $postsDir = __DIR__ . '/../posts';
    $posts = [];
    
    if (!is_dir($postsDir)) {
        return $posts;
    }
    
    $files = glob($postsDir . '/*.md');
    
    foreach ($files as $file) {
        if (!is_readable($file)) {
            continue;
        }
        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }
        $parsed = parseMarkdown($content);
        $posts[] = [
            'title' => $parsed['title'],
            'content' => $parsed['content'],
            'slug' => basename($file, '.md'),
            'date' => $parsed['date'],
            'tags' => $parsed['tags'] ?? [],
            'category' => $parsed['category'] ?? '',
        ];
    }
    
    return $posts;
}

function parseMarkdown($content) {
    // This is a very basic parser. You should use a proper Markdown parser in production.
    $lines = explode("\n", $content);
    $title = trim($lines[0], "# \t\n\r\0\x0B");
    $content = implode("\n", array_slice($lines, 1));
    
    return [
        'title' => $title,
        'content' => $content,
        'date' => date('Y-m-d'),
        'tags' => ['example', 'tag'],
        'category' => 'Example Category',
    ];
}

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Load search index
$searchIndex = loadSearchIndex();

// Perform search
$results = searchPosts($query, $searchIndex);

// Start output buffering
ob_start();

// Output search results
echo "<h1>Search Results for \"" . htmlspecialchars($query) . "\"</h1>";

if (empty($results)) {
    echo "<p>No results found.</p>";
} else {
    echo "<ul>";
    foreach ($results as $post) {
        echo "<li>";
        echo "<a href='/blog-project/public/index.php?post=" . htmlspecialchars($post['slug']) . "'>" . htmlspecialchars($post['title']) . "</a>";
        // Format the date if it's a valid timestamp, otherwise don't display it
        if (!empty($post['date']) && is_numeric($post['date'])) {
            $formattedDate = date('F j, Y', $post['date']);
            echo "<small> (" . htmlspecialchars($formattedDate) . ")</small>";
        }
        echo "<p>" . htmlspecialchars(substr($post['content'], 0, 150)) . "...</p>";
        if (!empty($post['tags'])) {
            echo "<p>Tags: " . implode(', ', array_map('htmlspecialchars', $post['tags'])) . "</p>";
        }
        if (!empty($post['category'])) {
            echo "<p>Category: " . htmlspecialchars($post['category']) . "</p>";
        }
        echo "</li>";
    }
    echo "</ul>";
}

// Get the buffered content
$content = ob_get_clean();

// Set title for layout
$title = 'Search Results for "' . htmlspecialchars($query) . '"';

// Include the layout template
include __DIR__ . '/../src/templates/layout.php';