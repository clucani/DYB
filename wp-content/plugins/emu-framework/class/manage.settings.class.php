<?php

class emuM_Settings
{
    public $settings = array();
    public $emuAppID = '';

    public function __construct( $get_settings = true, $emu_app_id = null )
    {
        if( $emu_app_id ) $this->emuAppID = $emu_app_id;
        if( $get_settings ) $this->getSettings();
    }

    public function getSettings()
    {
        if( count( $this->settings ) == 0 )
        {
            $settings = get_option( 'emu_settings' );

            if( !is_array( $settings ) ) return $this->settings;

            if( !isset( $settings[ $this->emuAppID ] ) ) return $this->settings;

            $app_settings = $settings[ $this->emuAppID ];

            foreach( $app_settings as $groupName => $settingsGroup )
            {
                $this->settings[ $groupName ] = unserialize( $settingsGroup );
            }
        }

        return $this->settings;
    }

    public function addSettingsGroup( $settingsGroup )
    {
        // If $settingsGroup is an array then we assume multiple settings groups are coming through (as an array of settings groups)
        if( is_array( $settingsGroup ) )
        {
            // Divide them up and register each individually...
            foreach( $settingsGroup as $group ) $this->addSettingsGroup( $group );
            return;
        }

        // If it's not an emuSettingsGroup object then it's not something to do with settings, bail...
        if( !is_a( $settingsGroup, 'emuSettingsGroup' ) ) return;

        // See if we can find an existing settings group with the same name as the one being registered (settingsGroup)
        if( $existingGroup = $this->getSettingsGroup( $settingsGroup->name ) )
        {
            // If we do have an existing settings group then go through all it's settings
            foreach( $settingsGroup->getSettings() as $setting )
            {
                // And see if they match any of the settings of the new group
                if( $existingSetting = $existingGroup->getSetting( $setting->name ) )
                {
                    // If the do then ...
                    // ... Update all properties except the actual setting value
                    $existingSetting->description = $setting->description;
                    $existingSetting->importance = $setting->importance;
                    $existingSetting->type = $setting->type;
                    $existingSetting->options = $setting->options;
                    $existingSetting->defaultValue = $setting->defaultValue;

                    // We'll update the new setting value with any existing setting value
                    // in case the new setting object in used before being reloaded by
                    $setting->value = $existingSetting->value;
                }
                else
                {
                    // add the new setting
                    $existingGroup->addSetting( $setting );
                }
            }

            // now remove any settings that are no longer there
            // go through each of the existing settings and try and find them in the
            // new group, if not found then remove
            foreach( $existingGroup->getSettings() as $setting )
            {
                if( !$settingsGroup->getSetting( $setting->name ) ) $existingGroup->removeSetting( $setting->name );
            }
        }
        else
        {
            $this->settings[ $settingsGroup->name ] = $settingsGroup;
        }
    }

    public function saveSettings()
    {
        $settings_groups = $this->getSettings();

        $serialized_settings = array();

        foreach( $settings_groups as $name => $settingsGroup )
        {
            $settings = $settingsGroup->getSettings();

            foreach( $settings as $setting )
            {
                if( $setting->value === '' || is_null( $setting->value ) ) $setting->value = $setting->defaultValue;
            }

            $serialized_settings[ $name ] = serialize( $settingsGroup );
        }

        $settings = get_option( 'emu_settings' );

        if( !is_array( $settings ) )
            $settings = array();

        $settings[ $this->emuAppID ] = $serialized_settings;

        update_option( 'emu_settings', $settings );
    }

    public function getSetting( $group_name, $name )
    {
        if( !$settingGroup = $this->getSettingsGroup( $group_name ) ) return null;

        if( isset( $settingGroup[ $name ] ) ) return $settingGroup[ $name ];

        return null;
    }

    public function createSettingsGroup( $group_name = null, $display_name = null, $description = null )
    {
        return new emuSettingsGroup( $group_name, $display_name, $description );
    }

    public function removeSettingsGroup( $group_name )
    {
        unset( $this->settings[$group_name] );
        $this->saveSettings();
    }

    public function getSettingsGroup( $group_name )
    {
        $settings = $this->getSettings();

        if( isset( $settings[ $group_name ] ) ) return $settings[ $group_name ];

        return false;
    }

}

?>