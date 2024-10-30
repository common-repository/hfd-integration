<?php
/**
 * Created by PhpStorm.
 * Date: 6/6/18
 * Time: 7:22 PM
 */
if (!$syncData) {
    return;
}
?>
<?php if (!empty($syncData['count'])) : ?>
<div class="updated fade">
    <p><?php echo sprintf(__('%s order(s) sent to HFD.', HFD_WC_EPOST), $syncData['count']) ?></p>
</div>
<?php endif; ?>

<?php if ($syncData['errors']) : ?>
<div class="updated fade">
    <?php foreach ($syncData['errors'] as $error) : ?>
        <p><?php echo $error ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>
