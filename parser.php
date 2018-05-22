<?php
//  by onn@onndo.com 10.12.2015

class parser {
    private $retry = 3;                     // num's retry curl request's
    private $use_cache = false;             // 4 debug - use local cache url's
    private $template = './template.html';  // template 4 out
    private $result = '';                   // result html data
    private $allowed = array (               // array sites allowed to parse
                                'cdw.com'     => 'parse_cdw_com',
                                'newegg.com'  => 'parse_newegg_com',
                                'bhphotovideo.com' => 'bhphotovideo_com',
                                'tigerdirect.com' => 'tigerdirect_com',
                                'biz.tigerdirect.com' => 'biz_tigerdirect_com',
                             );
    private $lastHttpError = "";

    public function get_result() {
        return $this->result;
    }

    private function run() {
        if( strlen($_POST['url'])>16 && strlen($_POST['site']) ) {
            $site = trim($_POST['site']);
            if( isset($this->allowed[$site]) ) {
                $func = $this->allowed[$site];
                $data = $this->get_cached_url($_POST['url']);
                if( $data !== FALSE ) {
                    $arr = $this->$func ($data);
                    if( $this->use_cache )
                        print_r($arr);
                    if( count($arr) )
                        return $this->templater($arr);
                     else
                        $this->result = 'No any results parsed.';
                    //print_r($data);
                } else {
                    $this->result = 'HTTP Errror: '.$this->lastHttpError;
                }
            }
            else {
                $this->result = 'Unsupported Site || Incorrect Select.';
            }
        } else {
            $this->result = 'Incorrect URL.';
        }
    }

    private function templater($arr) {
        $tpl = @file_get_contents($this->template);
        ob_start();
        if( count($arr) && strlen($tpl) )
            foreach ( $arr as $blocktitle => $data )
                if( count($data) )
                    eval("?>{$tpl}");
        $this->result = ob_get_contents();
        ob_end_clean();
    }


    private function tigerdirect_com($str) {
        $xpath = @new DOMXPath($doc=DOMDocument::loadHTML($str));
        $out = array();
        $head = $title = $name = $value = '';
        foreach ( $xpath->query("//table[contains(@class,'prodSpec')]/tbody/tr") as $node ) {
            $title = trim(@$xpath->query("th/h5",$node)->item(0)->textContent);
            $head = strlen($title)?$title:$head;
            $name  = trim(@$xpath->query("th",$node)->item(0)->textContent);
            $value = trim(@$xpath->query("td",$node)->item(0)->textContent);
            $name  = strip_tags($name); // а вдруг ?
            $value  = strip_tags($value); // а вдруг ?
            if( strlen($name) && strlen($value)  )
                $out[$head][][$name]=$value;
        }
        return $out;
    }


    private function biz_tigerdirect_com($str) {
        $xpath = @new DOMXPath(DOMDocument::loadHTML($str));
        $out = array();
        $id  = trim(@$xpath->query("//div[contains(@class,'info')]/input[contains(@id,'itemKey')]/@value",$node)->item(0)->nodeValue);
        $xpath = null;
        if( $id>0 ) {
            $ajaxDate = time().rand(100,999);
            $url = "http://biz.tigerdirect.com/productTab?type=2&itemKey={$id}&picGroupKey=0&selectedWarranty=&ajaxDate={$ajaxDate}&_={$ajaxDate}";
            $str = $this->get_cached_url($url);
            if( $data!==FALSE ) {
                $xpath = @new DOMXPath(DOMDocument::loadHTML($str));
                $head = $title = $name = $value = '';
                foreach ( $xpath->query("//div[contains(@class,'prodSpec')]/ul/li") as $node ) {
                    $title = trim(@$xpath->query("span[contains(@class,'psSubtitle')]",$node)->item(0)->textContent);
                    $head = strlen($title)?$title:$head;
                    foreach ($xpath->query("ul/li",$node) as $node2) {
                        $name  = trim(@$xpath->query("span",$node2)->item(0)->textContent);
                        $value = trim(@$xpath->query("span",$node2)->item(1)->textContent);
                        $name  = strip_tags($name); // а вдруг ?
                        $value = strip_tags($value);// а вдруг ?
                        if( strlen($name) && strlen($value)  )
                            $out[$head][][$name]=$value;
                    }
                }
                return $out;
            } else {
                return FALSE;
            }
        }
        return FALSE;
    }


    private function bhphotovideo_com($str) {
        $xpath = @new DOMXPath($doc=DOMDocument::loadHTML($str));
        $out = array();
        $head = $title = $name = $value = '';
        foreach ( $xpath->query("//table[contains(@class,'specTable')]/tbody/tr") as $node ) {
            $title = trim(@$xpath->query("th[contains(@class,'Header')]",$node)->item(0)->textContent);
            $head = strlen($title)?$title:$head;
            $name  = trim(@$xpath->query("td[contains(@class,'specTopic')]",$node)->item(0)->textContent);
            //$value = trim(@$xpath->query("td[contains(@class,'specDetail')][1]",$node)->item(0)->textContent);
            $node3 =@$xpath->query("td[contains(@class,'specDetail')][1]",$node)->item(0);
            $value = @$doc->saveXML($node3);
            $value = $this->clean_br($value);
            $name  = strip_tags($name); // а вдруг ?
            if( strlen($name) && strlen($value)  )
                $out[$head][][$name]=$value;
        }
        return $out;
    }


