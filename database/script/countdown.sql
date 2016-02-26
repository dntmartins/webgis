-- ************************************ UPDATE DO STATUS DA REQUISIÇÃO *****************************************
SET SQL_SAFE_UPDATES=0;
UPDATE requisition
			SET requisition.status = 2
				WHERE DATEDIFF(CURRENT_TIMESTAMP, reject_date) >= 40;
SET SQL_SAFE_UPDATES=1;


-- ************************************ TESTE DO STATUS DA REQUISIÇÃO *****************************************
SELECT *
	FROM requisition
				WHERE DATEDIFF(CURRENT_TIMESTAMP, reject_date) >= 40;


-- ************************************** EVENTO COUNTDOWN ****************************************************
SET GLOBAL event_scheduler = ON;

DELIMITER $

CREATE EVENT CountDown 

    ON SCHEDULE EVERY 1 MINUTE

   -- STARTS '10:48:00' -- precisa ser no futuro

    DO BEGIN 
        SET SQL_SAFE_UPDATES=0;
		UPDATE requisition
			SET requisition.status = 4
				WHERE DATEDIFF(CURRENT_TIMESTAMP, reject_date) >= 40;
		SET SQL_SAFE_UPDATES=1;
    END

$ DELIMITER ;
-- ************************************** FIM DO EVENTO COUNTDOWN *********************************************

-- drop event CountDown;
-- show events;
-- SHOW VARIABLES LIKE "event_scheduler";

-- ************************************** DESENVOLVIMENTO E TESTE *********************************************
-- Para ambiente de desenvolvimento e teste (prazo de 1 hora para finalizar uma requisição recusada)

-- trocar este teste: DATEDIFF(CURRENT_TIMESTAMP, reject_date) >= 40
-- por este teste: HOUR(TIMEDIFF(CURRENT_TIMESTAMP, reject_date)) >= 1