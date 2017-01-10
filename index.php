<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '/usr/local/google-api-php-client');
require_once '/usr/local/google-api-php-client/src/Google/autoload.php';
require_once('/home/m/share/htdocs/api/ipare/LINEBotTiny.php');

$channelAccessToken = 'zozhngkOrXeamjIe5SvZ/FL8Jw1ADdKQEI6sg+eOjxXDb8q5rrYG/O4rcMCjddjn/Nd+i4UrAO8DtmTt+UDWARgRdgKPaaRQ4Ql0OjGSOyTtw44k07Xpa1hN80dzPDd88s6tP/RJMU+Iy0Pla9bqOgdB04t89/1O/w1cDnyilFU=';
$channelSecret = 'e548a4f984cbe23d3d2250295eff3d4b';

$replyMessage = "I don't know what to do.";
$client = new LINEBotTiny($channelAccessToken, $channelSecret);
foreach ($client->parseEvents() as $event) {
    // debugging info
    file_put_contents('/tmp/linebot.txt', var_export($event, 1)."\n\n", FILE_APPEND);

    $error_message = array(
        '500' => '寶寶出錯了，只是寶寶不說',
        '404' => '找不到你要的寶貝',
    );
    switch ($event['type']) {
        case 'message':
            $message = $event['message'];
            switch ($message['type']) {
                case 'text':
                    $item = trim($message['text']);
                    if ( mb_substr($item, 0, 1, 'UTF-8') == '?' || mb_substr($item, 0, 1, 'UTF-8') == '$' ) {
                        $item = mb_substr($item, 1, NULL, 'UTF-8');
                        $replyMessage = queryApi($item, 'carousel');

                        file_put_contents('/tmp/linebot.txt', var_export($replyMessage, 1)."\n\n", FILE_APPEND);

                        if ( $replyMessage == '500' || $replyMessage == '404' ) {
                            $client->replyMessage(array(
                                'replyToken' => $event['replyToken'],
                                'messages' => array(
                                    array(
                                        'type' => 'text',
                                        'text' => $error_message[$replyMessage],
                                    ),
                                )
                            ));
                        } else {
                            $client->replyMessage(array(
                                'replyToken' => $event['replyToken'],
                                'messages' => array(
                                    array(
                                        'type' => 'template',
                                        'altText' => 'no support on this version, please upgrade',
                                        'template' => array(
                                            'type' => 'carousel',
                                            'columns' => $replyMessage
                                        ),
                                    ),
                                )
                            ));
                        }

                    }
                    break;
                default:
                    error_log("Unsupporeted message type: " . $message['type']);
                    break;
            }
            break;
        default:
            error_log("Unsupporeted event type: " . $event['type']);
            break;
    }
};
exit;



/*
 精選好康優惠
 https://api.feebee.com.tw/v1/today.php?
 */
function queryApi($query, $type = 'text') {    
    $q = urlencode($query);
    $findprice_url = "https://api.feebee.com.tw/v1/search.php?pl=500&n=5&q=$q";
    $ch2 = curl_init($findprice_url);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch2, CURLOPT_HEADER, FALSE);
    curl_setopt($ch2, CURLOPT_USERAGENT, "Google Bot");
    curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, TRUE);
    $ret = curl_exec($ch2);
    curl_close($ch2);

    if ( $ret === false ) {
        return 500;
    }

    $result = json_decode($ret, TRUE);
    if ( $result['total'] == 0 ) {
        return 404;
    }

    switch($type) {
        case 'carousel':
            return assemble_carousel($result);
            break;

        case 'text':
        default:
            return assemble_text($result);
            break;
    }
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


