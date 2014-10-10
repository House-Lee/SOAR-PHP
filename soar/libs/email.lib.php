<?php
date_default_timezone_set(SoarConfig::get("main.timezone"));
class Email {
	private static $sender_addr_;
	private static $sender_name_;
	private static $sender_pwd_;
	private static $smtp_server_;
	private static $smtp_port_;
	private static $secure_;
	private static $need_login_;

	public static $instance;

	public static function init() {
		if (!self::$instance)
			self::$instance = new self();
		return self::$instance;
	}

	public function __construct() {
				self::$sender_addr_ = "sample-addr@sample.com";
				self::$sender_name_ = "sample-sender";
				self::$sender_pwd_ = "sample-password";
				self::$smtp_server_ = "mail.sample-server.com";//HOST
				self::$smtp_port_ = "465";//25
				self::$secure_ = "no";//no
				self::$need_login_ = true;
		require_once dirname( __FILE__ )."/phpmailer/class.phpmailer.php";
	}

	public function SendMail($receiver , $subject , $body, $cc = null, $bcc = null) {
		$mail = new PHPMailer(true);
		$mail->IsSMTP();
		try {
			$mail->Host = self::$smtp_server_;
			$mail->Port = self::$smtp_port_;
			$mail->CharSet = "UTF-8";
			if (self::$secure_ != "no") {
				$mail->SMTPAuth = true;
				$mail->SMTPSecure = self::$secure_;
			}
			if (self::$need_login_) {
				$mail->Username = self::$sender_addr_;
				$mail->Password = self::$sender_pwd_;
			}
			$mail->SetFrom(self::$sender_addr_ , self::$sender_name_);

			if(is_array($receiver)) {
				foreach ($receiver as $rec) {
					$mail->AddAddress($rec);
				}
			}else {
				$mail->AddAddress($receiver);
			}
			if($cc != null) {
				if(is_array($cc)) {
					foreach ($cc as $c) {
						$mail->AddCC($c);
					}
				} else {
					$mail->AddCC($cc);
				}
			}
			if($bcc != null) {
				if(is_array($bcc)) {
					foreach ($bcc as $bc) {
						$mail->AddBCC($bc);
					}
				} else {
					$mail-> AddBCC($bcc);
				}
			}
			$mail->Subject = $subject;
			$mail->MsgHTML($body);
			$mail->Send();

		} catch (phpmailerException $e) {
			echo $e->errorMessage();
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
}
Email::init();