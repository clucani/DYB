<?php
class emuDbEntity extends emuDB
{
    public $data = array();

    public $dbPrefix;
    public $dbTable;
    public $specialFieldTypes = array();
    public $run_init = true;
    public $stripslashes = false;

    public function __construct( $dbID = null, $post = null, $dbPrefix = null, $dbTable = null, $specialFieldTypes = null )
    {
        $this->dbPrefix = $dbPrefix;
        $this->dbTable = $dbTable;
        $this->specialFieldTypes = $specialFieldTypes;

        $this->config();

        if( $dbID ) $this->dbID = $dbID;

        if( $post )
        {
            $postID = is_object( $post ) ? $post->ID : $post;
            $this->postID = $postID;
        }
        $this->getData();

        if( $this->run_init )
            $this->init();
    }

    public function init()
    {

    }

    public function config()
    {

    }

    public function setData( $data )
    {
        if( !is_array( $data ) ) return false;
        $this->data = $data;
    }

    public function getData()
    {
        global $wpdb;

        if( !( $this->dbID || $this->postID ) ) return;

        $sql = "select * from {$this->dbPrefix}{$this->dbTable}";

        $arr_where = array();

        $update_conditions = $this->getUpdateConditions();

        $arr_where = $this->prepareFields( $update_conditions, $this->specialFieldTypes );

        $sql .= ' where '.implode( ' and ', $arr_where );

        if( $data = $wpdb->get_row( $sql, ARRAY_A ) )
            $this->data = array_merge( $this->data, $data );
    }

    public function __get( $member )
    {
        global $wpdb;

        switch( $member )
        {
            default:

                if( !isset( $this->data[ $member ] ) ) return null;

                if($this->stripslashes)
                    return stripslashes($this->data[ $member ]);

                return $this->data[ $member ];
        }
    }

    public function __set( $member, $value )
    {
        $this->data[ $member ] = $value;
    }

    public function save()
    {
        global $wpdb;

        if( $this->dbID )
        {

            do_action('emu_db_before_update_'.$this->dbTable, $this);
            $result = $this->updateRecord( "{$this->dbPrefix}{$this->dbTable}", $this->data, $this->specialFieldTypes, $this->getUpdateConditions() );
            do_action('emu_db_after_update_'.$this->dbTable, $this, $result);//added result param it will be false or dbid of update insert
        }
        else
        {
            do_action('emu_db_before_insert_'.$this->dbTable, $this);
            $result = $this->dbID = $this->insertRecord( "{$this->dbPrefix}{$this->dbTable}", $this->data );
            do_action('emu_db_after_insert_'.$this->dbTable, $this, $result);//added result param it will be false or dbid of update insert
        }

        return true;
    }

    public function update()
    {
        return $this->save();
    }

    public function delete()
    {
        global $wpdb;

        $conditions = $this->getUpdateConditions();

        $arr_where = array();

        foreach( $conditions as $condition => $value )
        {
            $arr_where[] = "$condition = '$value'";
        }

        $wpdb->query( "delete from {$this->dbPrefix}{$this->dbTable} where ".implode( ' and ', $arr_where ) );
    }

    public function getUpdateConditions()
    {
        $conditions = array();

        if( $this->dbID )
            $conditions['dbID'] = $this->dbID;

        if( $this->postID )
            $conditions['postID'] = $this->postID;

        return $conditions;
    }

    public function isNewRecord()
    {
        return $this->dbID ? false : true;
    }

    public function getID()
    {
        return $this->dbID;
    }
}


?>