    private function parse_cdw_com($str) {
        $xpath = @new DOMXPath(DOMDocument::loadHTML($str));
        $out = array();
        $head = $title = $name = $value = '';
        foreach ( $xpath->query("//div[@id='innerTSpec']/table/tr") as $node ) {
            $title = trim(@$xpath->query("td[contains(@class,'techspecheading')]",$node)->item(0)->textContent);
            $head = strlen($title)?$title:$head;

            $name  = trim(@$xpath->query("td[contains(@class,'techspecdata')]",$node)->item(0)->textContent);
            $value = trim(@$xpath->query("td[contains(@class,'techspecdata')]",$node)->item(1)->textContent);
            $name  = strip_tags(trim($name,':')); // а вдруг ?
            $value = strip_tags(trim($value,':'));// а вдруг ?
            if( strlen($name) && strlen($value)  )
                $out[$head][][$name]=$value;
        }
        return $out;
    }


    private function parse_newegg_com($str) {
        $xpath = @new DOMXPath($doc=DOMDocument::loadHTML($str));
        $out = array();
        $title = $name = $value = '';
        foreach ( $xpath->query("//div[@id='detailSpecContent']/div/fieldset") as $node ) {
            $title = trim(@$xpath->query("h3[contains(@class,'specTitle')]",$node)->item(0)->textContent);
            if ( !strlen($title) )
                continue;
            foreach ($xpath->query("dl",$node) as $node2) {
                $name  = trim(@$xpath->query("dt",$node2)->item(0)->textContent);
                //$value = trim(@$xpath->query("dd",$node2)->item(0)->textContent); // nodeValue
                $node3 =@$xpath->query("dd",$node2)->item(0);
                $value = @$doc->saveXML($node3);
                $value = $this->clean_br($value);
                $name  = strip_tags($name); // а вдруг ?
                 if( strlen($name) && strlen($value) )
                    $out[$title][][$name] = $value;
            }
        }
        return $out;
    }


    private function get_cached_url ($url) {
        if( !$this->use_cache )
            return $this->get_url( $url );

        $cfile = md5($url);
        if( !is_file($cfile) ) {
            $data = $this->get_url( $url );
            $fp = fopen($cfile, 'w');
            fwrite($fp, $data );
            fclose($fp);
        } else {
            $data = file_get_contents ($cfile);
        }
        return $data;
    }


    private function clean_br($str) {
        $value = strip_tags($str, '<br><br/>');
        $value = str_replace(Array('<br><br>','<br/><br/>','<br/>'),Array('<br>','<br>','<br>'),$value);
        return trim(trim($value,'<br>'));
    }


    private function get_url( $url ) {
        $retry = $this->retry;
        while( $retry-- ) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_FAILONERROR,1);
            if(!ini_get("open_basedir")) {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
            }
            curl_setopt($ch, CURLOPT_MAXREDIRS,3);
            curl_setopt($ch, CURLOPT_VERBOSE,0);
            curl_setopt($ch, CURLOPT_HEADER,0);
            curl_setopt($ch, CURLOPT_TIMEOUT,600);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
            curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:42.0) Gecko/20100101 Firefox/42.0');
            curl_setopt($ch, CURLOPT_HTTPHEADER,Array(  "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                                                        "Accept-Language: en,en-us;q=0.8,ru-ru;q=0.5,ru;q=0.3",
                                                        /*"Accept-Encoding: gzip, deflate"*/));
            $res = curl_exec($ch);
            if(!$res) {
                $this->lastHttpError = curl_error($ch);
            }
            curl_close($ch);
            if( $res === FALSE ) {
                usleep(250000);
                continue;
            }
            return $res;
        }
        return FALSE;
    }


    public function console_run($url,$site) {
        global $_POST;
        $_POST['url'] = $url;
        $_POST['site'] = $site;
        $this->run();
    }


    function __construct() {
        if( isset($_POST['url']) && isset($_POST['site']) )
            $this->run();
    }


    function __destruct() {;}
}



if (  count(get_included_files())<=1 ) { // console debug
    if( isset($_SERVER["HTTP_HOST"]) )
        die('403');
    @ini_set('display_errors', true);
    error_reporting(E_ALL & ~E_NOTICE);
    set_time_limit(60);
    $url = "https://www.cdw.com/shop/products/Tripp-Lite-Mini-Displayport-to-VGA-DVI-HDMI-Adapter-MDP-Converter-6in/3600241.aspx";
    $url = "http://www.newegg.com/Product/Product.aspx?Item=N82E16820148855";
    $url = "http://biz.tigerdirect.com/product/itemKey/102821987";
    $url = "http://www.bhphotovideo.com/c/product/1122027-REG/samsung_un40j5200afxza_j5200_40_class_full_hd.html";
    $url = "http://www.tigerdirect.com/applications/SearchTools/item-details.asp?EdpNo=9864281&sku=HPR-103069217&cm_re=Homepage-_-Zone3_4-_-CatId_17_HPR-103069217";
    $cls = new parser();
    $cls->console_run($url,'tigerdirect.com');
    echo strlen($cls->get_result());

    echo "\n";
}

?>