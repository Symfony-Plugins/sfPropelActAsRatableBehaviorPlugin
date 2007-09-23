<?php echo isset($message) ? $message : '' ?>
<?php if (isset($object)): ?>
<script type="text/javascript">
$('<?php echo 'current_rating_'.$object_class.'_'.$object->getPrimaryKey() ?>').style.width = <?php echo (string)(sfConfig::get('app_rating_star_width', 25) * $object->getRating()) ?>+'px';
</script>
<?php endif; ?>