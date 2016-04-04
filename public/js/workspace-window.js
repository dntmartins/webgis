function abstractCallAjax(url,successFunc,method, data, beforeSendFunction){
	$.ajax({
	    beforeSend: beforeSendFunction,
	    type: method,  
	    url: url,
	    data: data, 
	    success: successFunc,
	    error: function(XMLHttpRequest, textStatus, errorThrown) { 
	    	document.location.reload(true);
	    }       
	});
}

var showAjaxSuccessMessage = function (msg, appendMsg){
    if(appendMsg)
        $("#ajax_workspace_success_msg").append( "<div>" + msg + "</div>" );
    else
        $("#ajax_workspace_success_msg").html(msg);
    $("#ajax_workspace_success").fadeIn(); 
    $("#ajax_workspace_error").hide();
    setTimeout( function(){$("#ajax_workspace_success").fadeOut()}, 15000);
    $(".flash-messages").remove();
};

var showAjaxErrorMessage = function (msg, appendMsg){
    if(appendMsg)
    	$("#ajax_workspace_error_msg").append( "<div>" + msg + "</div>" );
    else
        $("#ajax_workspace_error_msg").html(msg);
    $("#ajax_workspace_error").fadeIn();
    $("#ajax_workspace_success").hide();
    setTimeout( function(){$("#ajax_workspace_error").fadeOut()}, 15000);
    $(".flash-messages").remove();
};

var showAjaxWarningMessage = function (msg, appendMsg){
    if(appendMsg)
        $("#ajax_workspace_warning_msg").append( "<div>" + msg + "</div>" );
    else
        $("#ajax_workspace_warning_msg").html(msg);
    $("#ajax_workspace_warning").fadeIn(); 
    $("#ajax_workspace_error").hide();
    $("#ajax_workspace_sucess").hide();
    $(".flash-messages").remove();
};

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

var setDefaultSld=function(selectEl){
	var sld=selectEl[selectEl.selectedIndex];
	
	if(sld.value != ""){
		var prjId = $("#combo_box_prj").val();
	
		var url = basePath + "/workspace/setDefaultSld?sldId="+sld.value+"&prjId="+prjId;	
	
		var success = function(responseJSON) {
			if (responseJSON.status) {
				document.location=basePath + "/workspace?current_sld="+sld.value;
			} else {
				if(typeof(responseJSON.warning) !="undefined" && responseJSON.warning){
					$("#comboSld").val(0);
					showAjaxWarningMessage(responseJSON.msg);
				}else{
					$("#comboSld").val(0);
					showAjaxErrorMessage(responseJSON.msg);
				}
			}
		};
	
		var fail = function() {
			showAjaxErrorMessage("Ocorreu um erro ao atualizar arquivo de estilo");
		};
		$.ajax({
			type: "GET",
			dataType : "json",
			url : url,
			success : success,
			error : fail
		});
	}
}

