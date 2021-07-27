<!--
Assumes that the following variables are defined in PHP:
$id, $href, $title, $text, $confirm, $cancel
-->
<div class="modal fade" tabindex="-1" id="<?php echo $id ?>">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo $title ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><?php echo $text ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $cancel ?></button>
        <a class="btn btn-primary" href="<?php echo $href ?>"><?php echo $confirm ?></a>
      </div>
    </div>
  </div>
</div>
