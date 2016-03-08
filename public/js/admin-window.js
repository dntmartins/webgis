var moreInfo=function() {
	var link = "<a href='http://www.dpi.inpe.br/boeing/doku.php?id=wiki:development:portal'>http://www.dpi.inpe.br/boeing/</a>";
	$("#moreInfo").modal('show');
	$("#moreInfoContent").html('Para informações sobre como incluir URLs de serviços no Terrabrasilis, acesse:<br/>'+link+'<br/>*é necessário senha de acesso para ver estas informações.');
};

var showAjaxSuccessMessage = function (msg, appendMsg){
    if(appendMsg)
        $("#ajax_admin_success_msg").append( "<div>" + msg + "</div>" );
    else
        $("#ajax_admin_success_msg").html(msg);
    $("#ajax_admin_success").fadeIn(); 
    $("#ajax_admin_error").hide();
    $(".flash-messages").remove();
};

var showAjaxErrorMessage = function (msg, appendMsg){
    if(appendMsg)
    	$("#ajax_admin_error_msg").append("<div>" + msg + "</div>");
    else
        $("#ajax_admin_error_msg").html(msg);
    $("#ajax_admin_error").fadeIn();
    $("#ajax_admin_success").hide();
    $(".flash-messages").remove();
};

var getPublish = function(id) {
	$("#publish").modal('show');
	$('#id').val(id);
}

var publish = function(){
	date = $("#publishDate").val();
	id = $("#id").val();
	url = basePath+"/project/publish?id="+id+"&date="+date;
	var success = function(responseJSON) {
		$("#publish").modal("hide");
		if (responseJSON.status) {
			showAjaxSuccessMessage(responseJSON.msg);
		}
		else{
			showAjaxErrorMessage(responseJSON.msg);
		}
	};

	var fail = function() {
		showAjaxErrorMessage("Ocorreu um erro no servidor");
		$("#publish").modal("hide");
	};
	$.ajax({
		type:"get",
		dataType : "json",
		url : url,
		success : success,
		error : fail
	});
}

var getEnableUser = function(id, name) {
	$("#enable").modal('show');
	$("#modalLabelEnable").html('O usuário <b>'+ name +'</b> será ativado. Confirma?');
	$('#hiddenId').val(id);
}

var disableUser = function(id, url) {
	var data = {
		"id" : id
	};

	var success = function(responseJSON) {
		if(responseJSON.status){
			userId = responseJSON.userId; 
			$("#userName"+userId).addClass('link-disable');
			$("#userLogin"+userId).addClass('link-disable');
			$("#userEmail"+userId).addClass('link-disable');
			$("#userRol"+userId).addClass('link-disable');
			$("#editUserBtn"+userId).addClass('link-disable');
			$("#enableDisableUserBtn"+userId).attr("href","javascript:enableUser("+userId+", '"+basePath+"/user/enable');");
			$("#enableDisableUserBtn"+userId).removeClass("glyphicon-ban-circle");
			$("#enableDisableUserBtn"+userId).addClass("glyphicon-ok-circle");
			showAjaxSuccessMessage(responseJSON.msg);
		}
		else{
			showAjaxErrorMessage(responseJSON.msg);
		}
	};

	var fail = function() {
		showAjaxErrorMessage("Não foi possível desativar o usuário");
	};
	$.ajax({
		dataType : "json",
		url : url,
		data : data,
		success : success,
		error : fail
	});
};

