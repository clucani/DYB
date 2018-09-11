<?php

$settings = new emuM_Settings( $load_settings = false, $emuAppID );

?>
<script type="text/javascript">

</script>

<style type="text/css">

table.properties th { width: 323px; }

</style>

<form method="post" action="" />

	<div class="wrap emu-admin">

		<h2>Settings</h2>

		<?php

		$aSettingGroups = $settings->getSettings();

		foreach( $aSettingGroups as $settingsGroup )
		{
			echo "<div class='properties'>";

			echo "<h3>{$settingsGroup->displayName}</h3> <!-- {$settingsGroup->name}  --> ";

			if( $settingsGroup->description ) echo "<p>{$settingsGroup->description}</p>";

			?>
			<table class="properties">
			<?php

				$settings = $settingsGroup->getSettings();

				foreach( $settings as $setting )
				{
					$setting_description = $setting->description;

					switch( $setting->type )
					{
						case 'textbox':
							$setting_field = '<textarea name="'.$setting->name.'" cols="35" rows="3">'.stripslashes($setting->value).'</textarea>';
							break;
						case 'text':
							$setting_field = '<input type="text" name="'.$setting->name.'" value="'.$setting->value.'" />';
							break;
						case 'password':
							$setting_field = '<input type="text" name="'.$setting->name.'" value="" />';

							if( !empty( $setting->value ) )
								$setting_field = str_repeat('*', strlen( $setting->value ) ).'<br />'.$setting_field;

							break;
						case 'boolean':
							$setting_field = '<input type="checkbox" name="'.$setting->name.'" value="1" '.( $setting->value ? 'checked="checked"' : '' ).' />';
							break;
						case 'number':
							$setting_field = '<input type="text" name="'.$setting->name.'" value="'.$setting->value.'" class="number" />';
							break;
						case 'option':
							$setting_field = drop_down( '', $setting->name, '', $setting->value, $setting->options );
							break;
					}

					echo "<tr><th>$setting_description</th><td>$setting_field<input type='hidden' name='setting_group[]' value='{$settingsGroup->name}' /><input type='hidden' name='setting_name[]' value='{$setting->name}' /></td></tr>";
				}
			?>
			</table>
			<?php
			echo "</div>";
		}
		?>
		<p>
			<input type="hidden" name="e-plugin" value="<?php echo $emuAppID?>" />
			<input type="hidden" name="e-action" value="saveSettings" />
			<input type="submit" name="e-button" class="button-primary" value="Save Changes" />
		</p>

	</div>

</form>
