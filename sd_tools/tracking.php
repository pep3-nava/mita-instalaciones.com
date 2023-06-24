<?php

// do not display any output from stats api
ob_start();

include_once('IHP.php');

$site_url = str_replace(array('http://', 'https://'), '', SITE_URL);
$referer = parse_url($_SERVER['HTTP_REFERER']);
$host = $referer['host'];

// to make sure to correct the data when this file has been called on publish website only
if( !empty($host) && strpos($site_url, $host) !== false ) {
    if(ENV === 'dev')
        $domain = 'simdif-tracking.sd.test';
    else if(ENV === 'labs')
        $domain = 'sdlabtrackingha.simdif.local';
    else
        $domain = 'sdprdtrackingha.simdif.local';

    // check accessable domain
    $protocol = 'https://';
    $f = @get_headers($protocol.$domain);
    if( !$f ) {
        $protocol = 'http://';
        $f = @get_headers($protocol.$domain);
    }

    if( stripos($f[0], "200 OK") !== false ) {
        
        // set client ip for stats
        $ip = $_SERVER['REMOTE_ADDR'];
        if( strpos($ip, ':') === false ) {
            $ipnum = ip2long($ip);
            
            // ignore private ip address (internal network)
            if(   (($ipnum >= 3232235520 && $ipnum <= 3232301055) // 192.168.0.0 - 192.168.255.255 (65,536 IP addresses)
                || ($ipnum >= 2886729728 && $ipnum <= 2887778303) // 172.16.0.0 - 172.31.255.255 (1,048,576 IP addresses)
                || ($ipnum >= 167772160 && $ipnum <= 184549375))   // 10.0.0.0 - 10.255.255.255 (16,777,216 IP addresses)
                && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])
              ) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }
        
        $params = array(
            'path'  => '/tracking/pushsimplestats',
            'data'  => 'user_id='.USER_ID.'&site_id='.SITE_ID.'&ip='.$ip,
            'port'  => 80,
            'domain'=> $domain,
        );
        $response = IHP::sendSOCK($params);
        $dat = json_decode($response);
    }
}

// do not display any output from stats api
ob_end_clean();

?>
