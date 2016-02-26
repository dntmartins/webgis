//Enter Key eventos
$(document).ready(function(){
	$('#newName').keypress(function (e) {
		var key = e.which;
		if(key == 13)  // the enter key code
		{
			$('#modal-rename-btn')[0].click();
			return false;
		}
	});
	$('#modal-remove').on('shown.bs.modal', function() {
		$('#modal-remove-btn').focus();
		$('#modal-remove-btn').blur(function(){
			$('#modal-remove-btn').focus();
		});
	});
}); 

$(document).ready(function(){
	$('[rel="tooltip"]').tooltip({html:true});
	$('[data-toggle="popover"]').popover({html:true}); 
});