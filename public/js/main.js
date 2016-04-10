$(function(){
	// Parser para configurar a data para o formato do Brasil
	try{
		$.tablesorter.addParser({
			id: 'datetime',
			is: function(s) {
				return false; 
			},
			format: function(s,table) {
				s = s.replace(/\-/g,"/");
				s = s.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/, "$3/$2/$1");
				return $.tablesorter.formatFloat(new Date(s).getTime());
			},
			type: 'numeric'
		});

		$('.tablesorter').tablesorter({
	        // Envia os cabeçalhos 
	        headers: { 
	            // A sgunda coluna (começa do zero) 
	            4: { 
	                // Desativa a ordenação para essa coluna 
	                sorter: false 
	            },
				2: {
	                // Ativa o parser de data na coluna 4 (começa do 0) 
	                sorter: 'datetime' 
				}
	        },
			// Formato de data
			dateFormat: 'dd/mm/yyyy'
		});
		
		$('.reportsSorterTable').tablesorter({
	        // Envia os cabeçalhos 
	        headers: { 
	            // A sgunda coluna (começa do zero) 
	            2: { 
	                // Desativa a ordenação para essa coluna 
	                sorter: false 
	            }
	        }
		});
		$('.manageReportsSorterTable').tablesorter({
	        // Envia os cabeçalhos 
	        headers: { 
	            // A sgunda coluna (começa do zero) 
	            5: { 
	                // Desativa a ordenação para essa coluna 
	                sorter: false 
	            }
	        },
	        // Formato de data
			dateFormat: 'dd/mm/yyyy'
		});
		
	}catch(e){
		$('.table-user').tablesorter({
	        // Envia os cabeçalhos 
	        headers: { 
	            // A sgunda coluna (começa do zero) 
	            6: { 
	                // Desativa a ordenação para essa coluna 
	                sorter: false 
	            },
	        },
			// Formato de data
			dateFormat: 'dd/mm/yyyy'
		});
	}
});

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