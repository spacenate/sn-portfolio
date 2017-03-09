<?php
/**
 * Archive template for SN Portfolio
 */

if (!defined('ABSPATH')) {
    exit;
}

$query = new WP_Query(array(
    'post_type' => 'sn_portfolio',
    'post_status' => 'publish',
    'posts_per_page' => -1,
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
));

get_header();

?>
<div class="container">
    <?php
    $portfolio_item_counter = 0;

    while ($query->have_posts()) {
        $query->the_post();
        print(getPortfolioPieceMarkup(++$portfolio_item_counter, $query->post_count));
    }
    ?>
</div>
<?php

wp_reset_postdata();
get_footer();


/**
 * Generate HTML markup for a single portfolio piece.
 *
 * @global whatever WP nonsense allows get_the_ID()/get_the_content()/etc
 *         to work as expected
 * @return string HTML markup
 */
function getPortfolioPieceMarkup($portfolio_item_number, $total_items_count) {
    $media_type      = wp_get_post_terms(get_the_ID(), 'sn_portfolio_media_type')[0];
    $media_type_slug = is_a($media_type, 'WP_Term') ? $media_type->slug : null;
    $media_type_name = is_a($media_type, 'WP_Term') ? $media_type->name : null;
    $media_source    = post_custom('sn_portfolio_media_source');
    $featured_order  = (int) post_custom('sn_portfolio_featured_order');

    $post_title = post_custom('sn_portfolio_title');
    if (empty($post_title)) {
        $post_title = get_the_title();
    }

    $post_description = post_custom('sn_portfolio_description');
    if (empty($post_description)) {
        $post_description = get_the_excerpt();
    }

    $permalink = get_permalink();

    $extra_classes = getPieceLayout($portfolio_item_number, $total_items_count);

    $post_content = get_the_content();
    $post_excerpt = get_the_excerpt();

    // look for <img class="featured">
    $match1 = $match2 = array();
    preg_match('/<img\s[^>]*class=[\'"](?:|[^\'"]*\s)featured(?:|\s[^\'"]*)[\'"][^>]*>/i', $post_content, $match1);
    if (count($match1) > 0) {
        preg_match('/src=[\'"]([^\'"]+)[\'"]/i', $match1[0], $match2);
        if (count($match2) > 1 && strlen($match2[1]) > 0) {
            $post_preview = sprintf('<img src="%s"></img>', $match2[1]);
        }
    }
    // else get first image
    $match1 = $match2 = array();
    if (empty($post_preview)) {
        preg_match('/<img\s[^>]*>/i', $post_content, $match1);
        if (count($match1) > 0) {
            preg_match('/src=[\'"]([^\'"]+)[\'"]/i', $match1[0], $match2);
            if (count($match2) > 1 && strlen($match2[1]) > 0) {
                $post_preview = sprintf('<div style="background-image:url(\'%s\')" class="sn-image"></div>', $match2[1]);
            }
        }
    }
    // else Excerpt
    if (empty($post_preview)) {
        $post_preview = sprintf('<p class="sn-piece-preview">%s</p>', $post_excerpt);
    }

    $markup = <<<MEOW
<div class="sn-porfolio-piece sn-item-{$portfolio_item_number} sn-type-{$media_type_slug} {$extra_classes}">
    <div class="sn-piece-preview">
        <div class="sn-preview-contents">
            {$post_preview}
        </div>
    </div><!--
 --><div class="sn-piece-preview">
        <div class="sn-preview-contents">
            <a href="{$permalink}">
                <h1 class="sn-piece-title">{$post_title}</h1>
                <p class="sn-piece-description">{$post_description}</p>
            </a>
        </div>
    </div>
</div>
MEOW;

    return $markup;
}

/**
 * Return a string of layout classnames to style this piece with.
 *
 * Layout for a given item is determined automatically by the total number of items.
 *
 * @param int $item_number
 * @param int $total_items_count
 * @return string
 */
function getPieceLayout( $item_number, $total_items_count ) {
    // When less than 8 items total, add a second row of sidebyside to balance layout
    if ($total_items_count < 8) {
        $default_layout = 'sn-layout-stacked two-column';

        // Even number of items
        if ($total_items_count % 2 === 0) {
            $layout_opts = array(
                1 => 'sn-layout-sidebyside',
                2 => 'sn-layout-sidebyside'
            );
        }
        // Odd number of items
        else {
            $layout_opts = array(
                1 => 'sn-layout-sidebyside'
            );
        }
    }
    // When 8 or more items total, use a varying number of two-columns to balance layout
    else {
        $default_layout = 'sn-layout-stacked three-column';

        while ($total_items_count > 7) {
            $total_items_count -= 3;
        }

        // Even number of items
        if ($total_items_count % 2 === 0) {
            $layout_opts = array(
                1 => 'sn-layout-sidebyside',
                2 => 'sn-layout-stacked two-column',
                3 => 'sn-layout-stacked two-column'
            );
        }
        // Odd number of items
        else {
            $layout_opts = array(
                1 => 'sn-layout-sidebyside',
                2 => 'sn-layout-stacked two-column',
                3 => 'sn-layout-stacked two-column',
                4 => 'sn-layout-stacked two-column',
                5 => 'sn-layout-stacked two-column'
            );

            // Damn number 7 screwing up our layout
            if ($total_items_count === 7) {
                $layout_opts += array(
                    6 => 'sn-layout-stacked two-column',
                    7 => 'sn-layout-stacked two-column'
                );
            }
        }
    }

    if (isset($layout_opts[$item_number])) {
        return $layout_opts[$item_number];
    }

    // default
    return $default_layout;
}
