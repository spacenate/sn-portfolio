<?php

if (!defined('ABSPATH')) {
    exit;
}

wp_nonce_field($vm['nonce_action'], $vm['nonce_name']);

?>
<div>
    <label>
        <input type="checkbox" name="sn_portfolio_featured" value="1" <?php if ($vm['featured']) print('checked') ?> />
        Featured
    </label>
</div>
<div>
    <label>
        Media Type<br>
        <select name="sn_portfolio_media_type" id="sn_portfolio_media_type">
            <?php foreach ($vm['media_types'] as $slug => $value): ?>
                <option value="<?= $slug ?>" <?php if ($slug === $vm['media_type']) print('selected') ?>><?= $value ?></option>
            <?php endforeach; ?>
        </select>
    </label>
</div>
<div>
    <label>
        Source<br>
        <input type="text" name="sn_portfolio_media_source" id="sn_portfolio_media_source" value="<?= $vm['media_source'] ?>" />
    </label>
</div>
<div>
    <label>
        Portfolio Index Title<br>
        <input type="text" name="sn_portfolio_title" value="<?= $vm['title'] ?>">
    </label>
</div>
<div>
    <label>
        Portfolio Index Description<br>
        <textarea name="sn_portfolio_description"><?= $vm['description'] ?></textarea>
    </label>
</div>
