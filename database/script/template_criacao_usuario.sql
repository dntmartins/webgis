/* Passos para a criação de usuários e perfis */

/*
Role 

Caso seja necessário a criação de um novo perfil, ele deve ser inserido primeiro.
*/
INSERT INTO `BNDES`.`role` (`rol_id`,`name`,`is_admin`) VALUES(1,'Teste',0);

/* 
Privilege

O privilégio é o segundo a ser inserido. 
Caso o privilégio seja da área de documentos comuns ou administração, deve-se colocar o id do recurso(Tabela 'resource') correspondente:
2 - Documentos comuns;
3 - Administração.
Ao criar um novo privilégio é necessário modificar o código, colocando seu nome na chamada do método 'isAllowed'.
*/
INSERT INTO `privilege` (`pri_id`,`res_id`,`name`) VALUES (1,1,'Novo privilégio');

/*
Role_privilege

Depois, deve-se associar o perfil aos privilégios necessários.
Os IDs dos privilégios existentes se encontram no arquivo de inserts (database/scripts/inserts.sql).
*/
INSERT INTO `BNDES`.`role_privilege` (`rolPriId`,`rol_id`,`pri_id`) VALUES(1, 1, 1);

/*
User

Em seguida, inserir o usuário. A coluna 'active' deve ser true (1), caso contrário, esse usuário não poderá realizar o login.
*/
INSERT INTO `BNDES`.`user` (`use_id`,`rol_id`,`name`,`email`,`phone_number`,`institution`,`password`,`function_name`,`last_access`,`reset_token`,`active`)
					 VALUES(1,1,'nome','email','telefone','instituição',sha1('senha'),'',null,null,1);

/*
Access

Enfim, é preciso associar o usuário aos projetos. Caso o usuário seja responsável por um ou mais projetos, a coluna 'coordinator_responsible' deve ser true (1).
*/
INSERT INTO `BNDES`.`access` (`acc_id`,`prj_id`,`use_id`,`coordinator_responsible`) VALUES (1,1,1,0);
INSERT INTO `BNDES`.`access` (`acc_id`,`prj_id`,`use_id`,`coordinator_responsible`) VALUES (2,2,1,0);
INSERT INTO `BNDES`.`access` (`acc_id`,`prj_id`,`use_id`,`coordinator_responsible`) VALUES (3,3,1,0);
INSERT INTO `BNDES`.`access` (`acc_id`,`prj_id`,`use_id`,`coordinator_responsible`) VALUES (4,4,1,0);
INSERT INTO `BNDES`.`access` (`acc_id`,`prj_id`,`use_id`,`coordinator_responsible`) VALUES (5,5,1,0);
INSERT INTO `BNDES`.`access` (`acc_id`,`prj_id`,`use_id`,`coordinator_responsible`) VALUES (6,6,1,0);
INSERT INTO `BNDES`.`access` (`acc_id`,`prj_id`,`use_id`,`coordinator_responsible`) VALUES (7,7,1,0);