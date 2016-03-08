SET NAMES 'UTF8';
INSERT INTO `resource` VALUES (1,'Área de trabalho');
INSERT INTO `resource` VALUES (2,'Administração');

INSERT INTO `role` VALUES (1,'Coordenador',0);
INSERT INTO `role` VALUES (2,'Admin',1);

INSERT INTO `privilege` VALUES (1,1,'Upload de shapefile');
INSERT INTO `privilege` VALUES (2,1,'Upload de sld');
INSERT INTO `privilege` VALUES (3,1,'Trocar senha');
INSERT INTO `privilege` VALUES (4,2,'Administrar usuários e permissões');

INSERT INTO `role_privilege` VALUES(1,1,1);
INSERT INTO `role_privilege` VALUES(2,1,2);
INSERT INTO `role_privilege` VALUES(3,1,3);
INSERT INTO `role_privilege` VALUES(4,2,4);

INSERT INTO `user` VALUES(1,1,'Coordenador','coordenador@coordenador.com','coordenador',sha1('coordenador'),null, null, 1);
INSERT INTO `user` VALUES(2,2,'Administrador','admin@admin.com','admin', sha1('admin'),null, null, 1);