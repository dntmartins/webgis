var getExtension = function(doc_id) {
	return $('#document_file_extension_' + doc_id).val();
};
var getName = function(doc_id) {
	return $('#document_file_name_' + doc_id).val();
};

var selectElement = function(valueToSelect) {
	var element = $('#leaveCode');
	element.value = valueToSelect;
}

var removeBalloon = function(id) {
	window.setTimeout(function() {
		$(id).tooltip('destroy');}, 4000);
};

var showLoading = function(doc_id){
	$('.btn').prop('class', 'link-hide');
	$('#loading_'+doc_id).show();
};

var selectNewPrj=function(selectEl) {
	var prj=selectEl[selectEl.selectedIndex];
	document.location=basePath + "/workspace?current_prj="+prj.value;
};

var showCommitAjaxGif = function(){
	$("#commit-ajax-gif").show();
}

var showRevertAjaxGif = function($id){
	$("#revert-ajax-gif-"+$id).show();
}