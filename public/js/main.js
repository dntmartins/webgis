var selectMenu = function(id) {
	var url = "/configurations/userConfigurations";
	var data = {
		"id" : id
	};
	$.ajax({
		dataType : "json",
		url : url,
		data : data,
	});
};

var showAjaxSuccessMessage = function (msg, appendMsg){
    if(appendMsg)
        $("#ajax_success_msg").append( "<div>" + msg + "</div>" );
    else
        $("#ajax_success_msg").html(msg);
    $("#ajax_success").fadeIn(); 
    $("#ajax_error").hide();
    setTimeout( function(){$("#ajax_success").fadeOut()}, 15000);
    $(".flash-messages").remove();
};

var showAjaxErrorMessage = function (msg, appendMsg){
    if(appendMsg)
    	$("#ajax_error_msg").append( "<div>" + msg + "</div>" );
    else
        $("#ajax_error_msg").html(msg);
    $("#ajax_error").fadeIn();
    $("#ajax_success").hide();
    setTimeout( function(){$("#ajax_error").fadeOut()}, 15000);
    $(".flash-messages").remove();
};

var showAjaxWarningMessage = function (msg, appendMsg){
    if(appendMsg)
        $("#ajax_warning_msg").append( "<div>" + msg + "</div>" );
    else
        $("#ajax_warning_msg").html(msg);
    $("#ajax_warning").fadeIn(); 
    $("#ajax_error").hide();
    $("#ajax_sucess").hide();
    $(".flash-messages").remove();
};