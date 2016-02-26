function ValidateRegex() {
	this.emailRegex = /^[a-z0-9._%+-]+@[a-z0-9.-]+.[a-z]{2,4}$/; //match email address
	this.passwordRegex = /^[a-z0-9_-]{6,20}$/; //match password
	this.characterRegex = /^[a-zA-Zà-úÀ-Ú0-9'. \\s]{4,50}$/;
	this.lengthRegex = /^.{4,50}$/;
	this.lengthPassRegex = /^.{6,20}$/;
}

function checkCurrentPass(password){
	/* Verificando se a senha informada no formulário de edição do usuário, 
	realmente é a senha atual.*/
	var	url = "configurations/checkCurrentPassword?password=" + password;
	
	var fail = function() {
		document.location.reload(true);
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

function checkEmail (urlPath){
	var email = $("#email-input").val();
	var id = $("#id").val();
	var url;
	
	var fail = function() {
		document.location.reload(true);
	};
	
	if (typeof(urlPath) !="undefined"){//test do parametro opcional
		url = urlPath+"/checkIfEmailExists?email=" + email + "&id=" + id;
	}else{
		url = ((id === "") ? "checkIfEmailExists?email=" + email : 
			"checkIfEmailExists?email=" + email + "&id=" + id);
	}
	
	var call = $.ajax({
	    	async: false,
			url: url,
			fail: fail,
			dataType : "json", 
	    }).responseText;
	 
    var result = JSON.parse(call);
    
    return result.status;	
}

function messagesJsonLog(input, name, urlCall){
	
	var regex = new ValidateRegex();
	
    var emailRegex = regex.emailRegex;
    var passwordRegex = regex.passwordRegex;
	var characterRegex = regex.characterRegex;
	var lengthRegex = regex.lengthRegex;
	var lengthPassRegex = regex.lengthPassRegex;
	
    var hasError = "";
    var msgWarning = "";
    var response = {};
    var urlPath;
    
	if (typeof(urlCall) !="undefined"){//test do parametro opcional
		urlPath = urlCall;
	}
    
    if(input != ""){
    	if(name === "Senha"){
			if(!lengthPassRegex.test(input)){
				msgWarning = "O campo senha deve ter no mínimo 6 caracteres e no máximo 20 caracteres.";
				hasError = true;
			}else if(!passwordRegex.test(input)){
				msgWarning = "Os seguintes caracteres são permitidos na senha: a-z, A-Z, 0-9.";
				hasError = true;
			}else{
    			msgWarning = "";
    			hasError = false;
			}
        }else if(name === "Senha atual"){
        	if(!checkCurrentPass(input)){
        		msgWarning = "A senha informada é diferente da atual, por favor digite a senha correta.";
        		hasError = true;
        	}else{
    			msgWarning = "";
    			hasError = false;
        	}
		}else if(name === "Email"){
//			if(!emailRegex.test(input)){
//				msgWarning = "Este endereço é inválido, por favor utilize o formato user@email.com.";
//				hasError = true;
//			}else
			if(!checkEmail(urlPath)){
				msgWarning = "Este endereço de email já está cadastrado no sistema.";
				hasError = true;
			}else{
				msgWarning = "";
				hasError = false;
			}
        }else if(name === "Nome"){
        	if(!lengthRegex.test(input)){
        		msgWarning = "O campo "+name+" deve conter no mínimo 4 e no máximo 50 caracteres";
        		hasError = true;
        	}else{
        		msgWarning = "";
        		hasError = false;
        	}
        }else{
        	if(!lengthRegex.test(input)){
        		msgWarning = "O campo "+name+" deve conter no mínimo 4 e no máximo 50 caracteres";
        		hasError = true;
        	}else if(!characterRegex.test(input)){
        		msgWarning = "Os seguintes caracteres são permitidos na senha: a-z, A-Z, 0-9 e caracteres de pontuação comuns.";
        		hasError = true;
        	}else{
        		msgWarning = "";
        		hasError = false;
        	}
        }
    }else{
    	msgWarning = "O campo "+name+" é obrigatório.";
    	hasError = true;
    }
    
    response = {flag: hasError, msg: msgWarning};
	return response;
}

var validateForm = function(form){
	
  var $formGroups = $('div.form-horizontal');
  var $helpBlocks = $('span.help-block');
  var hasError = {};
  var jsonErros = {};
  var validate = false;
  
  function cleanMessage() {
    $formGroups.removeClass('has-error');
    $helpBlocks.text('');
  }
  
  function showMessage(erros) {
    var helpBlockPrefixo = '#help-block-';
    var formGroupPrefixo = '#form-group-';
    $.each(erros, function (propriedade, msg) {
    	if(msg != ""){	
    		$(helpBlockPrefixo + propriedade).text(msg);
    		$(formGroupPrefixo + propriedade).addClass('has-error');
    		$(formGroupPrefixo + propriedade).removeClass('has-success');
    	}else{
    		$(formGroupPrefixo + propriedade).addClass('has-success');
    		$(formGroupPrefixo + propriedade).removeClass('has-error');
    	}
    });
  }
  
  function validateInputs(inputs){
    $.each(inputs, function (propriedade, flag) {
    	if(flag == true){
    		validate = true;
    	}
    }); 
    return validate;
  }
  
  if(typeof(form)=="function"){
	  var logForm = form.call();
	  
	  hasError = logForm.flag;
	  jsonErros = logForm.msg;
	  
	  validateInputs(hasError);
	  cleanMessage();

	  if(!validate){
		  return true;
	  }else{
		  showMessage(jsonErros);
		  return false;
	  }  
  }else{
	  return false;
  } 
}
