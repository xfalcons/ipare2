<?php

set_include_path(get_include_path() . PATH_SEPARATOR . '/usr/local/google-api-php-client');
require_once '/usr/local/google-api-php-client/src/Google/autoload.php';

$client = new Google_Client();
$client->setApplicationName("clickmei-141506");
$client->setDeveloperKey("AIzaSyD-_ljrQ0_vkYwiQCsz0BvFy_QVi5PP7f8");

/* 輸入申請的Line Developers 資料  */
$channel_id = "1477752823";
$channel_secret = "40b5ad466ac56d7fcbad9cf59ba4f8fc";
$mid = "ufd095561de8d0b9b85fae83c52f2799c";

$service = new Google_Service_Books($client);
$optParams = array('filter' => 'free-ebooks');
$results = $service->volumes->listVolumes('Henry David Thoreau', $optParams);

foreach ($results as $item) {
  echo $item['volumeInfo']['title'], "<br /> \n";
}

//$audioData = file_get_contents('/tmp/audio.raw');
$audioData = file_get_contents('/tmp/line/voice_166.aac.flac');
$audioData = base64_encode($audioData);

$cloudSpeech = new Google_Service_CloudSpeechAPI($client);

$recognizeConfig = new Google_Service_CloudSpeechAPI_RecognitionConfig();
//$recognizeConfig->setEncoding('LINEAR16');
$recognizeConfig->setEncoding('FLAC');
$recognizeConfig->setSampleRate(8000);
$recognizeConfig->setLanguageCode('cmn-Hant-TW');

$recognizeAudio = new Google_Service_CloudSpeechAPI_RecognitionAudio();
$recognizeAudio->setContent($audioData);

$recognizeRequest = new Google_Service_CloudSpeechAPI_SyncRecognizeRequest();
$recognizeRequest->setAudio($recognizeAudio);
$recognizeRequest->setConfig($recognizeConfig);

$response = $cloudSpeech->speech->syncrecognize($recognizeRequest);
$result = $response->getResults();

$alternative = $result[0]->getAlternatives();
if ( isset($alternative[0]) ) {
    echo $alternative[0]['transcript'];
    echo "\n\n";
}
var_export($alternative);
