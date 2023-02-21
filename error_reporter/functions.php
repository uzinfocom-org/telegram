<?php

function request($method, $content=[]) {
	$content['parse_mode'] = 'html';
	
	$url = 'https://api.telegram.org/bot'.$GLOBALS['config']['token'].'/' . $method;
	
	$ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
    	"Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($content));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, TRUE);
}

function sendMessage($chat_id, $text){
	$disable_web_page_preview = TRUE;
	return request('sendMessage', compact( 'text', 'chat_id', 'disable_web_page_preview' ));
}

function sendError($text){
	foreach ($GLOBALS['config']['owners'] as $owner) {
		sendMessage($owner, $text);
	}
}

function pingDomain( $domain ){
    $starttime = microtime(true);
    $file      = @fsockopen ($domain, 80, $errno, $errstr, 10);
    $stoptime  = microtime(true);
    $status    = 0;

    if (!$file) $status = -1;
    else {
        fclose($file);
        $status = ($stoptime - $starttime) * 1000;
        $status = floor($status);
    }
    return $status;
}

function parse_errors(){
	$cmd = "tail -n 5 " . $GLOBALS['config']['nginx_log_file'];

	$errors = shell_exec($cmd);
	preg_match_all('/(\d+\/\d+\/\d+\s\d+:\d+:\d+)\s(.*)\n?/u', $errors, $matches, PREG_SET_ORDER);
	if(!empty($matches)){
		$matches = array_reverse($matches);
		if ( $GLOBALS['last_error_check'] != $matches[0][1]) {
			$GLOBALS['last_error_check'] = $matches[0][1];
			
			if( !preg_match('/No such file or directory|directory index of (.*?) is forbidden|SSL_do_handshake|not allocate new session in SSL|SSL_write\(\) failed/', $matches[0][0]) ){
				return $matches[0][0];
			}
			
			//return $matches[0][0];
		}
		
	}

	return FALSE;
}

function spaceLimit( $limitPercent ) {
    $drive = "/";
    $space = (disk_free_space($drive) / disk_total_space($drive)) * 100;
    return $limitPercent < $space;
}

function run(){
	if ( spaceLimit( 90 ) ) {
		$message = "<b>Muammo:</b> serverda 10%dan kam bo'sh xotira qoldi\n<b>Vaqt:</b> ".date("Y-m-d_H:i:s");
		sendError( $message );
	}

	$parse_errors = parse_errors();
	if ( $parse_errors ) {
		$message = "<b>Muammo:</b> <em>{$parse_errors}</em>\n\n<b>Vaqt:</b> " . date("Y-m-d_H:i:s");
		sendError( $message );
	}

	if ( !empty( $GLOBALS['config']['servers'] ) ) {
		foreach ($GLOBALS['config']['servers'] as $server) {
			if ( pingDomain( $server ) == -1 && ( (time() - $GLOBALS['last_ping_send_time']) > 60 || $GLOBALS['last_ping_send'] != $server ) ) {
				$message = "<b>Muammo:</b> {$server} serveriga ping yuborishda xatolik!\n<b>Vaqt:</b> ".date("Y-m-d_H:i:s");
				sendError( $message );
				$GLOBALS['last_ping_send'] = $server;
				$GLOBALS['last_ping_send_time'] = time();
			}
		}
	}
	
}
