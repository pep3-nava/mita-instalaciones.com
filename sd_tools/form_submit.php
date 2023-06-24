<?php
include_once('IHP.php');

$type = IHP::getVar('type');

if( $type == 'contact' )
    sendEmail();
elseif( $type == 'comment' )
    sendComment();


function sendEmail()
{
    $recaptcha  = IHP::getVar('g-recaptcha-response');

    if( IHP::reCaptchaVerify($recaptcha) ) {
        // Send email only if email is confirmed
        if ( EMAIL_CONFIRMED ) {
            $dat = IHP::getVar('data');
            $dat = json_decode($dat);
            $data = sdGetContactFormData($dat);

            include_once('phpmailer/PHPMailerAutoload.php');

            // Fillter Mail Components
            $sender_email   = $data['email'];
            $sbj            = stripslashes($data['subject']);

            $content        = '<p>'.CONTENT.'</p>';
            $content       .= '<br><p style="padding: 10px; border: solid 2px #bbbbbb; margin:10px;">[message]</p>';

            $str_search     = array('[sender_name]', '[sender_email]', '[site_name]', '[form_subject]', '[message]');
            $str_replace    = array($sender_email, $sender_email, SITE_URL, $sbj, $data['message']);

            $subject        = str_replace($str_search, $str_replace, SUBJECT);
            $message        = str_replace($str_search, $str_replace, $content);

            // Set Host to connect to
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = HOSTNAME_MTA_PUBLISH;

            // Sender and Return Address
            $mail->setFrom(SENDER_EMAIL, SENDER_NAME);
            $mail->addReplyTo($sender_email, $sender_email);
            $mail->addAddress(RECEIVER_EMAIL, RECEIVER_NAME.' '.RECEIVER_LASTNAME);

            // set intialize of mailer
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = '7bit';

            // set reciever
            $mail->Subject = $subject;
            $mail->msgHTML($message);

            // Actually send the mail
            $mail->send();
        }

        $ret = array(
            'result'    => 'ok',
            'message'   => 'Message has been sent.',
        );
    } else {
        $ret = array(
            'result'    => 'falied',
            'message'   => 'Something problem.',
        );
    }

    header('Content-Type: application/json');
    echo json_encode($ret);
}

function sdGetContactFormData($dat)
{
    $data   = array();
    $m      = '';
    $name   = '';

    if( count($dat) > 0 ) {
        foreach( $dat as $d ) {
            if( $d->name == 'email' )
                $data['email'] = $d->value;

            elseif( $d->name == 'subject' ) {
                $data['subject'] = $d->value;
                $m0  = '<b>'.$d->title.'</b><br>';
                $m0 .= $d->value.'<br>';
            }
            
            else {
                if( $d->type == 'checkbox' ) {
                    if( $name != $d->name ) {
                        $name = $d->name;
                        $m .= '<br><b>'.$d->title.'</b><br>';
                        if( !empty($d->value) )
                            $m .= $d->value.'<br>';
                    } else if( !empty($d->value) ) {
                        $m .= $d->value.'<br>';
                    }
                } else {
                    if( $name != $d->name ) {
                        $name = $d->name;
                        $m .= '<br>';
                    }

                    $m .= '<b>'.$d->title.'</b><br>';
                    $m .= $d->value.'<br>';
                }
            }
        }

        // email massage
        $data['message'] = $m0.$m;

        // check subject line if it empty
        if( empty($data['subject']) )
            $data['subject'] = 'Form subject';
    }

    return $data;
}

function sendComment()
{
    $user_id    = USER_ID;
    $refid      = REFID;
    $site_id    = SITE_ID;
    $block_id   = IHP::getVar('block_id');
    $name       = IHP::getVar('name');
    $message    = IHP::getVar('message');
    $recaptcha  = IHP::getVar('g-recaptcha-response');

    if( IHP::reCaptchaVerify($recaptcha) ) {
        if (ENV === 'dev')
            $domain = 'simdif-comments.sd.test';
        else if (ENV === 'labs')
            $domain = 'sdlabcommentha.simdif.local';
        else
            $domain = 'sdprdcommentha.simdif.local';

        $params = array(
            'path'  => '/comments/postmessage',
            'data'  => 'user_id='.$user_id.'&site_id='.$site_id.'&block_id='.$block_id.'&name='.rawurlencode($name).'&message='.rawurlencode($message),
            'port'  => 80,
            'domain'=> $domain,
        );

        $response   = IHP::sendSOCK($params);
        $dat        = json_decode($response);

        if( $dat->result == 'ok' ) {
            $ret = array(
                'result'    => 'ok',
                'message'   => 'Message has been sent.',
            );
        } else {
            $ret = array(
                'result'    => 'falied',
                'message'   => 'Something problem.',
            );
        }
    } else {
        $ret = array(
            'result'    => 'falied',
            'message'   => 'Something problem.',
        );
    }

    header('Content-Type: application/json');
    echo json_encode($ret);
}


?>
