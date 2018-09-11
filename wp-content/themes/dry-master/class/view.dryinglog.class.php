<?php
class emuV_DryingLog extends emuView
{
    public $bag;

    public function build()
    {
        $this->bag = $this->vars['bag'];

        $log_entries = $this->bag->getLogEntries();

        $log_entries = array_reverse($log_entries);

        ?>
        <table class="table log-entries">
          <?php 
          foreach( $log_entries as $entry )
            $this->row($entry);
          ?> 
        </table>
        <?php
    }

    public function row($entry)
    {
      ?>
      <tr>
        <td><?php echo $entry->getID()?></td>
        <td><?php echo $entry->logTime?></td>
        <td><?php echo $entry?></td>
        <td><?php
        if($entry->entryType == emuM_Bags::LOG_TYPE_MEASUREMENT)
        {
          echo $entry->waterPotential;
        }
        else
        {
          echo gmdate('H:i:s', $entry->duration).' ('.$entry->duration.')';
        }
        ?><input type="hidden" name="entry_id" class="entry-id" value="<?php echo $entry->getID()?>" /></td>
      </tr>
      <?php
    }
}
