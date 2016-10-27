<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '/usr/local/google-api-php-client');
require_once '/usr/local/google-api-php-client/src/Google/autoload.php';

/* 輸入申請的Line Developers 資料  */
$channel_id = "1484178374";
$channel_secret = "f1cd084fb5a28bd2716c63ca7225dbf9";
$channel_access_token = "4UBQVj3OSwDaWRsanONIURNhUni9/MJKokJ36hrM5v4EE6VVPvWU13txx+5mWSPIX0rNWP9vBrZ8HRZR+zcaRkJPOKKz/JZ+gUS0IF+b8/sTz4CHtg8lcb+9bImyK1yq2HTuzDMfWOWn3uGiauvP1QdB04t89/1O/w1cDnyilFU=";
$mid = "ufd095561de8d0b9b85fae83c52f2799c";

/* 將收到的資料整理至變數 */
$receive = json_decode(file_get_contents("php://input"));
if (json_last_error() !== JSON_ERROR_NONE) { // Not a valid request
    echo 'Oops...';
    exit;
} 

// var_dump($receive);
// exit;

// debugging info
file_put_contents('/tmp/linebot.txt', json_encode($receive));

$replyToken = $receive->events[0]->replyToken;
$text = $receive->events[0]->message->text;
$type = $receive->events[0]->type;
$userId = $receive->events[0]->source->userId;

//$from = $receive->result[0]->content->from;
//$content_type = $receive->result[0]->content->contentType;
//$event_type = $receive->result[0]->eventType;

/*
Different operation types:

The data defined in the content property depends on the type of request.
- Receiving messages (text, stickers, contacts) - "eventType":"138311609000106303",
- Receiving operations (adding an official account as a friend) - "eventType":"138311609100106403"
*/

/* 準備Post回Line伺服器的資料 */
$header = [ "Content-Type: application/json",
            "Authorization: Bearer ${channel_access_token}"
            ];

$replyMessage = "I don't know what to do.";

if ( isValidSignature($channel_secret) && mb_strlen($text) > 1 ) {
    $replyMessage = queryApi($text);
} else {
    $replyMessage = getBoubouMessage('犯傻了');
}

sendMessage($header, $replyToken, $replyMessage);

exit;




// Handling text message only
/*
$message = '';
switch ($event_type) {
    case "138311609000106303":
        if ( $content_type == 1 ) { 
            if ( isValidSignature($channel_secret) && mb_strlen($text) > 1 ) {
                $message = queryApi($text);
            } else {
                $message = getBoubouMessage('犯傻了');
            }
        } elseif ( $content_type == 4 ) {
            $message = '語音辨識中...';
            sendMessage($header, $from, $message);
            $message_id = $receive->result[0]->content->id;
            $text = getMessageContent($header, $message_id);
            if ( $text != '' ) {
                $message = "你要找的是 ($text)\n";
                $message .= queryApi($text);
            }
        }
    case "138311609100106403":
        $op_type = $receive->result[0]->content->opType;
        if ( $op_type == 4 ) { // Added as friend
            $message = '比價我最行，只要輸入你要比價的商品，我立即回覆！現在更支持「語音輸入」喔！';
        }
}
sendMessage($header, $from, $message);
*/

/* Retrive message content for image, video, audio */
function getMessageContent($header, $message_id) {
    $url = sprintf("https://trialbot-api.line.me/v1/bot/message/%s/content", $message_id);

    $context = stream_context_create(array(
        "http" => array(
            "method" => "GET",
            "header" => implode(PHP_EOL, $header),
            "ignore_errors" => true)
    ));

    $data = file_get_contents($url, false, $context);
    $response_headers = parseHeaders($http_response_header);
    $content_dist = $response_headers['Content-Disposition'];
    $filename = 'line_audio.aac';
    if ( preg_match('/.*?filename="(.*?)"/', $content_dist, $matches) == 1 ) {
        $filename = $matches[1];
    }
    file_put_contents('/tmp/linedata-header.txt', var_export($response_headers, true));
    file_put_contents("/tmp/line/$filename", $data);

    exec("/usr/bin/ffmpeg -i /tmp/line/$filename -f flac /tmp/line/{$filename}.flac");

    $audioData = file_get_contents("/tmp/line/{$filename}.flac");
    $audioData = base64_encode($audioData);

    $client = new Google_Client();
    $client->setApplicationName("clickmei-141506");
    $client->setDeveloperKey("AIzaSyD-_ljrQ0_vkYwiQCsz0BvFy_QVi5PP7f8");
    $cloudSpeech = new Google_Service_CloudSpeechAPI($client);

    $recognizeConfig = new Google_Service_CloudSpeechAPI_RecognitionConfig();
    $recognizeConfig->setEncoding('FLAC');
    $recognizeConfig->setSampleRate(8000);
    $recognizeConfig->setLanguageCode('cmn-Hant-TW');

    $recognizeAudio = new Google_Service_CloudSpeechAPI_RecognitionAudio();
    $recognizeAudio->setContent($audioData);

    $recognizeRequest = new Google_Service_CloudSpeechAPI_SyncRecognizeRequest();
    $recognizeRequest->setAudio($recognizeAudio);
    $recognizeRequest->setConfig($recognizeConfig);

    $response = $cloudSpeech->speech->syncrecognize($recognizeRequest);
    $results = $response->getResults();

    $message = '';
    if ( isset($results[0]) ) {
        $alternatives = $results[0]->getAlternatives();
        if ( isset($alternatives[0]) ) {
            $message = $alternatives[0]['transcript'];
        }
    }
    
    return $message;
}


