<?php
require 'scraperwiki.php';
define("COOKIE_FILE", "cookie.txt");
 
$User_Agent = 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.43 Safari/537.31';

$request_headers = array();
$request_headers[] = 'User-Agent: '. $User_Agent;


$url = "http://albopretorio.comune.genova.it/ialbo/consultazioneEnter.action";
$curl = curl_init($url);
curl_setopt ($curl, CURLOPT_COOKIEJAR, COOKIE_FILE);
curl_setopt ($curl, CURLOPT_COOKIEFILE, COOKIE_FILE);
curl_setopt($curl, CURLOPT_HEADER, true);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);

$output = curl_exec($curl);
curl_close($curl);

$dom = new DOMDocument;
libxml_use_internal_errors(TRUE);
$dom->loadHTML($output);

libxml_clear_errors();

$xPath = new DOMXPath($dom);
$links = $xPath->query('//table[@class="aurigalist"]//tbody//tr');
foreach ($links as $link){
    $id_riga = $link->getAttribute("id");
    $data = null;
    $numero = null;
    $proposto = null;
    $titolo = null;
    $pubblicazione = null;
    $url = null;

    $count = 0;
    $row = [];
    foreach ($link->childNodes as $child) {
    $row[] = $child;
    }
    if ($row[0]->attributes->getNamedItem('title') !== null) {
        $data = trim($row[0]->attributes->getNamedItem('title')->value);
    }

    foreach ($row[2]->childNodes as $child) {
        if(isset($child->tagName) && $child->tagName === 'label') {
            $numero = trim($child->attributes->getNamedItem('title')->value);
        }
    }

    $proposto = trim($row[4]->textContent);
 
    foreach ($row[6]->childNodes as $child) {
        if(isset($child->tagName) && $child->tagName === 'label') {
            $titolo = trim($child->attributes->getNamedItem('title')->value);
        }
    }

    foreach ($row[8]->childNodes as $child) {
        if(isset($child->tagName) && $child->tagName === 'img') {
            $pubblicazione = trim($child->attributes->getNamedItem('title')->value);
        }
    } 
    
    foreach ($row[10]->childNodes as $child) {
        if(isset($child->tagName) && $child->tagName === 'a'){
            $url = "http://albopretorio.comune.genova.it/ialbo/".trim($child->attributes->getNamedItem('href')->value);
        }
    }
    
 $record = array(
   'id_riga' => $id_riga,
   'data' => $data,
   'numero' => $numero,
   'proposto' => $proposto,
   'titolo' => $titolo,
   'pubblicazione' => $pubblicazione,
   'url' => $url
 );

scraperwiki::save_sqlite(array('id_riga'), $record); 
}
