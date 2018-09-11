<?php

// global $emuShop;

global $$emuAppObject;

$emuApp = $$emuAppObject;

switch( post_val( 'reset' ) )
{
	case 'Reset':

		// TO-DO: reset an individual template

		// $template = post_val('template');

		// $emuShop->resetTemplate( $template );

		break;

	case 'Reset All':

		$emuApp->getManager( 'template' )->populateTemplates(); break;

	case 'Refresh':

		// Reload any new templates
		$emuApp->getManager('template')->refreshTemplates(); break;

}

$templates = $emuApp->getManager( 'template' )->getTemplates();

$template_options = array();

foreach( $templates as $template )
{
	$template_options[$template->ID] = $template->filename;
}

?>

<script type="text/javascript">

var emuAppID = '<?php echo $emuApp->emuAppID?>';

jQuery(document).ready(function() {

	jQuery('input[value="Reset All"]').click(function() {

		if( confirm( 'Are you sure you want to reset all templates?' ) )
		{
			if( confirm ( 'Really? All template changes will be lost!' ) )
			{
				if( confirm( 'Really?' ) )
				{
					return true;
				}
			}
		}
		return false;
	});

});



</script>

<form method="post" action="" />

	<div class="wrap emu-templates">

		<h2><?php echo $emuApp->menuName?> Content Templates</h2>

		<div id="template-details">

			<div class="properties">

				<table class="properties">
				<tr>
					<td><div><input type="button" value="Save Changes" class="button-primary" name="save-changes" /></div>Template <?php echo drop_down( '', 'template', '', @$templates[0]->ID, $template_options )?> <!--<input type="submit" value="Reset" class="button" name="reset" />--><input type="submit" value="Reset All" class="button" name="reset" /><input type="submit" value="Refresh" class="button" name="reset" /><img src="<?php echo EMU_FRAMEWORK_URL?>/image/saving.gif" height="16" width="16" alt="Saving..." class="saving" id="template-saving" /></td>
				</tr>
				</table>

				<textarea name="templateContent" id="templateContent" rows="10" cols="200" tabindex="1" nowrap wrap="off" style="white-space:nowrap;" spellcheck="false"><?php echo count( $templates ) > 0 ? htmlentities( stripslashes( $templates[0]->template ) ) : 'No templates found.';?></textarea>

			</div>

			<input type="hidden" name="templateType" id="templateType" value="content" />

		</div>

	</div>

</form>
