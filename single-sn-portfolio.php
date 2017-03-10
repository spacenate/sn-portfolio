<?php
/**
 * Single article template for SN Portfolio
 */

if (!defined('ABSPATH')) {
    exit;
}

// Page header + navigation
get_header();

// Page content
the_post();
$post_ID = get_the_ID();
$post_custom = get_post_custom($post_ID);
$post_source = $post_custom['sn_portfolio_media_source'][0];

$other_posts_count = 4;

// Get other posts of the same media type first
$query_first_vars = array(
    'post__not_in' => array($post_ID),
    'post_type' => 'sn_portfolio',
    'post_status' => 'publish',
    'posts_per_page' => $other_posts_count,
    'meta_key' => 'sn_portfolio_featured_order',
    'orderby' => 'meta_value_num',
    'order' => 'ASC',
    'tax_query' => array(
        array(
            'taxonomy' => 'sn_portfolio_media_type',
            'field'    => 'slug',
            'terms'    => wp_get_object_terms($post_ID, 'sn_portfolio_media_type')[0]->slug,
        ),
        array(
            'taxonomy' => 'sn_portfolio_featured',
            'field'    => 'slug',
            'terms'    => '1'
        )
    )
);

// Fill in with all posts
$query_second_vars = array(
    'post__not_in' => array($post_ID),
    'post_type' => 'sn_portfolio',
    'post_status' => 'publish',
    'posts_per_page' => $other_posts_count,
    'meta_key' => 'sn_portfolio_featured_order',
    'orderby' => 'meta_value_num',
    'order' => 'ASC',
    'tax_query' => array(
        array(
            'taxonomy' => 'sn_portfolio_featured',
            'field'    => 'slug',
            'terms'    => '1'
        )
    )
);

?>
<main class="content-area">
    <div class="site-main">
        <h1><?php single_post_title(); ?></h1>
        <?php the_content(); ?>
    </div>
</main>
<aside class="widget-area">
    <?php if (!empty($post_source)): ?>
        <h2 class="sn-sidebar-header">Source</h2>
        <a href="<?= $post_source ?>" target="_blank"><?= $post_source ?> ([]->)</a>
    <?php endif; ?>
    <h2 class="sn-sidebar-header">Other Excellent Work</h2>
    <ul>
        <?php
        $query = new WP_Query($query_first_vars);

        if ($query->found_posts < $other_posts_count) {
            $query_second_vars['posts_per_page'] = $other_posts_count - $query->found_posts;
        }
        else {
            unset($query_second_vars);
        }

        while ($query->have_posts()) {
            $query->the_post();

            // Don't pull the same posts twice
            if (isset($query_second_vars)) {
                $query_second_vars['post__not_in'][] = get_the_ID();
            }

            // require_once('vendor/autoload.php');
            // eval(\Psy\sh());

            ?>
            <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?> </a></li>
            <?php

            // When there is a second query, and we are done with the first query, start the second
            if (isset($query_second_vars) && ($query->current_post + 1 >= $query->post_count)) {
                $query = new WP_Query($query_second_vars);
                unset($query_second_vars);
            }
        }
        ?>
    </ul>
</aside>
<?php

// Page footer
get_footer();
