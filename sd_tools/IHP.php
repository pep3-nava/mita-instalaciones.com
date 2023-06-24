<?php

class IHP
{
    /**
      * Get variable via POST and GET
      * 
      * @param string parameter name
      * @return mix data of the pareteter name
      */
    static function getVar($var='')
    {
        $var = trim($var);
        
        if( !empty($var) )
        {
            if( $_POST[$var] != '' )
                $ret = $_POST[$var];
            else
                $ret = $_GET[$var];
            
            $ret = html_entity_decode(preg_replace("/%u([0-9a-f]{3,4})/i", "&#x\\1;", urldecode($ret)), null, 'UTF-8');
            
            return self::inputCheck($ret);
        }
        
        return '';
    }
    
    /**
      * Check and validate input data
      * 
      * @param mix $data
      * @return mix depend of data
      */
    private static function inputCheck($data)
    {
        $data = urldecode($data);
        
        //validate cross site script
        $data = self::XSS_Detection($data);
        
        //validate SQL injection
        $data = self::SQL_InjectionDetection($data);
        
        
        return $data;
    }
    
    /**
      * SQL injection detection rule
      * 
      * @param string $data
      * @return string
      */
    private static function SQL_InjectionDetection($data='')
    {
        if( !empty($data) )
        {
            // Prepare the data with some sql injection tricky
            {
                $dat = strtolower($data);
                $dat = str_replace(' ', '', $dat);          //strip off whitespace
                $dat = str_replace('/*', '{/*}', $dat);     //keep comment tag
                $dat = str_replace('*/', '{*/}', $dat);     //keep comment tag
                
                $dat = preg_replace('/\(\(+/', '(', $dat);  //strip multiple tag '(' and keep only one '('
                $dat = preg_replace('/\/\/+/', '/', $dat);  //strip multiple tag '/' and keep only one '/'
                
                $dat = str_replace('{/*}', '/*', $dat);     //restore comment tag
                $dat = str_replace('{*/}', '*/', $dat);     //restore comment tag
                //echo $dat.'<br>'; exit;
                
                //not found comment tag return all data
                if( strpos($dat, '/*') !== false)
                {
                    
                    #######################################
                    # strip off comment tag - tricky bit
                    #######################################
                    $_dat             = '';
                    $strlen           = strlen($dat);
                    $found_open_tag   = false;
                    $found_open_tag2  = false;
                    
                    for($i = 0; $i < $strlen; $i++)
                    {
                        //find open tag /*!
                        if($dat[$i].$dat[($i+1)].$dat[($i+2)] == '/*!')
                        {
                            $found_open_tag2 = true;
                            $i+=3;
                        }
                    
                        //find close tag
                        if($found_open_tag2)
                        {
                            if($dat[$i].$dat[($i+1)] == '*/')
                            {
                                $found_open_tag2 = false;
                                $i++;
                            }
                            else
                                $_dat .= $dat[$i];
                        }
                        else
                        {
                        
                            //find open tag /*
                            if($dat[$i].$dat[($i+1)] == '/*')
                            {
                                $found_open_tag = true;
                                $x = $i;
                            }
                        
                            //find close tag
                            if($found_open_tag)
                            {
                                if($dat[$i].$dat[($i+1)] == '*/')
                                {
                                    $found_open_tag = false;
                                    $i++;
                                }
                            }
                            else
                                $_dat .= $dat[$i];
                        }
                    
                    //echo $i.' - '.$_dat.'<br>';
                    }
                    
                    //not found close tag return all the rest
                    if($found_open_tag)
                    {
                        for($i = $x; $i < $strlen; $i++)
                            $_dat .= $dat[$i];
                    }
                    #######################################
                    
                    $dat = $_dat;
                }
            }
            
            $sql_detection_rule = array(
                                      'concat(', 
                                      'concat_ws', 
                                      'unionselect', 
                                      'unionallselect', 
                                      'union(select', 
                                      'unionall(select', 
                                      'waitfordelay', 
                                      'killall', 
                                      'benchmark(', 
                                      'load_file(', 
                                      'dumpfile', 
                                      'outfile', 
                                      'sleep(', 
                                      'information_schema', 
                                  );
            
            $tmp = str_replace($sql_detection_rule, '', $dat);
            
            // if invalid data, clear it
            if( $dat != $tmp )
                $data = '';
            
        }
        
        return $data;
    }
    
    /**
      * Cross Site Script detection rule
      * 
      * @param string $data
      * @return string 
      */
    private static function XSS_Detection($data='')
    {
        if( !empty($data) )
        {
            $dat = strtolower($data);
            $dat = str_replace(' ', '', $dat); // remove all spaces
            
            //https://www.acunetix.com/websitesecurity/cross-site-scripting/
            $xss_detection_rule = array(
                                    '<script', 
                                    '%3cscript', 
                                    //'onload=',
                                    'javascript:',
                                    '<iframe',
                                    'expression(',
                                    'x-scriptlet', 
                                  );
            
            $tmp = str_replace($xss_detection_rule, '', $dat);
            
            // if invalid data, clear it
            if( $dat != $tmp )
                $data = '';
            
        }
        
        return $data;
    }

    static function reCaptchaVerify($recaptcha='')
    {
        if( !empty($recaptcha) ) {
            $secret_key = '6Lcx8ygTAAAAAJlVXemYPDOVR1sGJTd8-h5Z4VNy';
            $response   = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret_key.'&response='.$recaptcha);
            $dat        = json_decode($response);

            if( $dat->success )
                return true;
        }

        return false;
    }

    static function sendCURL($params)
    {
        $url    = $params['url'];
        $data   = $params['data'];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $ret = curl_exec($ch);
        //echo curl_errno($ch);
        //print "Error: " . curl_error($ch); 
        
        curl_close($ch);
        
        return $ret;
    }

    static function sendSOCK($params)
    {
        // Create header
        $header = "POST ".$params['path']." HTTP/1.1\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: ".strlen($params['data'])."\r\n";
        $header .= "Host: ".$params['domain']."\r\n";
        $header .= "Connection: close\r\n\r\n";

        $fp = fsockopen ($params['domain'], $params['port'], $errno, $errstr, 30);
        if (!$fp)
            return null;

        $res = '';
        fputs ($fp, $header . $params['data']);
        while (!feof($fp)) {
            $res .= fread($fp, 1024);
        }
        fclose ($fp);
        $res = explode("\r\n\r\n", $res);

        foreach( explode("\n", $res[1]) as $v ) {
            if (strpos($v, '}') !== false) {
                $response = $v;
                break;
            }
        }

        return $response;
    }
}

?>
