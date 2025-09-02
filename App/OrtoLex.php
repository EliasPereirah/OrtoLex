<?php
namespace App;

use CoffeeCode\Uploader;
use Spatie\PdfToText\Pdf;
use Cocur\Slugify\Slugify;
use App\Database;

class OrtoLex
{
    private Uploader\Uploader $Uploader;
    private \Cocur\Slugify\Slugify $Slugify;

    public function __construct()
    {

        $this->mimes = [
            "application/pdf",
            "text/plain",
            "application/rtf",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "text/html",
            "application/msword"

        ];


        $this->extensions = [
            "pdf",
            "txt",
            "rtf",
            "docx",
            "html",
            "htm",
            "doc"];

        $this->Uploader = new \CoffeeCode\Uploader\Send("uploads", "pdf", $this->mimes, $this->extensions, false);
        $this->Slugify = new \Cocur\Slugify\Slugify();
    }

    public function startUpload()
    {
        if (!empty($_FILES)) {
            try {
                $name = $_FILES['pdf']['name'] ?? 'no_name';
                if (strlen($name) < 6) {
                    $name = "name_{$name}";
                }
                $name = rtrim($name, ".pdf");
                $name = $this->Slugify->slugify($name);
                $file_path = $this->Uploader->upload($_FILES['pdf'], $name);
                return $file_path;
            } catch (\Exception $e) {
                echo "<div class='presentation'><p class='exception'>(!) {$e->getMessage()}</p></div>";
            }
        }
        return false;
    }

    private function getWordList(int $min_repeat)
    {
        $min_repeat = (int)$min_repeat;
        $Database = new Database();
        $sql = "SELECT word, repeat_times FROM word_list WHERE repeat_times >= :min_rp";
        $binds = ['min_rp' => $min_repeat];
        $select = $Database->select($sql, $binds);
        if ($select->rowCount()) {
            return $select->fetchAll();
        }
        return [];
    }

    public function analyze(string $file, int $min_repeat, bool $is_verbete, $fromPage = false, $toPage = false)
    {
        if ($fromPage && $toPage) {
            $fromPage = (int) $fromPage;
            $toPage = (int) $toPage;
            $option = ["-f $fromPage", "-l $toPage"];
        } else {
            $option = []; // all pages
        }
        $text = $this->getText($file, $option);

        // mudança
        $questionologia = ''; // todos os textos após a Questionologia.
        if($is_verbete) {
            $parts_txt = explode("Questionologia.", $text);
            $total_parts = count($parts_txt);
            $real_text = "";
            if ($total_parts > 2) {
                for ($i = 0; $i < $total_parts; $i++) {
                    if ($i == ($total_parts - 1)) {
                        $questionologia = $parts_txt[$i];
                    } else {
                        $real_text .= $parts_txt[$i];
                    }
                }
            } elseif (!empty($parts_txt[1])) {
                $real_text = $parts_txt[0];
                $questionologia = $parts_txt[1];
            } else {
                $real_text = $text;
            }
            $text = $real_text;
        }
        /// mudança

        $text = $this->cleanText($text);

        $pdf_words = explode(" ", $text);
        $pdf_words = array_unique($pdf_words);


        $word_list_raw = $this->getWordList($min_repeat); // palavras do banco de dados(palavras extraídas de vários verbetes)

        $dicio_list = file_get_contents(__DIR__ . "/../dicio_pt.txt");
        $dicio_list = mb_strtolower($dicio_list);
        $dicio_list = explode("\n", $dicio_list);


        $dicio_two = file_get_contents(__DIR__ . "/../dicionarizados.txt");
        $dicio_two = mb_strtolower($dicio_two);
        $dicio_two = explode("\n", $dicio_two);

        $english_dicio_list = file_get_contents(__DIR__ . "/../english_dicio.txt");
        $english_dicio_list = preg_replace("/\s+/", "\n", $english_dicio_list);
        $english_dicio_list = explode("\n", $english_dicio_list);

        $word_list = [];
        foreach ($word_list_raw as $item) {
            $word_list[] = $item->word;
        }
        $errors = 0;
        $low_freq_words = [];
        foreach ($pdf_words as $word) {
            if (strlen(trim($word) < 1)) {
                continue;
            }
            if (!in_array($word, $word_list) && !in_array($word, $dicio_list) && !in_array($word, $english_dicio_list) && !in_array($word, $dicio_two)) {
                   if(substr_count($word, "-") > 0){
                       $hyphen_words = explode("-", $word);
                       $can_add_as_wrong = false;
                       foreach ($hyphen_words as $hw){
                           if(!in_array($hw, $word_list) && !in_array($hw, $dicio_list) && !in_array($hw, $english_dicio_list)){
                               //$low_freq_words[] = str_replace("$hw", "<span class='red_error'>$hw</span>", $word);
                               $word = str_replace("$hw", "<span class='red_error'>$hw</span>", $word);
                               $can_add_as_wrong = true; // entrou aqui, significando que pelo menos uma das palavras não
                               // está na base

                           }
                       }
                       if($can_add_as_wrong){
                           $low_freq_words[] = $word;
                       }

                   }else{
                       $low_freq_words[] = $word;
                   }
            }
        }


        $questionologia_low_freq_words = [];
        if(strlen($questionologia) > 5){
            $questionologia = $this->cleanText($questionologia);
            $ques_words = explode(" ", $questionologia);
            $ques_words = array_unique($ques_words);
            foreach ($ques_words as $word) {
                if (strlen(trim($word) < 1)) {
                    continue;
                }
                if (!in_array($word, $word_list) && !in_array($word, $dicio_list) && !in_array($word, $english_dicio_list) && !in_array($word, $dicio_two)) {
                    if(substr_count($word, "-") > 0){
                        $hyphen_words = explode("-", $word);

                        foreach ($hyphen_words as $hw){
                            if(!in_array($hw, $word_list) && !in_array($hw, $dicio_list) && !in_array($hw, $english_dicio_list)){
                                //$questionologia_low_freq_words[] = str_replace("$hw", "<span class='red_error'>$hw</span>", $word);
                                $word = str_replace("$hw", "<span class='red_error'>$hw</span>", $word);
                            }
                        }
                        $questionologia_low_freq_words[] = $word;

                    }else{
                        $questionologia_low_freq_words[] = $word;
                    }
                }
            }
        } // end if questionologia


        $low_freq_words = array_unique($low_freq_words); // devido hyphen_words
        $questionologia_low_freq_words = array_unique($questionologia_low_freq_words);
        $arr = ['low_freq' =>$low_freq_words, 'low_freq_questionologia' => $questionologia_low_freq_words];
        return $arr;
    }

