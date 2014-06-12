<?php
class Search{
        private $_db,
                $_data;

    public function __construct(){
        $this->_db = DB::getInstance();
    }

    public function search_perform($table, $args = array(), $terms){

        $terms = $this->search_split_terms($terms);
        $terms_db = $this->search_db_escape_terms($terms);
        $terms_rx = $this->search_rx_escape_terms($terms);

        $parts = array();
        foreach($terms_db as $term_db){
            if(!empty($args)){
                foreach($args as $arg){
                    $parts[] = "`$arg` LIKE '$term_db'";
                }
            }else {
                throw new Exception("Please enter an arguement!");
            }
        }
        $parts = implode(' OR ', $parts);

        $sql = "SELECT * FROM $table WHERE $parts";

        $data = $this->_db->query($sql);
        if($data->count()){
            $this->_data = $data->results();
        }

    }

    public function search_split_terms($terms){

        $terms = preg_replace("/\"(.*?)\"/", "search_transform_term('\$1')", $terms);
        $terms = preg_split("/\s+|,/", $terms);

        $out = array();

        foreach($terms as $term){

            $term = preg_replace("/\{WHITESPACE-([0-9]+)\}/", "chr(\$1)", $term);
            $term = preg_replace("/\{COMMA\}/", ",", $term);

            $out[] = $term;
        }

        return $out;
    }

    private function search_transform_term($term){
        $term = preg_replace("/(\s)/e", "'{WHITESPACE-'.ord('\$1').'}'", $term);
        $term = preg_replace("/,/", "{COMMA}", $term);
        return $term;
    }

    private function search_escape_rlike($string){
        return preg_replace("/([.\[\]*^\$])/", '\\\$1', $string);
    }

    private function search_db_escape_terms($terms){
        $out = array();
        foreach($terms as $term){
            $out[] = '%'.AddSlashes($this->search_escape_rlike($term)).'%';
        }
        return $out;
    }

    private function search_rx_escape_terms($terms){
        $out = array();
        foreach($terms as $term){
            $out[] = '\b'.preg_quote($term, '/').'\b';
        }
        return $out;
    }

    public function search_html_escape_terms($terms){
        $out = array();

        foreach($terms as $term){
            if (preg_match("/\s|,/", $term)){
                $out[] = '"'.HtmlSpecialChars($term).'"';
            }else{
                $out[] = HtmlSpecialChars($term);
            }
        }

        return $out;
    }

    public function search_pretty_terms($terms_html){

        if (count($terms_html) == 1){
            return array_pop($terms_html);
        }

        $last = array_pop($terms_html);

        return implode(', ', $terms_html)." and $last";
    }

    public function results(){
        return $this->_data;
    }
    public function first(){
        return $this->_data[0];
    }

}
?>
