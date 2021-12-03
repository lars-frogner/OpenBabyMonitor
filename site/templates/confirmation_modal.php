<div class="modal fade" tabindex="-1" id="confirmation_modal">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <svg id="confirmation_modal_icon" class="bi me-3" fill="currentColor">
          <use id="confirmation_modal_icon_src" />
        </svg>
        <h5 class="modal-title" id="confirmation_modal_header"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="confirmation_modal_body">
      </div>
      <div class="modal-footer justify-content-between">
        <div>
          <input class="form-check-input" type="checkbox" value="" style="display: none;" id="confirmation_modal_checkbox">
          <label class="form-check-label ms-1" for="confirmation_modal_checkbox" id="confirmation_modal_checkbox_label"></label>
        </div>
        <div>
          <a class="btn btn-primary" id="confirmation_modal_confirm_link"></a>
          <button type="button" class="btn btn-primary" id="confirmation_modal_confirm_button"></button>
          <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal" id="confirmation_modal_dismiss"></button>
        </div>
      </div>
    </div>
  </div>
</div>
