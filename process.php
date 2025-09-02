<?php
//exit('exit');
require __DIR__ . "/config.php";
require(__DIR__ . "/vendor/autoload.php");
$Database = new \App\Database();

$files = scandir(__DIR__ . "/verbetes_a");
$files = array_diff($files, ['.', '..']);
foreach ($files as $file) {
    echo "Verificando $file<br>";
    $sql = "SELECT id FROM file_queue WHERE file = :file";
    $exist = $Database->select($sql, ['file' => $file])->rowCount();
    if($exist){
        echo "$file já existe no banco de dados<br>";
    }else{
        $sql = "INSERT INTO file_queue SET file = :file, processed = 0";
        if($Database->insert($sql, ['file' => $file])){
            echo "$file inserido no banco de dados<br>";
        }else{
            echo "Erro ao inserir $file no banco de dados<br>";
        }
    }
}

exit();


use Spatie\PdfToText\Pdf;

$Uploader = new CoffeeCode\Uploader\File("uploads", "files");
$max_process = $_GET['max_process'] ?? 5;
$max_process = (int) $max_process;

$sql_files = "SELECT file,id FROM file_queue WHERE processed = 0 LIMIT $max_process";
$select_files = $Database->select($sql_files, []);
$tot_processed = 0;

if ($select_files->rowCount()) {
    $all_files = $select_files->fetchAll();
    foreach ($all_files as $item){
        $sql_up_temp = "UPDATE file_queue SET processed = 2 WHERE id =:id";
        $Database->update($sql_up_temp, ['id' => $item->id]);
    }
    foreach ($all_files as $item_file) {
        $f_id = $item_file->id;
        $pdf_path = __DIR__ . "/verbetes_a/$item_file->file";
        $text = Pdf::getText($pdf_path);

        // remove o termo Enciclopédia da Conscienciologia de cada página
        $text = preg_replace("/([0-9])?\s?\n?Enciclopédia da Conscienciologia\s?\n?([0-9])?/", "\n", $text);
        $text = trim($text, "Enciclopédia da Conscienciologia"); // remove do início

        $text = preg_replace("/(-|–)(\n+)/", "", $text);

        $text = preg_replace("/\n+/"," ", $text);

        //$text = $slugify->slugify($text);
         $text = mb_strtolower($text);
       // $text = preg_replace("/“|”|\"/", " ", $text)
       // $text = str_replace([".",",",":","(",")","“","”","\"","[","]",";","ª","ª","º","{","}",">",">"], " ", $text);
        $text = preg_replace("/[^a-zàáâãäåçèéêëìíîïðòóôõöùúûüýÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÐÒÓÔÕÖÙÚÛÜÝñÑ\s\-−']/iu", " ", $text);
        //$text = str_replace([" '", "' "], " ", $text); // remove 'aspas' no inicio e final
// Remover caracteres especiais e quebrar a string em palavras
      //  $all_words = str_word_count($text, 1, 'àáâãäåçèéêëìíîïðòóôõöùúûüýÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÐÒÓÔÕÖÙÚÛÜÝñÑ–−');
        $all_words = explode(" ", $text);
        $unique_words = array_unique($all_words);
       //print_r($unique_words);
        //exit();

        $sql = "SELECT word, repeat_times FROM word_list";
        $select = $Database->select($sql, []);
        $db_words = [];
        if ($select->rowCount()) {
            $data = $select->fetchAll();
            foreach ($data as $item) {
                $db_words[] = $item->word;
            }
        }
        $sql = "INSERT INTO word_list SET word = :word, repeat_times = :rt";
        $sql_update = "UPDATE word_list SET repeat_times = repeat_times+1 WHERE word = :word";

        foreach ($unique_words as $word) {
            if (strlen($word) > 140) {
                continue;
            }
            if(strlen(trim($word)) < 1){
                continue;
            }
            if (in_array($word, $db_words)) {
                if (!$Database->update($sql_update, ['word' => $word])) {
                    echo "Erro ao atualizar para $word<br>";
                }
            } else {
                if (!$Database->insert($sql, ['word' => $word, 'rt' => 1])) {
                    echo "Erro ao insrir $word<br>";
                }
            }

        }
        $update_files = "UPDATE file_queue SET processed = 1 WHERE id = :id";
        if($Database->update($update_files, ['id' => $f_id])){
            $tot_processed++;
            echo "$item_file->file com id $f_id processado<br>";
        }

    }

} else {
    exit("Sem mais files para seres processados");
}

exit('<hr>');
if ($_FILES) {
    try {
        $name = microtime();
        $file_path = $Uploader->upload($_FILES['pdf'], $name);
        echo "<p><a href='{$file_path}' target='_blank'>Ver Arquivo</a></p>";
    } catch (Exception $e) {
        echo "<p>(!) {$e->getMessage()}</p>";
    }
}

$dicionarizados = file_get_contents(__DIR__ . "/dicionarizados.txt");
$dicionarizados = explode("\n", $dicionarizados);
$file_path = "/verbetes_a/Vontade Ternaria.pdf";


