<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap">
    <h1>Portfolio Settings</h1>
    <form action='options.php' method='post'>
        <?php
        // Output hidden fields (nonce, option group name, etc)
        settings_fields($vm['settings']);
        // Output settings sections
        do_settings_sections($vm['settings']);
        ?>
        <h2>Featured Pieces</h2>
        <p>Modify the order of pieces on the Portfolio index page.</p>
        <ol id="sn_portfolio_featured_pieces" class="sn-list sn-list__reorderable">
            <?php
            $query = $vm['featured_query'];
            while ($query->have_posts()) {
                $query->the_post();
                $current_value = wp_get_object_terms(get_the_ID(), 'sn_portfolio_featured')[0]->slug;
                ?>
                <li class="sn-list-item">
                    <label>
                        <input type="checkbox" name="sn_portfolio_featured[<?php the_ID() ?>]" value="1" <?php if ($current_value === '1')  print('checked') ?> />
                        <?php the_title() ?>
                        (<a href="/wp-admin/post.php?post=<?php the_ID() ?>&action=edit">edit</a>)
                    </label>
                    <input type="hidden" name="sn_portfolio_featured_order[]" value="<?php the_ID() ?>"/>
                </li>
                <?php
            }
            wp_reset_query();
            ?>
        </ol>
        <em>Click and drag to re-order pieces</em>
        <?php submit_button(); ?>
    </form>
</div>
