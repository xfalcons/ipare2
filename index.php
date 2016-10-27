<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '/usr/local/google-api-php-client');
require_once '/usr/local/google-api-php-client/src/Google/autoload.php';
require_once('/home/m/share/htdocs/api/ipare/LINEBotTiny.php');

$channelAccessToken = '4UBQVj3OSwDaWRsanONIURNhUni9/MJKokJ36hrM5v4EE6VVPvWU13txx+5mWSPIX0rNWP9vBrZ8HRZR+zcaRkJPOKKz/JZ+gUS0IF+b8/sTz4CHtg8lcb+9bImyK1yq2HTuzDMfWOWn3uGiauvP1QdB04t89/1O/w1cDnyilFU=';
$channelSecret = 'f1cd084fb5a28bd2716c63ca7225dbf9';

$replyMessage = "I don't know what to do.";
$client = new LINEBotTiny($channelAccessToken, $channelSecret);
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
                                    /*
                                    array(
                                        "type" => "uri",
                                        "linkUri" => "http://bit.ly/2ek0eRZ",
                                        "area" => array(
                                            "x" => 0,
                                            "y" => 0,
                                            "width" => 520,
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
                                    */
                                    array(
                                        "type" => "message",
                                        "text" => "Hello message from imagemap",
                                        "area" => array(
                                            "x" => 521,
                                            "y" => 0,
                                            "width" => 510,
                                            "height" => 1040,
                                        )
                                    )
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
exit;



/*
 精選好康優惠
 https://api.feebee.com.tw/v1/today.php?
 */
function queryApi($query) {
    $q = urlencode($query);
    $findprice_url = "https://api.feebee.com.tw/v1/search.php?pl=1000&ph=&q=$q";
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

