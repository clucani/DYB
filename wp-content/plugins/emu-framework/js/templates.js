jQuery(document).ready(function() {

	templateContent = jQuery('#templateContent');
	imageSaving = jQuery('#template-saving');
	templateList = jQuery('#template');

	var templateEditor = CodeMirror.fromTextArea(document.getElementById("templateContent"), {
        mode: "text/html", lineNumbers: true, enterMode: "keep"
    });

	jQuery('input[name="save-changes"]').click(function() {
		saveTemplate();
	});

	function saveTemplate() {

		imageSaving.fadeIn("fast");

		jQuery.post( document.URL,
				{
					'e-action': (jQuery('#templateType').val() == 'email' ? 'save_email_template' : 'save_template'),
					'template_content': templateEditor.getValue(),
					'e-plugin': emuAppID,
					'template_id': templateList.val()
				},
				function(result){

					if (result === null || result === undefined) {
						// something went wrong...
					}
					else {
						if(result.ok) {

						}
						imageSaving.fadeOut("fast");
					}
				}
			);
	}


	templateList.change(function() {

		imageSaving.fadeIn("fast");

		jQuery.post( document.URL,
			{
				'e-action':(jQuery('#templateType').val() == 'email' ? 'get_email_template' : 'get_template'),
				'e-plugin': emuAppID,
				'template_id': jQuery(this).val()
			},
			function(result){

				if (result === null || result === undefined) {
					// something went wrong...
				}
				else {
					if(result.ok) {
						templateEditor.setValue(result.templateContent);
					}
					imageSaving.fadeOut("fast");
				}
			}
		);
	});



});