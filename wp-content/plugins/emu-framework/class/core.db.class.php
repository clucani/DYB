<?php

class emuDB
{
    public function updateRecord( $table, $data, $data_types = array(), $where, $where_types = array() )
    {
        global $wpdb;

        $fields = array();

        $data_fields = $this->prepareFields( $data, $data_types ); // sets types, nulls etc

        $where_fields = $this->prepareFields( $where, $where_types );

        $sql = "update $table set ".implode( $data_fields, ',' );

        if( count( $where_fields ) > 0 ) $sql .= ' where '.implode( $where_fields, ' and ' );

        $wpdb->query( $sql );

        do_action('emu_db_after_updateRecord'.$table, $wpdb);

    }

    protected function prepareFields( $data, $types )
    {
        $fields = array();

        foreach( $data as $key => $value )
        {
            $type = isset( $types[ $key ] ) ? $types[ $key ] : '%s';

            switch( $type )
            {
                case '%s':
                    $field_value = '"'.addslashes( $value ).'"'; break;
                case '%f':
                case '%d':
                    $field_value = $value; break;
                case '%b':
                    $field_value = $value ? '1' : '0'; break;
                case '%datefill':
                    $value = 'now()';
                    $field_value = 'now()'; break;
                default:
                    $field_value = '"'.addslashes( $value ).'"';
            }

            if( $value === '' || $value === null || $value === 'null' ) $field_value = 'null';

            $fields[] = "$key = $field_value";

        }

        return $fields;

    }

    public function insertRecord( $table, $data, $data_types = array() )
    {
        global $wpdb;

        $data_fields = $this->prepareFields( $data, $data_types ); // sets types, nulls etc

        $sql = "insert into $table set ";

        $sql .= implode( $data_fields, ',' );

        $wpdb->query( $sql );

        do_action('emu_db_after_insertRecord'.$table, $wpdb);

        return $wpdb->insert_id;
    }

}

?>