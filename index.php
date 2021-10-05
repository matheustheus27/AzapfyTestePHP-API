<?php
    include "ControlePagamento.php";

    printJson("http://homologacao3.azapfy.com.br/api/ps/notas");
    saveJsonFile("notas", "http://homologacao3.azapfy.com.br/api/ps/notas");
