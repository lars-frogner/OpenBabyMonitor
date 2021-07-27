<!--
Assumes that the following variables are defined in PHP:
$id, $href, $title, $static_text, $dynamic_text, $confirm, $cancel
-->
<div class="modal fade" tabindex="-1" id="<?php echo $id ?>">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo $title ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="<?php echo $id ?>_body">
        <?php if (!empty($static_text)) {
          echo "<p>$static_text</p>";
        } ?>
        <p id="<?php echo $id ?>_body_dynamic_text"><?php echo $dynamic_text ?></p>
      </div>
      <div class="modal-footer">
        <a class="btn btn-primary" href="<?php echo $href ?>"><?php echo $confirm ?></a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $cancel ?></button>
      </div>
    </div>
  </div>
</div>

<script>
  var MODAL_BODY_ID_TAIL = '_body';
  var MODAL_BODY_TEXT_ID_TAIL = MODAL_BODY_ID_TAIL + '_dynamic_text';
</script>