var enableUser = function(id, url) {
	$("#enable").modal('hide')

	var data = {
		"id" : id
	};
	$('loading_' + id).show();

	var success = function(responseJSON) {
		if(responseJSON.status){
			userId = responseJSON.userId;
			$("#userName"+userId).removeClass('link-disable');
			$("#userLogin"+userId).removeClass('link-disable');
			$("#userEmail"+userId).removeClass('link-disable');
			$("#userRol"+userId).removeClass('link-disable');
			$("#editUserBtn"+userId).removeClass('link-disable');
			$("#enableDisableUserBtn"+userId).attr("href","javascript:disableUser("+userId+", '"+basePath+"/user/disable');");
			$("#enableDisableUserBtn"+userId).addClass("glyphicon-ban-circle");
			$("#enableDisableUserBtn"+userId).removeClass("glyphicon-ok-circle");
			showAjaxSuccessMessage(responseJSON.msg);
		}
		else{
			showAjaxErrorMessage(responseJSON.msg);
		}
	};

	var fail = function() {
		showAjaxErrorMessage("Não foi possível ativar o usuário");
	};
	var always = function() {
		$('loading_' + id).hide();
	};
	$.ajax({
		dataType : "json",
		url : url,
		data : data,
		success : success,
		error : fail,
		complete : always
	});
};

var getRole = function(id, name) {
	$("#remove").modal('show');
	$("#roleNameLabel").html('O perfil <b>'+ name +'</b> será removido permanentemente. Confirma?');
	$('#hiddenId').val(id);
}

var removeRole= function(url) {
	var id = $('#hiddenId').val();

	$("#remove").modal('hide');

	var data = {
		"id" : id
	};
	$('loading_' + id).show();

	var success = function(responseJSON) {
		if (responseJSON.status) {
			$("#roleRow"+responseJSON.rolId).remove();
			showAjaxSuccessMessage(responseJSON.msg);
		}else if(responseJSON.msg){
			$("#removeError").modal('show');
			$("#modalLabelError").html(responseJSON.msg);
		}
	};

	var fail = function() {
		showAjaxErrorMessage("Não foi possível remover o perfil");
	};
	var always = function() {
		$('loading_' + id).hide();
	};
	$.ajax({
		dataType : "json",
		url : url,
		data : data,
		success : success,
		error : fail,
		complete : always
	});
};

var getEnableProject = function(id, name) {
	$("#enable").modal('show');
	$("#modalLabelEnable").html('O projeto <b>'+ name +'</b> será ativado. Confirma?');
	$('#hiddenId').val(id);
}

var disableProject = function(id, url) {
	var data = {
		"id" : id
	};

	var success = function(responseJSON) {
		if(responseJSON.status){
			prjId = responseJSON.prjId;
			$("#prjName"+prjId).addClass('link-disable');
			$("#prjDesc"+prjId).addClass('link-disable');
			$("#prjLink"+prjId).addClass('link-disable');
			$("#editPrjBtn"+prjId).addClass('link-disable');
			$("#publishPrjBtn"+prjId).addClass('link-disable');
			$("#enableDisablePrjBtn"+prjId).attr("href","javascript:enableProject("+prjId+", '"+basePath+"/project/enable');");
			$("#enableDisablePrjBtn"+prjId).removeClass("glyphicon-ban-circle");
			$("#enableDisablePrjBtn"+prjId).addClass("glyphicon-ok-circle");
			showAjaxSuccessMessage(responseJSON.msg);
		}
		else{
			showAjaxErrorMessage(responseJSON.msg);
		}
	};

	var fail = function() {
		showAjaxErrorMessage("Não foi possível desativar o projeto");
	};
	$.ajax({
		dataType : "json",
		url : url,
		data : data,
		success : success,
		error : fail
	});
};

var enableProject = function(id, url) {
	$("#enable").modal('hide')

	var data = {
		"id" : id
	};
	$('loading_' + id).show();

	var success = function(responseJSON) {
		if(responseJSON.status){
			prjId = responseJSON.prjId;
			$("#prjName"+prjId).removeClass('link-disable');
			$("#prjDesc"+prjId).removeClass('link-disable');
			$("#prjLink"+prjId).removeClass('link-disable');
			$("#editPrjBtn"+prjId).removeClass('link-disable');
			$("#publishPrjBtn"+prjId).removeClass('link-disable');
			$("#enableDisablePrjBtn"+prjId).attr("href","javascript:disableProject("+prjId+", '"+basePath+"/project/disable');");
			$("#enableDisablePrjBtn"+prjId).addClass("glyphicon-ban-circle");
			$("#enableDisablePrjBtn"+prjId).removeClass("glyphicon-ok-circle");
			showAjaxSuccessMessage(responseJSON.msg);
		}
		else{
			showAjaxErrorMessage(responseJSON.msg);
		}
	};

	var fail = function() {
		showAjaxErrorMessage("Não foi possível ativar o projeto");
	};
	var always = function() {
		$('loading_' + id).hide();
	};
	$.ajax({
		dataType : "json",
		url : url,
		data : data,
		success : success,
		error : fail,
		complete : always
	});
};

