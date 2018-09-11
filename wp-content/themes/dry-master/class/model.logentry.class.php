<?php

class logEntry extends emuDbEntity
{
    function config()
    {
        global $emuTheme;

        $this->dbPrefix = $emuTheme->dbPrefix;
        $this->dbTable = 'drying_logs';
    }

   public function __toString()
    {
        return $this->getEntryDescription();
    }    

    public function getEntryDescription()
    {
      switch( $this->entryType )
        {
          case emuM_Bags::LOG_TYPE_DRYING:
            return "Drying";
          break;
          case emuM_Bags::LOG_TYPE_EQUIL:
            return "Equilibrating";
          break;
          case emuM_Bags::LOG_TYPE_MEASUREMENT:
            return "Measurement";
          break;
          default:
          	return 'New Entry';
        }
    }

    public function toJSON()
    {
      return json_encode($this->data);
    }
}
?>