<?php
require __DIR__ . "/../config.php";
require(__DIR__ . "/../vendor/autoload.php");
$OrtoLex = new \App\OrtoLex();
set_time_limit(300);

?><!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=yes, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="favicon.png">
    <title>OrtoLex</title>
    <meta name="description" content="OrtoLex ajuda você a encontrar erros de digitação em seu verbete conscienciológico.">
    <link rel="stylesheet" href="css/style.css?v=<?= rand(0,9999); ?>">
    <script async src="script.js?v=<?= rand(0,9999); ?>"></script>
</head>
<body>
<div class="content">
    <div class="app">
        <form id="upform" method="post" enctype="multipart/form-data">
            <input id="js_file" type="file" name="pdf">
            <p>O arquivo é um verbete?
                <select name="is_verbete" required="required">
                    <option selected="true" disabled="disabled">Selecione</option>
                    <option value="yes">Sim</option>
                    <option value="no">Não</option>
                </select>
            </p>

            <p>Autoriza armazenamento das palavras encontradas?
                <select name="can_store" required="required">
                    <option selected="true" disabled="disabled">Selecione</option>
                    <option value="yes">Sim</option>
                    <option value="no">Não</option>
                </select>
            </p>
            <button>Analisar</button>
        </form>
    </div>
        <?php
        $file_path = $OrtoLex->startUpload();
        if($file_path){
            echo "<div class='presentation gold'>";
            $hash = hash_file('sha256', $file_path);
            $file_history = __DIR__."/history/{$hash}.json";
            $min_repeat = $_POST['min_repeat'] ?? 6;
            $min_repeat = (int) $min_repeat;
            $is_verbete = $_POST['is_verbete'] ?? 'no';
            if($is_verbete == 'yes'){
                $is_verbete = true;
            }else{
                $is_verbete = false;
            }
            $is_verbete = (bool) $is_verbete;


            $can_store = $_POST['can_store'] ?? 'no';
            if($can_store == 'yes'){
                $can_store = true;
            }else{
                $can_store = false;
            }
            $can_store = (bool) $can_store;

            if(is_file($file_history)){
                $cnt = file_get_contents($file_history);
                $json = json_decode($cnt);
                if(!unlink($file_path)){
                    echo "Ops, erro ao remover arquivo<br>";
                }
                echo "<h2>Lista de Palavras Para Análise</h2>";
                echo "<p>Confira abaixo lista de palavras para análise.</p>";
                if(!empty($json->low_freq)){
                    echo "<div class='block_list'>";
                    foreach ($json->low_freq as  $lfw){
                        $lfw = str_replace("--", "<span class='red_error'>--</span>", $lfw);
                        echo "<div class='block'> ";
                        echo "$lfw";
                        echo "</div> ";
                    }
                    echo "</div>"; //end block_list

                }else{
                    echo "<p class='good'>Nossa analise não encontrou nenhuma palavra que não exista em nossa base de dados.</p>";
                }

                if(!empty($json->low_freq_questionologia)){
                    echo "<div class='block_list'>";
                    echo "<h3 class='reference'>Seção Questionologia/bibliografia/webgrafia</h3>";
                    echo "<p>OBS: É comum haver falsos positivos nessa seção.</p>";
                    foreach ($json->low_freq_questionologia as $lfw){
                        echo "<div class='block'> ";
                        echo "$lfw";
                        echo "</div> ";
                    }
                    echo "</div>"; //end block_list

                }

            }else{
                $data = new stdClass();
                echo "<h2>Lista de Palavras Para Análise</h2>";
                echo "<p>Confira abaixo lista de palavras para maiores análises.</p>";
                $file_path = __DIR__ . "/$file_path";
                $file_link  = basename($file_path);
                // echo "<div class='info'><a target='_blank' href=\"uploads/pdf/$file_link\">Ver arquivo</a></div>";
                $low_freq_word = $OrtoLex->analyze($file_path, $min_repeat, $is_verbete);
                if(!unlink($file_path)){
                    echo "Ops, erro ao remover arquivo<br>";
                }
                if($low_freq_word['low_freq']){
                    $data->low_freq = $low_freq_word['low_freq'];
                    echo "<div class='block_list'>";
                    foreach ($low_freq_word['low_freq'] as $lfw){
                        $lfw = str_replace("--", "<span class='red_error'>--</span>", $lfw);
                        echo "<div class='block'> ";
                        echo "$lfw";
                        echo "</div> ";
                    }
                    echo "</div>"; //end block_list

                }else{
                    echo "<p class='good'>Nossa análise não encontrou nenhuma palavra que não exista em nossa base de dados.</p>";
                }

                if($low_freq_word['low_freq_questionologia']){
                    $data->low_freq_questionologia = $low_freq_word['low_freq_questionologia'];
                    echo "<div class='block_list'>";
                    echo "<h3 class='reference'>Dados de referência/bibliografia/webgrafias/etc</h3>";
                    echo "<p>OBS: É comum haver falsos positivos nesta seção.</p>";
                    foreach ($low_freq_word['low_freq_questionologia'] as $lfw){
                        echo "<div class='block'> ";
                        echo "$lfw";
                        echo "</div> ";
                    }
                    echo "</div>"; //end block_list

                }else{
                    if($is_verbete){
                        // echo "Nenhum palavra tem menos de $min_repeat repetições nas referências<br>";
                    }
                }
                if($can_store){
                    $json_encoded = json_encode($data);
                    file_put_contents($file_history, $json_encoded);
                }
            }
            echo "</div>"; //end presentation

        }


        ?>

    <div class="presentation two">
        <h2>O que é?</h2>
        <p>Ferramenta de detecção de possíveis erros de digitação considerando muitos dos neologismos conscienciológicos.</p>
        <h2>Recomendação</h2>
        <p>O uso dessa ferramenta é indicado após a revisão humana, podendo auxiliar a encontrar erros de digitação.</p>
        <h2>Como funciona?</h2>
        <p>Ao enviar o arquivo(PDF ou texto) é analisado em quantos verbetes a palavra aparece ou se ela é uma palavra dicionarizada.</p>
        <p>Caso não seja uma palavra dicionarizada nem apareça em pelo menos 6 verbetes a palavra será exibida para sua
            análise.
        </p>
        <h2>O que preciso saber?</h2>
        <p>Nossa base de dados não inclui todos verbetes nem possui todas palavras dicionarizadas, por isso é normal
            que algumas delas apareçam mesmo estando corretas.
        </p>
        <h2>Palavras em vermelho</h2>
        <p>Palavras em vermelho não significa mais chances de estarem erradas, é apenas uma forma de apontar qual das palavras
            precisa ser analisada quando ela é composta e separada por hífen. <br>Exemplos:<br> arco-<span class="red_error">iris</span><br>
            arranha-<span class="red_error">ceu</span><br><span class="red_error">beja</span>-flor<br> <span class="red_error">elememto</span>-chave.
        </p>
        <h2>Links quebrados</h2>
        <p>Caso apareça trechos de links(URLs) como se fossem palavras compostas, verifique se o link em questão estar
            quebrado, geralmente devido quebra de linhas.

        </p>
        <h2>Palavras inexistentes</h2>
        <p>A depender do tipo de arquivo enviado é possível que surja palavras inexistentes devido o processo de
            recuperação do arquivo para o formato texto.
        </p>
        <h2>Privacidade</h2>
        <p>O arquivo enviado é deletado automaticamente, a lista de palavras encontradas para analise só será armazenada
            caso você autorize o armazenamento das mesmas. Essa opção evita reprocessamento caso o mesmo arquivo seja
            enviado novamente que é identificado por hash. Isso significa que temos o hash do arquivo, mas não o arquivo em si, com isso
            identificamos o reenvio de arquivo já processado caso a opção armazenar seja selecionada.
        </p>
    </div>
