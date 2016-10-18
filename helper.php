<?php

function async_get_url($url_array, $wait_usec = 0) {     
	if (!is_array($url_array))
             return false;

	$wait_usec = intval($wait_usec);
	$data    = array();
	$handle  = array();
	$running = 0;
	$mh = curl_multi_init();

	// multi curl handler     
	$i = 0;
	foreach($url_array as $url) {         
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return don't print         
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 302 redirect         
		curl_setopt($ch, CURLOPT_MAXREDIRS, 7);
		curl_multi_add_handle($mh, $ch);
		// 把 curl resource 放進 multi curl handler 裡         
		$handle[$i++] = $ch;
	}     


        echo "We are here<br>";
	/* 執行 */     
	/* 此種做法會造成 CPU loading 過重 (CPU 100%)     
	   do {         
	   curl_multi_exec($mh, $running);


	   if ($wait_usec > 0) // 每個 connect 要間隔多久             
	   usleep($wait_usec);

	// 250000 = 0.25 sec     
	} while ($running > 0);

	 */    

	/* 此做法就可以避免掉 CPU loading 100% 的問題 */     
   	// 參考自: http://www.hengss.com/xueyuan/sort0362/php/info-36963.html     
	do {         
		$mrc = curl_multi_exec($mh, $active);
	} while ($mrc == CURLM_CALL_MULTI_PERFORM);

	while ($active and $mrc == CURLM_OK) {         
		if (curl_multi_select($mh) != -1) {             
			do {                 
				$mrc = curl_multi_exec($mh, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		}     
	}    

	/* 讀取資料 */     
	foreach($handle as $i => $ch) {         
		$content  = curl_multi_getcontent($ch);
		$data[$i] = (curl_errno($ch) == 0) ? $content : false;
	}     

	/* 移除 handle*/     
	foreach($handle as $ch) 
	{         
		curl_multi_remove_handle($mh, $ch);
	}     

	curl_multi_close($mh);
	return $data;
}

?>
