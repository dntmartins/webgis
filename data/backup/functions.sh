

# Remove last old version
function removeOld {

    # Resetando as variáveis
    unset TESTE
    unset myarray
    unset TA
    unset myarray2

    # Filtra a pasta onde estão os backups e captura somente seus arquivos
    # O retorno é uma string com todos os elementos separados por virgula
    TESTE=$(find $1 -maxdepth 1 -name "portalbndes*" -type f -printf "%f,")

    # Divide a string capturada acima, criando um novo elemento sempre que houver
    # o separador vírgula
    IFS=',' read -a myarray <<< $TESTE

    # Recupera todos os elementos do array de uma vez
    # e executa a ordenação levando em consideração espaço como separador
    TA=$(echo ${myarray[@]} | sed 's/ /\n/g' | sort)

    # Cria um novo array a partir da string criada acima
    # o criterio de criação de novos elementos é o contido em IFS
    IFS=' ' read -a myarray2 <<< $TA

    # Capturando o tamanho total do array
    TT=$(echo ${#myarray2[@]})

    # Verifica se existe mais de um arquivo, caso exista somente 1 ele não exclui
    # Garantindo sempre 2 cópias de backup da aplicação
    if [ $TT -gt 1 ]; then
        # Pega o primeiro elemento do array, o mais antigo e remove
        rm -rf $1/${myarray2[0]}
        echo $1/${myarray2[0]}
    fi

}

# Função de log
# Créditos a quem fez ela na máquina do svn
function log() {
    echo $1 >> $LOG_PATH/$LOG_FILE
}
