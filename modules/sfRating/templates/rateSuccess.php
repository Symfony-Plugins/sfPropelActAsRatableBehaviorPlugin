<?php echo isset($message) ? $message : '' ?>
<?php if (isset($object)): ?>
<?php use_helper('sfRating') ?>
<script type="text/javascript">
$('<?php echo 'current_rating_'.get_class($object).'_'.$object->getReferenceKey() ?>').style.width = <?php echo (string)(sfConfig::get('app_rating_starwidth', 25) * $object->getRating()) ?>+'px';
</script>
<?php endif; ?>