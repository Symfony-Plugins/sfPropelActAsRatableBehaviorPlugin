<?php echo isset($message) ? $message : '' ?>
<script type="text/javascript">
$('<?php echo 'current_rating_'.get_class($object).$object->getId() ?>').style.width = <?php echo (string)(sfConfig::get('app_rating_starwidth', 25) * $object->getRating()) ?>+'px';
</script>