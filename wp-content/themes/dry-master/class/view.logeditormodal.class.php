<?php
class emuV_LogEditorModal extends emuView
{
    public function build()
    {
      ?>
      <div class="modal fade" id="entryEditorModal">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title">Edit Entry</h4>
            </div>
            <div class="modal-body">
              <div class="row" style="">
                <div class="col-sm-6">
                  <label>Log Date</label>
                  <input type="text" class="form-control log-date" name="log_date" value="">  
                  <span class="help-block">yyyy-mm-dd e.g. 2014-03-28
                </div>
                <div class="col-sm-6">
                  <label>Log Time</label>
                  <input type="text" class="form-control log-time" name="log_time" value=""> 
                  <span class="help-block">hh:mm:ss e.g. 14:34:00</span> 
                </div>
              </div>
              <div class="form-group">
                <label>Entry Type</label>
                <select name="entry_type" class="entry-type form-control">
                  <?php foreach($this->emuApp->bagsManager->entryDescriptions as $const => $description) { ?>
                  <option value="<?php echo $const?>"><?php echo $description?></option>
                  <?php } ?>
                </select>        
              </div>  
              <div class="form-group form-group-duration">
                <label>Duration</label>
                <input type="text" class="duration form-control" name="duration" value="" />
                <span class="help-block">hh:mm:ss e.g. 01:34:00</span> 
              </div>
              <div class="form-group form-group-wp">
                <label>Water Potential</label>
                <input type="text" class="water-potential form-control" name="water_potential" value="" />
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary btn-save-changes">Save changes</button>
              <button type="button" class="btn btn-warning btn-delete-entry pull-left">Delete</button>
            </div>
          </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
      </div><!-- /.modal -->      
      <?php
    }
}