var setOlderAndNewerDates = function(){
	id = $("#prjs").val();
	url = basePath+"/shape/getOlderAndNewerDates?prjId="+id;
	var success = function(responseJSON) {
		if (responseJSON.status) {
			$("#from").val(responseJSON.older);
			$("#to").val(responseJSON.newer);
		}
	};

	var fail = function() {
		showAjaxErrorMessage("Ocorreu um erro no servidor");
	};
	$.ajax({
		type:"get",
		dataType : "json",
		url : url,
		success : success,
		error : fail
	});
}

var checkPrjs = function(assocPrjs){
	$("input[name='prjs[]']:checkbox").prop('checked', false);
	for (var cont = 0; cont < assocPrjs.length; cont++) {
		$("#prj"+(assocPrjs[cont])).prop('checked', true);
	}
}

var changeUser = function() {
	var userId = $("#users").val();
	var url = "getUserPrjs?id="+userId;
		
	var success = function(responseJSON) {
		if (responseJSON.status != undefined) {
			if(responseJSON.status){
				$('input[name="prjs[]"]').attr('disabled', false);
				$("input[name='prjs[]']:checkbox").prop('checked', false);
				
				prjs = document.getElementsByName("prjs[]");
				if(responseJSON.assocPrjs){
					checkPrjs(responseJSON.assocPrjs);
				}
			}else{
				showAjaxErrorMessage(responseJSON.msg);
			}
		}
	};

	var fail = function() {
		showAjaxErrorMessage('Não foi possível recuperar os projetos associados a este usuário');
	};

	$.ajax({
		dataType : "json",
		type: "get",
		url : url,
		success : success,
		error : fail
	});
}

var deselectAll = function(){
	$('#multiSelectPrivileges').multiSelect('deselect_all');
}

$(document).ready( function() {
	$('#multiSelectPrivileges').multiSelect();
});

function validateFormUser(){ //Validação para formuláro de criação do usuário.
	//Validação do usuário
    var nome = $('#nome-input').val();
    var comboRoles = $('#comboRoles').val();
    var formEdit = $('#id').val();
    var hasError = {};
    var jsonErros = {};
    var response = {};
    
    var validateName = messagesJsonLog(nome, 'Nome');
    
    //Validação do campo nome
    hasError.nome = validateName.flag;
    jsonErros.nome = validateName.msg;
    
    //Validação do usuário no momento da edição
	if(formEdit != ""){
		//Validação do campo email
		if(email != ""){
			hasError.email = validateEmail.flag;
		    jsonErros.email = validateEmail.msg;
		}else{
			jsonErros.email = validateEmail.msg;
		}
		//Validação do campo senha
		if(password != ""){
			hasError.password = validatePass.flag;
		    jsonErros.password = validatePass.msg;
		}else{
			jsonErros.password = "";
		}
	}else{	
		//Validação do campo email
		hasError.email = validateEmail.flag;
	    jsonErros.email = validateEmail.msg;
	    
		//Validação do campo senha
		hasError.password = validatePass.flag;
	    jsonErros.password = validatePass.msg;
	}
	
    //Validação do perfil do usuário
    if (comboRoles == 0) {
    	jsonErros.comboRoles = "Por favor selecione um perfil para o usuário.";
    	hasError.comboRoles = true;	
    }else{
    	jsonErros.comboRoles = "";
    	hasError.comboRoles = false;
    }
    
    response = {flag: hasError, msg: jsonErros};
    return response;

}

