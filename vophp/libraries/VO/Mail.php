<?php
/**
 * 定义 VO_Mail邮件发送类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-06-01
 **/

defined('VOPHP') or die('Restricted access');

class VO_Mail{

	
	/**
	 * 构造方法
	 * @return VO_Mail
	 */
	public function __construct(){}
	
	/**
	 * 获取单一实例
	 * @return VO_Mail
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Mail){
			$instance = new self();
		}
		return $instance;
	}
    
    /**
	 * 发送邮件
	 */
	public function send($mail, $to, $subject='无主题', $content=''){
	   $ext = array(
            'to_mail'   => $to,
            'subject'   =>  $subject,
            'body'  =>  $content, 
       );
       
       if(empty($mail)){
            $mail = C('mail');
       }
       
	   if(!isset($mail['mode'])){
	       $mail['mode'] = 'mail';
	   }
       switch($mail['mode']){            
            case 'smtp' : 
                $info = array_merge($mail, $ext);
                $ret = $this->smtp($info);
                break;
            
            case 'mail' :
            default: 
                $info = array_merge($mail, $ext);
                $ret = $this->mail($info);
                break;
       }
       return $ret;
    }
	
	/**
	 * 通过SMTP方式发送邮件(phpMailer)
	 */
	public function smtp($info = array()){
		$default_info = array(
			'charset'	=>	'utf-8',
			'is_smtp'	=> true,
			'host'  =>  '',
			'port'	=>	25,
			'username'	=>	'',
			'password'	=>	'',
			'from'	=>	'',
			'from_name'	=>	'',
			'subject'	=>	'',
			'is_html'	=>	true,
			'body'	=>	'',
			'attachment'	=>	array(),
			'to_mail'	=> array(),
			'reply_to'	=> '',
			'reply_to_name'	=> '',
			'is_smtp_auth'	=> false,
			'is_smtp_keep_alive'	=> false,
			
		);
		
		$info = array_merge($default_info, $info);
		include VO_EXT_DIR . DS . 'mail' . DS . 'phpmailer' . DS . 'class.phpmailer.php';
		$mailer = new PHPMailer(true);
		try {
			if( $info['is_smtp'] == true ){
				$mailer->IsSMTP();
			}
			
			if( $info['is_smtp_auth'] == true ){
				$mailer->SMTPAuth = true;
			}
			
			if( $info['is_smtp_keep_alive'] == true ){
				$mailer->SMTPKeepAlive = true;
			}
			
			if(!empty($info['host'])){
				if(is_array($info['host'])){
					$info['host'] = implode(';', $info['host']);
				}
				$mailer->Host = $info['host'];
			}
			$mailer->Port = $info['port'];
			$mailer->Username = $info['username'];
			$mailer->Password = $info['password'];
			$mailer->CharSet = $info['charset'];
			
			if(!empty($info['reply_to'])){
				$mailer->AddReplyTo($info['reply_to'], $info['reply_to_name']);
			}
			if(!empty($info['from'])){
				$mailer->SetFrom($info['from'], $info['from_name']);
			}
			$mailer->Subject = $info['subject'];
			if($info['is_html']){
				$mailer->MsgHTML($info['body']);
			}else{
				$mailer->AltBody = $info['body']; // optional - MsgHTML will create an alternate automatically
			}
			if(!empty($info['to_mail'])){
				if(is_array($info['to_mail'])){
					foreach($info['to_mail'] as $k => $v){
						$mailer->AddAddress($info['to_mail'], $info['to_mail']);
					}
				}
				$mailer->AddAddress($info['to_mail'], $info['to_mail']);
			}
			
			if(!empty($info['attachment'])){
				if(is_array($info['attachment'])){
					foreach($info['attachment'] as $k => $v){
						$mailer->AddAttachment($v);      // attachment
					}
				}
				$mailer->AddAttachment($info['attachment']);      // attachment
			}
			$ret = $mailer->Send();
			return $ret;
		} catch (phpmailerException $e) {
	  		return $e->errorMessage(); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
    	  		return $e->getMessage(); //Boring error messages from anything else!
    	}
	}
    
    /**
	 * 通过PHP内置的mail函数发送(sendmail)
	 */
    public function mail($info = array()){
        $subject = "=?UTF-8?B?" . base64_encode($info['subject']) . "?="; //防止乱码
        
        $headers = 'MIME-Version: 1.0' . '\r\n'; 
        $headers .= 'Content-type: text/html; charset=' . $info['charset'] . ' \r\n'; //Additional headers 
        $headers .= 'Reply-To: ' . $info['reply_name'] . $info['reply'] . '\r\n'; 
        $headers .= 'From: ' . $info['from_name'] . $info['from'] . '\r\n'; 
        
        return mail($info['to_mail'], $subject, $info['body'], $headers);
    }
}