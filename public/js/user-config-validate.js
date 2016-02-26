function userConfigurationForm(){
	//Definição dos campos do input
	var nome = $('#nome-input').val();
	var email = $('#email-input').val();
	var email2 = $('#email2-input').val();
	var password = $('#password-input').val();
	var phone = $('#phone-input').val();
	 
	var hasError = {};
	var jsonErros = {};
	var response = {};
	
	var validateName = messagesJsonLog(nome, 'Nome');
	var validateEmail = messagesJsonLog(email, 'Email', 'configurations'); // 'configurations'-> Parâmetro para o caminho da URL do Ajax
	var validateEmailCheck = messagesJsonLog(email2, 'Email', 'configurations'); // 'configurations'-> Parâmetro para o caminho da URL do Ajax
	var validatePass = messagesJsonLog(password, 'Senha');
	var validatePhone = messagesJsonLog(phone, 'Telefone');
		
	//Validação do campo nome
    hasError.nome = validateName.flag;
    jsonErros.nome = validateName.msg;

	//Validação dos emails do usuário
    if(!validateEmail.flag && !validateEmailCheck.flag){
		 if (email != email2) {
		    jsonErros.email2 = "Os emails informados estão diferentes, favor digitar corretamente.";
		    hasError.email2 = true;
		    jsonErros.email = "Os emails informados estão diferentes, favor digitar corretamente.";
		    hasError.email = true;	
		 }
    }else{
        //Validação do campo email
    	hasError.email = validateEmail.flag;
        jsonErros.email = validateEmail.msg;
        
        //Validação do campo emailCheck
    	hasError.email2 = validateEmailCheck.flag;
        jsonErros.email2 = validateEmailCheck.msg;
    }

    //Validação do campo senha
	hasError.password = validatePass.flag;
    jsonErros.password = validatePass.msg;
    
    //Validação do campo telefone
	hasError.phone = validatePhone.flag;
    jsonErros.phone = validatePhone.msg;
    
    response = {flag: hasError, msg: jsonErros};
    return response;
		
}

function userChangePasswordForm(){
	
	var passwordOld = $('#passwordOld').val();
	var passwordNew1 = $('#passwordNew1').val();
	var passwordNew2 = $('#passwordNew2').val();
	var hasError = {};
	var jsonErros = {};
	var response = {};
	
	var validateOldPass = messagesJsonLog(passwordOld, 'Senha atual');
	var validateNewPass = messagesJsonLog(passwordNew1, 'Senha');
	var validateConfNewPass = messagesJsonLog(passwordNew2, 'Senha');
	
	//Validação das senhas do usuário
	if(!validateOldPass.flag && !validateNewPass.flag && !validateConfNewPass.flag){
		if (passwordNew1 != passwordNew2) {
	    	jsonErros.passwordNew1 = "As novas senhas informados estão diferentes, favor digitar corretamente.";
	    	hasError.passwordNew1 = true;
	    	jsonErros.passwordNew2 = "As novas senhas informados estão diferentes, favor digitar corretamente.";
	    	hasError.passwordNew2 = true;
	    	
	    }else if(passwordOld === passwordNew1){
	    	jsonErros.passwordOld = "A nova senha informada é igual a anterior, favor digitar uma nova.";
	    	hasError.passwordOld = true;
	    	jsonErros.passwordNew1 = "A nova senha informada é igual a anterior, favor digitar uma nova.";
	    	hasError.passwordNew1 = true;
	    }
	}else{
        //Validação do campo Senha Atual
    	hasError.passwordOld = validateOldPass.flag;
        jsonErros.passwordOld = validateOldPass.msg;
        
        //Validação do campo Nova Senha
    	hasError.passwordNew1 = validateNewPass.flag;
        jsonErros.passwordNew1 = validateNewPass.msg;
        
        //Validação do campo Nova Senha Repetir
    	hasError.passwordNew2 = validateConfNewPass.flag;
        jsonErros.passwordNew2 = validateConfNewPass.msg;
    }
	
    response = {flag: hasError, msg: jsonErros};
    return response;
	
}