function validadeAssociateForm(){//Validação para o formuláro de associação do usuário.
	var users = $('#users').val();
	var checkProjects;
	var checkProject = $("input[name='prjs[]'][type='checkbox']:checked").length;
    var hasError = {};
    var jsonErros = {};
    var response = {};
	  
	//Validação do perfil do usuário
    if (users == 0) {
    	jsonErros.users = "Por favor selecione um usuário para associação.";
    	hasError.users = true;	
    }else{
    	jsonErros.users = "";
    	hasError.users = false;
    }
    
	//Validação dos projetos
	if (checkProject == 0) {
		jsonErros.checkProjects = "Por favor selecione pelo menos um subprojeto.";
		hasError.checkProjects = true;
	}else{
		jsonErros.checkProjects = "";
    	hasError.checkProjects = false;
	}

	response = {flag: hasError, msg: jsonErros};
	return response;
}

$(document).ready(function(){ //Mensagens de aviso para o combo de perfil!
	$('#comboRoles').on('change', function() {
		if(this.value == 3){
			  $("#message").html(warningGeralCoord);
			  $("#warningCoord").css("display", "block");
		 }else{
			  $("#warningCoord").css("display", "none");
		  }
	});
});

function checkRole(name, id){
	var	url = ((id === "") ? basePath+"/role/checkDuplicateName?name=" + name : basePath+"/role/checkDuplicateName?name=" + name + "&id=" + id);
	var fail = function() {
		showAjaxErrorMessage('Não foi possível verificar se o nome do perfil já existe');
	};
	var call = $.ajax({
    	async: false,
		url: url,
		fail: fail,
		dataType : "json", 
    }).responseText;
	var result = JSON.parse(call);
	return result.status;	
}

function createPerfilForm(){
    var nome = $('#nome-input').val();
    var privileges = $('#multiSelectPrivileges').val();
    var formEdit = $('#id').val();
    
    var hasError = {};
    var jsonErros = {};
    var response = {};
    
    var validateName = messagesJsonLog(nome, 'Nome');
    
    //Validação dos privilégios do usuário
    if (privileges === null) {
    	jsonErros.privileges = "Por favor selecione pelo menos um privilégio para o usuário.";
    	hasError.privileges = true;	
    }else{
    	jsonErros.privileges = "";
    	hasError.privileges = false;
    }

	if(!validateName.flag){
		//Validação do campo nome
		if(checkRole(nome, formEdit)){
			hasError.nome = validateName.flag;
			jsonErros.nome = validateName.msg;	
		}else{
			jsonErros.nome = "Já existe um perfil com este nome, favor informe um nome diferente.";
			hasError.privileges = true;
		}
	}else{
		hasError.nome = validateName.flag;
		jsonErros.nome = validateName.msg;
	}

    response = {flag: hasError, msg: jsonErros};
    return response;
}

jQuery(document).ready(function() {
    var offset = 220;
    var duration = 500;
    jQuery(window).scroll(function() {
        if (jQuery(this).scrollTop() > offset) {
            jQuery('.back-to-top').fadeIn(duration);
        } else {
            jQuery('.back-to-top').fadeOut(duration);
        }
    });
    
    jQuery('.back-to-top').click(function(event) {
        event.preventDefault();
        jQuery('html, body').animate({scrollTop: 0}, duration);
        return false;
    })
});