var uploadSld = function() {
	$("#loadingSld").show();
	$("#uploadSld").addClass("link-disable");
	
	var url = basePath + "/workspace/uploadSld";	
	formData = new FormData();
	sld = null;
	jQuery.each($('#sld')[0].files, function(i, file) {
        formData.append('sldUpload', file);
        sld = file;
	});
	regex = sld.name.match(/[À-ú!@#$%*()[]|'"_+?:><?©\/=§¹²³£¢¬/);
    if(regex){
    	showAjaxErrorMessage("O nome do arquivo de estilo não pode conter caracteres especiais");
    	$("#sld").replaceWith($("#sld").clone());
    	$("#sldName").html("");
		$("#loadingSld").hide();
    	return;
    }

	var success = function(responseJSON) {
		if (responseJSON.status) {
			id = responseJSON.sldId;
			name = responseJSON.sldName;
			$('<option value="'+ id +'">'+ id + ". " + name +'</option>').appendTo("#comboSld");
			$("#comboSld option[value='0']").remove();
			showAjaxSuccessMessage(responseJSON.msg);
		} else {
			showAjaxErrorMessage(responseJSON.msg);
		}
		$("#sld").replaceWith($("#sld").clone());
		$("#sldName").html("");
		$("#loadingSld").hide();
	};

	var fail = function() {
		showAjaxErrorMessage("Ocorreu um erro ao realizar upload do arquivo de estilo");
		$("#loadingSld").hide();
	};

	$.ajax({
		type: "POST",
		dataType : "json",
		url : url,
		data : formData,
		success : success,
		error : fail,
		cache: false,
		contentType: false,
	    processData: false
	});
}

var uploadShape = function(){
	$('#progress-bar').css('width', 0+'%');
	$('#progress-bar').css('color', 'black');
	$('#progress-bar').html("Enviando...");
	$("#progress-div").show();
	$("#uploadShape").addClass("link-disable");
	$("#escolher-shape").addClass("link-disable");
	var url = basePath + "/workspace/uploadShapeFile";
	var formData = new FormData();
	
	jQuery.each($('#shape')[0].files, function(i, file) {
        formData.append('shapeUpload', file);
	});
	
	var success = function(responseJSON) {
		if (responseJSON.status) {
			$('#progress-bar').css('width', 25+'%');
			extractShapeFile();
		} else {
			$("#progress-div").hide();
			$("#escolher-shape").removeClass("link-disable");
			showAjaxErrorMessage(responseJSON.msg);
		}
		$("#shape").replaceWith($("#shape").clone());
		$("#shapeFileName").html("");
	};

	var fail = function() {
		$("#progress-div").hide();
		$("#escolher-shape").removeClass("link-disable");
		showAjaxErrorMessage("Ocorreu um erro ao realizar upload do Shapefile");
		$("#shapeFileName").html("");
	};
	
	$.ajax({
		type: "POST",
		dataType : "json",
		url : url,
		data : formData,
		success : success,
		error : fail,
		cache: false,
		contentType: false,
	    processData: false
	});
}

var extractShapeFile = function (){
	$('#progress-bar').css('color', 'white');
	$('#progress-bar').html("Extraindo arquivos...");
	var url = basePath + "/workspace/extractShapeFile";
	
	var success = function(responseJSON) {
		if (responseJSON.status) {
			$('#progress-bar').css('width', 50+'%');
			validateShapeDbf();
		} else {
			$("#progress-div").hide();
			$("#escolher-shape").removeClass("link-disable");
			showAjaxErrorMessage(responseJSON.msg);
		}
	};

	var fail = function() {
		$("#escolher-shape").removeClass("link-disable");
		$("#progress-div").hide();
		showAjaxErrorMessage("Ocorreu um erro ao realizar upload do Shapefile");
	};
	
	$.ajax({
		type: "POST",
		dataType : "json",
		url : url,
		success : success,
		error : fail,
		cache: false,
		contentType: false,
	    processData: false
	});
}

var validateShapeDbf = function (){
	$('#progress-bar').html("Validando Shapefile...")
	var url = basePath + "/workspace/validateShapeDbf";
	var success = function(responseJSON) {
		if (responseJSON.status) {
			$('#progress-bar').css('width', 75+'%');
			importShapeToDB();
		} else {
			$("#progress-div").hide();
			$("#escolher-shape").removeClass("link-disable");
			showAjaxErrorMessage(responseJSON.msg);
		}
	};

	var fail = function() {
		$("#escolher-shape").removeClass("link-disable");
		$("#progress-div").hide();
		showAjaxErrorMessage("Ocorreu um erro ao realizar upload do Shapefile");
	};
	$.ajax({
		type: "POST",
		dataType : "json",
		url : url,
		success : success,
		error : fail,
		cache: false,
		contentType: false,
	    processData: false
	});
}

var importShapeToDB = function() {
	$('#progress-bar').html("Importando para PostGIS...")
	$("#loadingShp").show();
	$("#uploadShape").addClass("link-disable");
	var url = basePath + "/workspace/importShapeToDB";
	var formData = new FormData();
	
	jQuery.each($('#shape')[0].files, function(i, file) {
        formData.append('shapeUpload', file);
	});
	
	var success = function(responseJSON) {
		if (responseJSON.status) {
			$('#progress-bar').css('width', 100+'%')
			$('#progress-bar').html("Completo!")
			$("#noshapeThead").hide();
			$("#shapeThead").show();
			addTableRowShape(responseJSON);
			showAjaxSuccessMessage(responseJSON.msg);
		} else {
			$("#progress-div").hide();
			showAjaxErrorMessage(responseJSON.msg);
		}
		$("#shapeFileName").html("");
		$("#loadingShp").hide();
	};

	var fail = function() {
		showAjaxErrorMessage("Ocorreu um erro ao realizar upload do Shapefile");
		$("#shapeFileName").html("");
		$("#progress-div").hide();
	};
	
	var always = function() {
		$("#escolher-shape").removeClass("link-disable");
	};
	
	$.ajax({
		type: "POST",
		dataType : "json",
		url : url,
		data : formData,
		success : success,
		error : fail,
		complete : always,
		cache: false,
		contentType: false,
	    processData: false
	});
}

var addTableRowShape = function(json) {
	var numRows =  $('#shapeTable tr').length;
	var newRow = $("<tr>");
    var cols = "";
    cols += '<td id="newShape-'+json.shapeId+'">' + json.fileName + '</td>';
    cols += '<td id="newDate-'+json.shapeId+'">' + json.date +'</td>';
    //cols += '<td id="newInfo-'+json.shapeId+'"><a href="#" title="Report" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="'+ json.info +'">Report</a></td>';
    newRow.append(cols);
    $("#shapeTable").append(newRow);
};

var showSldFileName = function(){
	jQuery.each($('#sld')[0].files, function(i, file) {
		fileName = file.name;
		fileType = file.type;
		fileSize = file.size;
	});
	extensao = (fileName.substring(fileName.lastIndexOf("."))).toLowerCase();
	 if(extensao != ".sld"){
		 $("#sldName").html("<span style='color : red'>Extensão inválida</span>");
	 }
	 else if(fileSize > fileSizeInBytes){
		$("#sldName").html("<span style='color : red'>Tamanho do sld excede o limite</span>");
		$("#uploadSld").addClass("link-disable");
	}else{
		$("#uploadSld").removeClass("link-disable");
		$("#sldName").html(fileName);
	}
}
var showShapeFileName = function(){
	jQuery.each($('#shape')[0].files, function(i, file) {
		fileName = file.name;
		fileType = file.type;
		fileSize = file.size;
	});
	if(fileType != "application/zip"){
		$("#shapeFileName").html("<span style='color : red'>Escolha um arquivo .zip</span>");
		$("#uploadShape").addClass("link-disable");
	}
	else if(fileSize > fileSizeInBytes){
		$("#shapeFileName").html("<span style='color : red'>Tamanho do Shapefile excede o limite</span>");
		$("#uploadShape").addClass("link-disable");
	}else{
		$("#uploadShape").removeClass("link-disable");
		$("#shapeFileName").html(fileName);
	}
}

var showValidationRules = function() {
	var url = basePath + "/workspace/getDbfJSON";
	var template = "<ul>";
	var success = function(responseJSON) {
		$.each(responseJSON, function(index,jsonObject){
			template += "<li>";
		    $.each(jsonObject, function(key,val){
		    	if(key == "name"){
		    		key = "coluna: "
		    	}
		    	if(key == "type"){
		    		key = "- tipo: "
		    	}
		    	template += " " + key + " " + val;
		    });
		    template += "</li>"
		});
		template += "</ul>"
		rules = "<ol>"+
					"<li>"+
						"Verifica se o tamanho do arquivo não excede "+fileSizeString+";"+
					"</li>"+
					"<li>"+
						"Verificação da existência dos 4 arquivos (shp, shx, prj, dbf) - Demais extensões são ignoradas. Não é aceito mais de um shapefile no pacote;"+
					"</li>"+
					"<li>"+
						"Verificação da existencia de diretórios/pastas no arquivo zip (Arquivo zip não deve conter pastas);"+
					"</li>"+
					"<li>"+
						"Descompactação do arquivo zip;"+
					"</li>"+
					"<li>"+
						"Validação da projeção;"+
					"</li>"+
					"<li>"+
						"Validação dos atributos minimos do dbf: " + template +
					"</li>"+
					"<li>"+
						"Importação para o postgis."+
					"</li>"+
				"</ol>";
		$("#rules").html(rules);
	};

	var fail = function() {
		$("#modal-info").modal("hide");
		showAjaxErrorMessage("Ocorreu um erro ao baixar informações sobre o template do arquivo dbf");
	};
	
	$.ajax({
		type: "POST",
		dataType : "json",
		url : url,
		success : success,
		error : fail,
		cache: false,
		contentType: false,
	    processData: false
	});
	$("#modal-info").modal("show");
}

var showCommitAjaxGif = function(){
	$("#commit-ajax-gif").show();
}