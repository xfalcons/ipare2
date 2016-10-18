<?php
include_once("simple_html_dom.php");
include_once("helper.php");

function compare_price($a, $b) {
    if ($a["price"] == $b["price"]) {
        return 0;
    }

    return ($a["price"] > $b["price"]) ? 1 : -1;
}

//$req_q = '%E6%97%A5%E7%AB%8B+%E5%86%B0%E7%AE%B1+RS47ZMJ';
$req_q = urlencode("日立 冰箱");
$q = '';
$req_p = '';
$p = '';
$type = '';
$isJson = FALSE;
$minp = '';
$maxp = '';

$results = array();

if ( isset($_GET['q']) ) {
    if (isset($_GET['p'])) {
        $p = $_GET['p'];
    }
    $q = $_GET['q'];
    $type = $_GET['type'];
    $req_q = urlencode($q);
}

if ( $p !== '' && is_numeric($p) ) {
    $minp = $p * 0.7;
    $maxp = $p * 1.3;
}

if ( $type == "json" ) {
    $isJson = TRUE;
}

$findprice_url = "https://api.feebee.com.tw/v1/search.php?q=$req_q";
if ($minp != '') {
    $findprice_url .= "&minp=$minp&maxp=$maxp";
}

$ch2 = curl_init($findprice_url);

curl_setopt($ch2, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch2, CURLOPT_HEADER, false);
curl_setopt($ch2, CURLOPT_USERAGENT, "Google Bot");
curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);

$ret = curl_exec($ch2);
curl_close($ch2);

//$data = async_get_url($urls);

// $html1 = str_get_html($data[0]);

// $results["q"] = urldecode($req_q);
// $results["results"] = array();

/*
// Processing buy.yahoo.com
$div = $html2->getElementById("search_result_list");
if ( isset($div) ) {
    $ul_array = $div->find('ul[data-gdid]');
    if (is_array($ul_array) && count($ul_array)>0) {
        foreach ($ul_array as $ul) {
            $title = '';
            $price = '';
            $feature = '';
            $description = '';
            $image_url = '';
            $item_url = '';

            $image_url = $ul->find('.pd-image img', 0)->src;
            $title = $ul->find('.title', 0)->plaintext;
            $item_url = $ul->find('.title a', 0)->href;
            $price = $ul->find('.price span', 0)->plaintext;
            $price = str_replace(",", "", $price);
            $li_array = $ul->find('.content li');

            foreach ($li_array as $li) {
                $description .= $li->plaintext . ", ";
            }

            $results["results"][] = array(
                    "title" => "$title",
                    "item_url" => "$item_url",
                    "price" => "$price",
                    "feature" => "$feature",
                    "description" => "$description",
                    "image_url" => "$image_url",
                    "merchant" => "Yahoo!奇摩購物中心"
                    );
        }
    }
}


// Processing findprice
$div = $html1->getElementById("GoodsGridDiv");
$tr_ary = $div->find('tr');

foreach ( $tr_ary as $tr ) {
  $td_ary = $tr->find('td');

    $title = '';
    $price = '';
    $feature = '';
    $description = '';
    $image_url = '';
    $item_url = '';
    $merchant = '';

    $image_url = $td_ary[0]->find('img', 0)->src;
    $price = $td_ary[1]->find('b', 0)->plaintext;
    $item_url = $td_ary[2]->find('a', 0)->href;
    $title = $td_ary[2]->find('a', 0)->plaintext;
    $font_ary = $td_ary[2]->find('font');
    //$merchant = $td_ary[2]->find('font', 0)->outertext;
    foreach ($font_ary as $f) {
    if ( isset($f->style) ) {
      $merchant = str_replace('&nbsp;', '', $f->plaintext);
      $merchant = trim($merchant, " -");
    }
  }


  $results["results"][] = array(
    "title" => "$title",
    "item_url" => "$item_url",
    "price" => "$price",
    "feature" => "$feature",
    "description" => "$description",
    "image_url" => "$image_url",
    "merchant" => "$merchant"
);

}

*/

//usort($results['results'], "compare_price");
//$json_string = json_encode($results, JSON_UNESCAPED_UNICODE);
//$json_string = json_encode($results);
header("Content-type: application/json");

echo $ret;
?>