/* 發送訊息 */
function sendMessage($header, $token, $message) {

    $url = "https://api.line.me/v2/bot/message/reply";
    $data = array(
        "replyToken" => $token,
        "messages" => array(
            array(
                "type" => "text",
                "text" => $message 
            )
        )
    );

    $context = stream_context_create(array(
        "http" => array(
            "method" => "POST",
            "header" => implode(PHP_EOL, $header),
            "content" => json_encode($data),
            "ignore_errors" => true)
    ));

    echo "I am ready to callback.<br>\n";
    var_dump(json_encode($data));

    $res = file_get_contents($url, false, $context);
    echo "<br><br>\n\n $res.";
}

function getBoubouMessage($value){
    return "寶寶" . $value ."，只是寶寶不說";
}


/*

 精選好康優惠
 https://api.feebee.com.tw/v1/today.php?

 */

function queryApi($query) {
    $q = urlencode($query);
    $findprice_url = "https://api.feebee.com.tw/v1/search.php?pl=200&ph=&q=$q";
    $ch2 = curl_init($findprice_url);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch2, CURLOPT_HEADER, false);
    curl_setopt($ch2, CURLOPT_USERAGENT, "Google Bot");
    curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
    $ret = curl_exec($ch2);
    curl_close($ch2);

    if ( $ret === false ) {
        return '寶寶出錯了，只是寶寶不說';
    }

    /*
    {
        q: "em5",
        sq: "em5",
        total: 282,
        ql: 150,
        qh: 70990,
        date: "20160825",
        share: "http://feebee.com.tw/s/?q=em5&page=1&n=3&s=p&mode=l",
        products: [ ],
        items: [],
        campaign: [],
        provide: []
    }
    */
    $result = json_decode($ret, TRUE);
    if ( $result['total'] == 0 ) {
        return '找不到你要的寶貝';
    }

    $message = '';
    if (isset($result['products']) && is_array($result['products'])) {
        $n = 0;
        foreach ($result['products'] as $product) {
            //$message .= sprintf("・($%s-$%s) %s[%s]\n\n", $product['ql'], $product['qh'], $product['title'], $product['url']);
            $message .= sprintf("・《$%s-$%s》%s\n\n", $product['ql'], $product['qh'], $product['title']);
            if ($n++ > 3) {
                break;
            }
        }
    }

    if (isset($result['items']) && is_array($result['items'])) {
        $n = 0;
        foreach ($result['items'] as $item) {
            //$message .= sprintf("・($%s) %s[%s]\n\n", $item['price'], $item['title'], $item['link']);
            $message .= sprintf("・《$%s》%s\n\n", $item['price'], $item['title'], $item['link']);
            if ($n++ > 3) {
                break;
            }
        }
    }

    if (isset($result['campaign']) && is_array($result['campaign'])) {
        $n = 0;
        foreach ($result['campaign'] as $campaign) {
            //$message .= sprintf("・($%s) %s[%s]\n\n", $campaign['price'], $campaign['title'], $campaign['link']);
            $message .= sprintf("・《$%s》%s\n\n", $campaign['price'], $campaign['title']);
            if ($n++ > 3) {
                break;
            }
        }
    }

    $message .= sprintf("《更多》%s", $result['share']);

    return $message;
}

/**
 * LINE BOT API のSignatureを検証する
 * 
 * @param string $myChannelSecret LINE developers(管理画面)の「Channels > Basic information」にある「Channel Secret」文字列
 * @return boolean 正しいsignatureの場合true
 */
function isValidSignature($myChannelSecret) {
    if (isset($_SERVER['HTTP_X_LINE_TEST'])) {
        return true;
    }
    if (isset($_SERVER['HTTP_X_LINE_SIGNATURE'])) {
        $signature = base64_encode(hash_hmac('sha256', file_get_contents('php://input'), $myChannelSecret, true));
        
        if ($_SERVER['HTTP_X_LINE_SIGNATURE'] === $signature) {
            return true;
        }
    }
    
    return false;
}

function parseHeaders($headers) {
    $head = array();
    foreach( $headers as $k=>$v )
    {
        $t = explode( ':', $v, 2 );
        if( isset( $t[1] ) )
            $head[ trim($t[0]) ] = trim( $t[1] );
        else
        {
            $head[] = $v;
            if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
                $head['reponse_code'] = intval($out[1]);
        }
    }
    return $head;
}
