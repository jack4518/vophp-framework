<?php
class JCommon{
	/**
	 * 发送邮件
	 *
	 * @access	public
	 * @param	string 要转换的字符串
	 * @param	boolean	$jsSafe		是否为字符串提供安全转换(Javascript过滤)
	 * @since	1.5
	 *
	 */
	function sendMail($recipient, $subject, $body, $attachment=null, $mode=0, $cc=null, $bcc=null, $replyto=null, $replytoname=null )
	{
	 	// 获取一个JMail实例
		$mail = 
		
		$jconfig = & JFactory::getConfig();

		$mail->setSender(array($jconfig->mailfrom, $jconfig->fromname));
		$mail->setSubject($subject);
		$mail->setBody($body);
		if( $jconfig->mailer == "smtp"){
			$mail->useSmtp($jconfig->smtpauth,$jconfig->smtphost,$jconfig->smtpuser,$jconfig->smtppass);
		} else {
			$mail->useSendmail();
		}

		// Are we sending the email as HTML?
		if ( $mode ) {
			$mail->IsHTML(true);
		}

		$mail->addRecipient($recipient);
		$mail->addCC($cc);
		$mail->addBCC($bcc);
		$mail->addAttachment($attachment);

		// Take care of reply email addresses
		if( is_array( $replyto ) ) {
			$numReplyTo = count($replyto);
			for ( $i=0; $i < $numReplyTo; $i++){
				$mail->addReplyTo( array($replyto[$i], $replytoname[$i]) );
			}
		} elseif( isset( $replyto ) ) {
			$mail->addReplyTo( array( $replyto, $replytoname ) );
		}
		return  $mail->Send();
	}
}
?>