function assemble_text($result) {
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

function assemble_carousel($result) {
    $columns = array();
    if (isset($result['items']) && is_array($result['items'])) {
        $n = 0;
        foreach ($result['items'] as $item) {
            //$message .= sprintf("・($%s) %s[%s]\n\n", $item['price'], $item['title'], $item['link']);
            $imageUrl = trim($item['image']);
            if ( preg_match('/^http:\/\/(.*)/i', $imageUrl, $matches) == 1 ) {
                $imageUrl = 'https://images.weserv.nl/?url='. $matches[1];
            }

            error_log("check encoding: " . mb_strlen($item['title']));
            error_log("check encoding utf-8: " . mb_strlen($item['title'], 'UTF-8'));
            error_log("substr: " . mb_substr($item['title'], 0, 60, 'UTF-8'));

            $columns[] = array(
                'title' => '$' . strval($item['price']),
                'text' => mb_substr($item['title'], 0, 60, 'UTF-8'),
                'thumbnailImageUrl' => $imageUrl,
                'actions' => array(
                    array(
                        'type' => 'uri',
                        'label' => $item['store'],
                        'uri' => $item['link'],
                    ),
                ),
            );

            if ($n++ > 5) {
                break;
            }
        }
    }

    return $columns;

}


/*

foreach ($client->parseEvents() as $event) {
    // debugging info
    file_put_contents('/tmp/linebot.txt', var_export($event, 1)."\n\n", FILE_APPEND);

    switch ($event['type']) {
        case 'message':
            $message = $event['message'];
            switch ($message['type']) {
                case 'text':
                    $replyMessage = queryApi($message['text']);
                    $client->replyMessage(array(
                        'replyToken' => $event['replyToken'],
                        'messages' => array(
                            array(
                                'type' => 'text',
                                'text' => $replyMessage
                            ),
                            array(
                                'type' => 'image',
                                'originalContentUrl' => 'https://dummyimage.com/1024x1024/000/fff.jpg&text=Hello+World',
                                'previewImageUrl' => 'https://dummyimage.com/240x240/000/fff.jpg&text=Hello+World'
                            ),
                            array(
                                'type' => 'video',
                                'originalContentUrl' => 'https://video.twimg.com/ext_tw_video/560049056895209473/pu/vid/720x720/S7F4BF2wKR2txCpA.mp4',
                                'previewImageUrl' => 'https://dummyimage.com/240x240/a8b3b1/2f37a3.jpg&text=VideoPreview'
                            ),
                            array(
                                'type' => 'location',
                                'title' => 'Pocha 韓式熱炒',
                                'address' => '106台北市大安區忠孝東路四段216巷19弄9號',
                                'latitude' => 25.0404594,
                                'longitude' => 121.5513114
                            ),
                            array(
                                'type' => 'imagemap',
                                'baseUrl' => 'https://dummyimage.com',
                                'altText' => 'This is a imagemap',
                                'baseSize' => array(
                                    'width' => 1040,
                                    'height' => 1040
                                ),
                                'actions' => array(
                                    array(
                                        "type" => "message",
                                        "text" => "Hello message from imagemap",
                                        "area" => array(
                                            "x" => 521,
                                            "y" => 0,
                                            "width" => 510,
                                            "height" => 1040,
                                        )
                                    ),
                                    array(
                                        "type" => "uri",
                                        "linkUri" => "https://tw.news.yahoo.com/%E7%9C%8B%E5%BE%97%E5%88%B04%E5%80%8B%E6%95%B8%E5%AD%97-%E4%BB%A3%E8%A1%A8%E4%BD%A0%E7%9C%BC%E5%8A%9B%E9%9D%9E%E6%AF%94%E5%B0%8B%E5%B8%B8-032029628.html",
                                        "area" => array(
                                            "x" => 525,
                                            "y" => 0,
                                            "width" => 500,
                                            "height" => 1040,
                                        )
                                    ),
                                )
                            ),
                        )
                    ));


                    $client->pushMessage(array(
                        'to' => $event['source']['userId'],
                        'messages' => array(
                            array(
                                'type' => 'text',
                                'text' => 'You are query : ' . $message['text']
                            )
                        )
                    ));
                    break;
                default:
                    error_log("Unsupporeted message type: " . $message['type']);
                    break;
            }
            break;
        default:
            error_log("Unsupporeted event type: " . $event['type']);
            break;
    }
};


*/

