<?php


class emuSettingsGroup
{
    public $error;
    public $messages = array();

    public $name;
    public $displayName;
    public $description;

    private $settings = array();

    public function __construct( $name = null, $display_name = null, $description = null )
    {
        if( $name ) $this->name = $name;
        if( $display_name ) $this->displayName = $display_name;
        if( $description ) $this->description = $description;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function setSettings( $settings )
    {
        $this->settings = $settings;
    }

    public function removeSetting( $name )
    {
        if( is_a( $name, 'emuSetting' ) )
        {
            $this->removeSetting( $name->name );
            return;
        }

        if( isset( $this->settings[ $name ] ) )
        {
            unset( $this->settings[ $name ] );
        }
    }

    public function getSetting( $name )
    {
        if( !isset( $this->settings[ $name ] ) ) return false;

        $setting = $this->settings[ $name ];

        return $setting;
    }

    public function getSettingValue( $name )
    {
        if( !$setting = $this->getSetting( $name ) ) return null;
        return stripslashes($setting->value);
    }

    public function addSetting( $name = null, $default_value = null, $description = null, $importance = 1, $type = 'text'   )
    {
        if( is_a( $name, 'emuSetting' ) )
        {
            $this->settings[ $name->name ] = $name;
            return;
        }

        if( is_array( $name ) )
        {
            foreach( $name as $setting )
            {
                if( is_a( $setting, 'emuSetting' ) )
                    $this->addSetting( $setting );
                else if ( is_array( $setting ) )
                    $this->addSetting( @$setting[0], @$setting[1], @$setting[2], @$setting[3], @$setting[4] );
            }
            return;
        }

        if( empty( $name ) ) return;

        $setting = new emuSetting;

        $setting->name = $name;
        $setting->description = $description;
        $setting->importance = $importance;
        $setting->defaultValue = $default_value;

        if( is_array( $type ) )
        {
            $setting->type = 'option';
            $setting->options = $type;
        }
        else $setting->type = $type;

        $this->addSetting( $setting );
    }

}

?>