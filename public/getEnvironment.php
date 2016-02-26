<?php
/**
 * Este script é utilizado pelo script shell que dispara o processo de leitura de e-mails do servidor de e-mails e registra as requisições.
 * Esta variável é necessária para definir o nome do host que irá compor a URL de disparo.
 */
echo getenv("APPLICATION_ENV");