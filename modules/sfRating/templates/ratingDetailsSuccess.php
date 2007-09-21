<?php if (isset($rating_details) && is_array($rating_details)): ?>
  <table class="rating_details_table">
    <?php foreach ($rating_details as $rating => $votes): ?>
    <tr>
      <th><?php echo sprintf(__('%d stars'), $rating) ?></th>
      <td><div style="width:<?php echo $votes * 10 ?>px">&nbsp;</div></td>
      <td>(<?php echo $votes ?>)</td>
    </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
