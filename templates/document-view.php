<?php
if (!defined('ABSPATH')) exit;

/** @var \WP_Post $post */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($post->post_title); ?></title>
    <?php wp_head();  ?>
</head>
<body <?php body_class('shared-document-view'); ?>>

<article class="document-container">
    <header class="document-header">
        <h1><?php echo esc_html($post->post_title); ?></h1>
    </header>

    <div class="document-content">
        <?php echo apply_filters('the_content', $post->post_content); ?>
    </div>
</article>

<?php wp_footer(); ?>
</body>
</html>