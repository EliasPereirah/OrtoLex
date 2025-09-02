<?php
namespace App;
class SmartAction
{
    private array $suggestions = [];
    // IP Anonymization https://github.com/geertw/php-ip-anonymizer/
    /**
     * @var string IPv4 netmask used to anonymize IPv4 address.
     */
    public $ipv4NetMask = "255.255.255.0";

    /**
     * @var string IPv6 netmask used to anonymize IPv6 address.
     */
    public $ipv6NetMask = "ffff:ffff:ffff:ffff:0000:0000:0000:0000";

    public function timeToSeconds(int $hour, int $minute, int $seconds)
    {
        return (($hour * 60) * 60) + ($minute * 60) + $seconds;
    }


    public function shortTitle($title){
        $original_title = $title;
        $crop =  [".wmv",".avi",".mp4","LOW RES -","| - Waldo Vieira","Acervo Tertuliarium -"];
        $title = str_replace($crop, "", $title);
        $title = preg_replace("/Aula\s+\d+\s+-/i", "", $title);
        $title = preg_replace("/C(í|i)rculo\s+Mentalsom(á|a)tico\s+\d+\s+-/i", "", $title);
        $title = preg_replace("/Tert(ú|u)lia\s+Matinal\s+\d+\s+-/i", "", $title);
        $title = preg_replace("/Are(ó|o)pago\s+Conscienciol(ó|o)gico\s+\d+\s+-/","", $title);
        $title = preg_replace("/-\s+-/", "-", $title);
        $title = preg_replace("/\|\s+-/", "|", $title);
        if(strlen($title) < 10){
            return $original_title;
        }
        return trim($title);
    }


    public function suggest($term) {
        if (empty($this->suggestions)) {
            $json = file_get_contents(__DIR__."/../suggestions.json");
            $this->suggestions = json_decode($json, true);
        }
        $suggestion = strtolower($term);
        if (array_key_exists($suggestion, $this->suggestions)) {
            return $this->suggestions[$suggestion];
        } else {
            return '';
        }
    }


    /**
     * Remove pagination from search term: Example: consciência/2 -> consciência
     */
    public function getTerm($term)
    {
        $term = trim($term);
        $explodedTerm = explode('/', $term);
        if(count($explodedTerm) > 1) {
            $term = $explodedTerm[0];
        }
        return $term;

    }

    public function getNextPage($term): int
    {
        $term = trim($term);
        $explodedTerm = explode('/', $term);
        if(count($explodedTerm) > 1) {
            $nextPage = (int) $explodedTerm[1];
            $nextPage++;
            if($nextPage > 99999999){
                $nextPage = 99999999;
            }
            if($nextPage > 1) {
                return $nextPage;
            }
        }
        return 2;
    }

    public function cleanTerm($term){
        $term = preg_replace("/[^[:alnum:][:space:]]/u", " ", $term); // Remove all non-alphanumeric characters
        return preg_replace("/\s+/", " ", $term);  // remove all multiple spaces to a single space

    }




    /**
     * Anonymize an IPv4 or IPv6 address.
     *
     * @param $address string IP address that must be anonymized
     * @return string The anonymized IP address. Returns an empty string when the IP address is invalid.
     */
    public function anonymize($address) {
        $packedAddress = inet_pton($address);

        if (strlen($packedAddress) == 4) {
            return $this->anonymizeIPv4($address);
        } elseif (strlen($packedAddress) == 16) {
            return $this->anonymizeIPv6($address);
        } else {
            return "";
        }
    }

    /**
     * Anonymize an IPv4 address
     * @param $address string IPv4 address
     * @return string Anonymized address
     */
    public function anonymizeIPv4($address) {
        return inet_ntop(inet_pton($address) & inet_pton($this->ipv4NetMask));
    }

    /**
     * Anonymize an IPv6 address
     * @param $address string IPv6 address
     * @return string Anonymized address
     */
    public function anonymizeIPv6($address) {
        return inet_ntop(inet_pton($address) & inet_pton($this->ipv6NetMask));
    }


}