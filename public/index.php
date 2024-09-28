<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/comments.php';

use Symfony\Component\Yaml\Yaml;

error_reporting(E_ALL);
ini_set('display_errors', 1);

function debug($var, $label = '') {
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    error_log($label . ' ' . $output);
}

function parseMarkdownFile($filename) {
    $content = file_get_contents($filename);
    debug($content, "Raw file content:");
    $pattern = '/^---\s*\n(.*?)\n---\s*\n(.*)/s';
    if (preg_match($pattern, $content, $matches)) {
        try {
            $frontMatter = Yaml::parse($matches[1]);
            debug($frontMatter, "Front matter:");
        } catch (Exception $e) {
            error_log("Error parsing front matter: " . $e->getMessage());
            $frontMatter = [];
        }
        $markdownContent = $matches[2];
    } else {
        $frontMatter = [];
        $markdownContent = $content;
    }
    debug($markdownContent, "Markdown content:");
    return [$frontMatter, $markdownContent];
}

function adjustImagePaths($content) {
    return preg_replace('/!\[(.*?)\]\((.*?)\)/', '![$1](/LMS/app/blog-project/public/$2)', $content);
}

function createSearchIndex() {
    $posts = glob(__DIR__ . '/../src/posts/*.md');
    $searchIndex = [];
    foreach ($posts as $post) {
        [$frontMatter, $content] = parseMarkdownFile($post);
        $fileCreationTime = filectime($post);
        $searchIndex[] = [
            'title' => $frontMatter['title'] ?? basename($post, '.md'),
            'date' => date('Y-m-d', $fileCreationTime),
            'tags' => $frontMatter['tags'] ?? [],
            'category' => $frontMatter['category'] ?? '',
            'content' => strip_tags(Parsedown::instance()->text($content)),
            'slug' => basename($post, '.md')
        ];
    }
    file_put_contents(__DIR__ . '/../cache/search_index.json', json_encode($searchIndex));
    return $searchIndex;
}

function getAllPosts() {
    $posts = glob(__DIR__ . '/../src/posts/*.md');
    $allPosts = [];
    foreach ($posts as $post) {
        [$frontMatter, ] = parseMarkdownFile($post);
        $slug = basename($post, '.md');
        if ($slug !== 'welcome' && $slug !== 'about') {
            $fileCreationTime = filectime($post);
            $date = date('Y-m-d', $fileCreationTime);
            
            error_log("Post: $slug, Date: $date");
            $allPosts[] = [
                'title' => $frontMatter['title'] ?? $slug,
                'slug' => $slug,
                'date' => $date
            ];
        }
    }
    usort($allPosts, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    return $allPosts;
}

function renderPost($post, $slug) {
    $postFile = __DIR__ . "/../src/posts/{$slug}.md";
    $fileCreationTime = filectime($postFile);
    $postDate = date('F j, Y', $fileCreationTime);

    ob_start();
    echo "<h1>" . htmlspecialchars($post['title']) . "</h1>";
    echo "<p>Published on: " . htmlspecialchars($postDate) . "</p>";
    echo "<div class='post-content'>" . $post['content'] . "</div>";
    
    // Display comments
    echo "<div class='comments-section'>";
    echo "<h2>Comments</h2>";
    $comments = getComments($slug);
    if (empty($comments)) {
        echo "<p>No comments yet.</p>";
    } else {
        foreach ($comments as $comment) {
            echo "<div class='comment'>";
            echo "<p><strong>" . htmlspecialchars($comment['author']) . "</strong> said:</p>";
            echo "<p>" . htmlspecialchars($comment['content']) . "</p>";
            echo "<p class='comment-meta'>Posted on: " . $comment['created_at'] . "</p>";
            echo "</div>";
        }
    }
    echo "</div>";
    
    // Comment form
    echo "<div class='comment-form'>";
    echo "<h3>Add a Comment</h3>";
    echo "<form method='post' action='add_comment.php'>";
    echo "<input type='hidden' name='post_slug' value='" . htmlspecialchars($slug) . "'>";
    echo "<label for='author'>Name:</label>";
    echo "<input type='text' id='author' name='author' required>";
    echo "<label for='content'>Comment:</label>";
    echo "<textarea id='content' name='content' required></textarea>";
    echo "<input type='submit' value='Submit Comment'>";
    echo "</form>";
    echo "</div>";
    
    return ob_get_clean();
}

// Main logic
$post_slug = $_GET['post'] ?? 'welcome';
$post_slug = basename($post_slug);
debug($post_slug, "Post slug:");

$post_file = __DIR__ . "/../src/posts/{$post_slug}.md";
$cache_file = __DIR__ . "/../cache/{$post_slug}.html";
debug($post_file, "Post file path:");

$cache_dir = __DIR__ . "/../cache";
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

// Create or update the search index
$searchIndex = createSearchIndex();

// Check if a valid cached version exists
if (file_exists($cache_file) && filemtime($cache_file) > filemtime($post_file)) {
    $html_content = file_get_contents($cache_file);
    debug($html_content, "Cached HTML content:");
    [$frontMatter, ] = parseMarkdownFile($post_file); // Get front matter for title
} else {
    if (file_exists($post_file)) {
        [$frontMatter, $markdownContent] = parseMarkdownFile($post_file);
        $parsedown = new Parsedown();
        $html_content = $parsedown->text($markdownContent);
        debug($html_content, "Parsed HTML content:");
        
        // Cache the parsed content
        if (is_writable($cache_dir)) {
            file_put_contents($cache_file, $html_content);
        } else {
            error_log("Cache directory is not writable: {$cache_dir}");
        }
    } else {
        error_log("Post file not found: {$post_file}");
        $html_content = "<h1>Post Not Found</h1>";
        $frontMatter = ['title' => 'Post Not Found'];
    }
}

$frontMatter = $frontMatter ?? ['title' => 'My Minimalist Blog'];

// If this is the welcome page, add the list of all posts after the content
if ($post_slug === 'welcome') {
    $allPosts = getAllPosts();
    $postList = "<h2>Recent Posts</h2>";
    foreach ($allPosts as $post) {
        $postDate = date('F j, Y', strtotime($post['date']));
        error_log("Displaying post: {$post['title']}, Date: $postDate");
        $postList .= "
        <div class='card mb-3'>
            <div class='card-body'>
                <h3 class='card-title'><a href='/blog-project/public/index.php?post={$post['slug']}'>{$post['title']}</a></h3>
                <p class='card-text'><small class='text-muted'>Published on {$postDate}</small></p>
                <a href='/blog-project/public/index.php?post={$post['slug']}' class='btn btn-primary'>Read More</a>
            </div>
        </div>";
    }
    $html_content .= $postList;
}

// Set title and content for layout
$title = $frontMatter['title'] ?? 'My Minimalist Blog';

// Add comments and comment form to the content
if (isset($_GET['post']) && $post_slug !== 'welcome') {
    $content = renderPost(['title' => $title, 'content' => $html_content], $post_slug);
} else {
    $content = $html_content;
}

// Debug: Output the content
error_log("Content before rendering: " . substr($content, 0, 500)); // Log the first 500 characters

// Include the layout template
include __DIR__ . '/../src/templates/layout.php';

debug($content, "Final content to be rendered:");