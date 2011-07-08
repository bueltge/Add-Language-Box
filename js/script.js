jQuery(document).ready( function ( $ ) {
	var id = 'fb-language-box';
	
	$("#fb-language-box").addClass("mceEditor");
	if ( typeof( tinyMCE ) == "object" && typeof( tinyMCE.execCommand ) == "function" ) {
		$(id).wrap( "<div id='editorcontainer'></div>" );
		tinyMCE.execCommand("mceAddControl", false, id);
	};
	
	$('a.toggleVisual').click(
		function() {
			tinyMCE.execCommand('mceAddControl', false, id);
		}
	);
	
	$('a.toggleHTML').click(
		function() {
			tinyMCE.execCommand('mceRemoveControl', false, id);
		}
	);
});