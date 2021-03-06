<div class="modal fade" tabindex="-1" id="confirmation_modal">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content mx-auto" style="max-width: 95vw;">
      <div class="modal-header">
        <svg id="confirmation_modal_icon" class="bi me-3" fill="currentColor">
          <use id="confirmation_modal_icon_src" />
        </svg>
        <h5 class="modal-title" id="confirmation_modal_header"></h5>
        <button type="button" class="btn-close ms-3" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="confirmation_modal_body">
      </div>
      <div class="modal-footer justify-content-between">
        <div class="me-2">
          <input class="form-check-input" type="checkbox" value="" style="display: none;" id="confirmation_modal_checkbox">
          <label class="form-check-label ms-1" for="confirmation_modal_checkbox" id="confirmation_modal_checkbox_label"></label>
        </div>
        <div>
          <span class="me-2">
            <a class="btn btn-primary my-1" id="confirmation_modal_confirm_link"></a>
            <button type="button" class="btn btn-primary my-1" id="confirmation_modal_confirm_button"></button>
          </span>
          <button type="button" class="btn btn-secondary my-1" data-bs-dismiss="modal" id="confirmation_modal_dismiss"></button>
        </div>
      </div>
    </div>
  </div>
</div>
