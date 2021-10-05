<?php
    $http_response_header = 'Content-Type: application/json';
    header($http_response_header);

    function printJson($url){

        $api = returnJson($url);

        echo json_encode($api);
    }
    function saveJsonFile($filename, $url){
        $api= json_encode(returnJson($url));
        $file = fopen($filename.".json", "w");

        fwrite($file, $api);
        fclose($file);
    }

    function returnJson($url){
        $data =  json_decode(file_get_contents($url), true);
        $api = [createArray($data)];
        return $api[0];
    }

    function createArray($data): array{
        $notas = null;
        foreach($data as $info) {
            if(is_null($notas) == true or !array_key_exists($info["cnpj_remete"], $notas)){
                $notas[$info["cnpj_remete"]] = createNota($info);
            }else{
                $notas[$info["cnpj_remete"]] = updateNota($info, $notas[$info["cnpj_remete"]]);
            }
        }

        return $notas;
    }

    function createNota($info){
            $nota = [
                "nome" => $info["nome_remete"],
                "cnpj" => $info["cnpj_remete"],
                "notas" =>[
                    $info["chave"] => defineNotaFiscal($info)
                ],
                "valor_total" => $info["valor"],
                "volume_total" => $info["volumes"],
                "valor_receber" => 0,
                "volume_entregue" => 0,
                "valor_pendente" => 0,
                "volume_pendente" => 0,
                "valor_perdido" => 0,
                "volume_perdido" => 0
            ];

            return calculeValores($info, $nota);
    }
    function updateNota($info , $nota){
        $novaNota = $nota["notas"];
        $novaNota[$info["chave"]] = defineNotaFiscal($info);

        $nota["notas"] = $novaNota;
        $nota["valor_total"] = number_format((float)$nota["valor_total"] + $info["valor"], 2, ".", "");
        $nota["volume_total"] = $nota["volume_total"] + $info["volumes"];

        return calculeValores($info, $nota);
    }

    function defineData($info){
        if($info["status"] == "COMPROVADO"){
           return [
               "dt_emis" => $info["dt_emis"],
               "dt_entrega" => $info["dt_entrega"]
           ];
        }

        return  [
            "dt_emis" => $info["dt_emis"]
            ];
    }
    function defineNotaFiscal($info){
        return [
            "numero" => $info["numero"],
            "dest" => $info["dest"],
            "transp" => [
                "nome" => $info["nome_transp"],
                "cnpj" => $info["cnpj_transp"]
            ],
            "data" => defineData($info),
            "status" => $info["status"],
            "valor" => $info["valor"],
            "volumes" => $info["volumes"]
        ];
    }

    function calculeValores($info, $nota){
            $intervalo = 10;
            if($info["status"] == "COMPROVADO"){
                $dataEmi = str_replace("/", "-", $info["dt_emis"]);
                $dataEnt = str_replace("/", "-",  $info["dt_entrega"]);

                $dataEmi = new DateTime($dataEmi);
                $dataEnt = new DateTime($dataEnt);

                $intervalo =  $dataEnt -> diff($dataEmi);
            }

            if($info["status"] == "COMPROVADO" and $intervalo->d <= 2){
                $nota["valor_receber"] = $nota["valor_receber"] + $info["valor"];
                $nota["volume_entregue"] = $nota["volume_entregue"] + $info["volumes"];
            }else{
                if($info["status"] == "ABERTO"){
                    $nota["valor_pendente"] = $nota["valor_pendente"] + $info["valor"];
                    $nota["volume_pendente"] = $nota["volume_pendente"] + $info["volumes"];
                }else{
                    $nota["valor_perdido"] = $nota["valor_perdido"] + $info["valor"];
                    $nota["volume_perdido"] = $nota["volume_perdido"] + $info["volumes"];
                }
            }

        return $nota;
    }