    public function getText($file_path, $option)
    {
       // $total_pages = (int) $this->getPdfTotalPages($file_path);
        try {
            $info = pathinfo($file_path);
            $ex = '';
            if(!empty($info['extension'])){
                $ex = $info['extension'];
                $ex = strtolower($ex);
            }
            if($ex == 'txt'){
                $text = file_get_contents($file_path);
            }elseif ($ex == "pdf"){
                $text = Pdf::getText($file_path, null, $option);
            }else{
                $text = $this->DocumentToText($file_path);
                if(!$text){
                    throw new \Exception("<div class='exception'>Erro ao extrair texto do arquivo.</div>");
                }
            }
            return $text;
        } catch (\Exception $e) {
            echo "<p class='exception'>Erro ao extrair texto do arquivo.</p>";
            return "";
        }
    }

    public function getPdfTotalPages($file)
    {
        $total = 0;
        if (is_file($file)) {
            $file = escapeshellarg($file);
            exec("pdfinfo $file | grep -Po 'Pages:[[:space:]]+\K[[:digit:]]'", $output);
            $total = $output[0] ?? 0;
        }
        return $total;
    }

    private function cleanText($text)
    {


        $regex = "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@"; // remove links
        $text = preg_replace($regex, " ", $text);
        $text = preg_replace("/([0-9])?\s?\n?Enciclopédia da Conscienciologia\s?\n?([0-9])?/", "\n", $text);
        $text = preg_replace("/-­\n+/","-\n", $text);
        $text = preg_replace("/­\n+/","-\n", $text);
        $text = preg_replace("/­/","", $text);
        $text = preg_replace("/(-|–)(\n+)/", "", $text);
        $text = preg_replace("/\n+/", " ", $text);
        $text = mb_strtolower($text);
        $text = preg_replace("/[^a-zàáâãäåçèéêëìíîïðòóôõöùúûüýÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÐÒÓÔÕÖÙÚÛÜÝñÑ\s\-−']/iu", " ", $text);
        $text = preg_replace("/\s+/u", " ", $text);
        return $text;
    }


    public function DocumentToText($file_path)
    {
        $directory = dirname($file_path);
        putenv("HOME=/tmp");
        $txt_path = substr($file_path, 0, strrpos($file_path, ".")).".txt";
        if(!is_file($txt_path)){
            $file_path = escapeshellarg($file_path);
            //\"txt:Text (encoded):UTF8\"
            $cmd = "libreoffice --headless --convert-to \"txt:Text (encoded):UTF8\"  $file_path --outdir $directory";
            exec($cmd, $output);
            $response = implode(" ", $output);
            if(preg_match("/convert\s+\/var\/www\/html/", $response)){
                $text = file_get_contents($txt_path);
                if(!unlink($txt_path)){
                    echo "<div class='presentation'>Erro ao deletar arquivo versão texto</div>";
                }
                return $text;
            }
        }else{
            // o arquivo já existe e isso não deveria acontecer
            echo "<div class='presentation'>Arquivo erro: DocumentToText</div>";
        }

        return false;
    }

    public function Hunspell($word){
        //$file_path = escapeshellarg($file_path);
        $word = escapeshellarg($word);
        // $cmd = "cat $file_path | /usr/bin/hunspell -d pt_BR -l -i utf-8";
        $cmd = "echo $word | /usr/bin/hunspell -d pt_BR -l -i utf-8";
        exec($cmd, $output, $return_var);
        if($return_var !=0){
            echo "<div class='exception'>Erro ao executar Hunspell, code: $return_var</div>";
        }
        return count($output);
    }

}