var addProject = function(){
	$("#btnAddProject").addClass("link-disable");
	$("#loading").show();
	 
	url = basePath+"/project/form";
	formData = new FormData();

	id = $("#id").val();
	
	formData.append('id', id);

	prjName = $("#nome-input").val();
	if(!id && !prjName){
		showAjaxErrorMessage("Digite um nome");
		$("#btnAddProject").removeClass("link-disable");
		$("#loading").hide();
		return;
	}
	regex = prjName.match(/[À-ú!@#$%*()[]|'"_+?:><?©\/-.=§¹²³£¢¬/);
	if(prjName.length > 10){
		showAjaxErrorMessage("Nome do projeto deve conter no máximo 10 caracteres");
		$("#btnAddProject").removeClass("link-disable");
		$("#loading").hide();
		return;
	}else if(regex){
		showAjaxErrorMessage("Digite um nome sem caracteres especiais");
		$("#btnAddProject").removeClass("link-disable");
		$("#loading").hide();
		return;
	}
	formData.append('name', prjName);

	link = $("#link-input").val();
	formData.append('link', link);
	
	logoName = $("#uploadLogoLabel").html();
	if(!id && !logoName){
		$("#btnAddProject").removeClass("link-disable");
		$("#loading").hide();
		showAjaxErrorMessage("Escolha um logo");
		return;
	}else{
		formData.append('logoName', logoName);
	}
	
	if(!id && !$('#image')[0]){
		$("#btnAddProject").removeClass("link-disable");
		$("#loading").hide();
		$("#image").replaceWith($("#image").clone());
		showAjaxErrorMessage("Escolha um logo");
		return;
	}else{
		$.each($('#image')[0].files, function(i, file) {
	        formData.append('image', file);
		});
	}
	sld = null;
	if($('#sld')[0]){
		$.each($('#sld')[0].files, function(i, file) {
			formData.append('sldUpload', file);
			sld = file;
		});
	}
	if(sld){
		regex = sld.name.match(/[À-ú!@#$%*()[]|'"_+?:><?©\/=§¹²³£¢¬/);
	    if(regex){
	    	showAjaxErrorMessage("O nome do arquivo de estilo não pode conter caracteres especiais");
	    	$("#sld").replaceWith($("#sld").clone());
	    	$("#sldName").html("");
			$("#loadingSld").hide();
	    	return;
	    }
	}
	
	description = CKEDITOR.instances.description.getData();
	if(!id && !description){
		showAjaxErrorMessage("Digite uma descrição");
		$("#btnAddProject").removeClass("link-disable");
		$("#loading").hide();
		return;
	}else{
		formData.append('description', description);
	}
	
	var success = function(response) {
		if(response.status){
			CKEDITOR.instances.description.setData('');
			$("#btnAddProject").removeClass("link-disable");
			$("#loading").hide();
			document.location = basePath+"/project";
			showAjaxSuccessMessage(response.msg);
		}else{
			$("#sld").replaceWith($("#sld").clone());
	    	$("#sldName").html("");
			$("#btnAddProject").removeClass("link-disable");
			$("#loading").hide();
			showAjaxErrorMessage(response.msg);
		}
	};
	
	var fail = function() {
		$("#btnAddProject").removeClass("link-disable");
		$("#loading").hide();
		showAjaxErrorMessage("Ocorreu um erro no servidor");
	};
	
	$.ajax({
		dataType : "json",
		url : url,
		data: formData,
		type: "post",
		success : success,
		error : fail,
		cache: false,
		contentType: false,
	    processData: false
	});
	
};

var showUploadImageName = function() {
	if($('#image')[0].files[0].name)
		$('#uploadLogoLabel').html($('#image')[0].files[0].name);
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
	 else if(fileSize > 51200000){// 50 mb é o limite
		$("#sldName").html("<span style='color : red'>Tamanho do sld excede o limite</span>");
	}else{
		$("#sldName").html(fileName);
	}
}
var removeShapes = function(){
	from = $("#from").val();
	to = $("#to").val();
	prj = $("#prjs").val();
	if(!prj){
		showAjaxErrorMessage("Selecione um projeto");
		return;
	}
	if(to && from){
		var url = basePath + "/shape/removeZipFiles?from="+from+"&to="+to+"&prjId="+prj;
		var success = function(responseJSON) {
			if (responseJSON.status) {
				showAjaxSuccessMessage(responseJSON.msg);
			} else {
				showAjaxErrorMessage(responseJSON.msg);
			}
		};
	}
	else{
		showAjaxErrorMessage("Selecione as datas");
		return;
	}

	var fail = function() {
		showAjaxErrorMessage("Não foi possível remover os shapefiles");
	};
	$.ajax({
		type: "GET",
		dataType : "json",
		url : url,
		success : success,
		error : fail,
	});
}