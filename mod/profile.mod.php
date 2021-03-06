<?php

class profile {
	private $options;

	public function __construct($options) {
		$this->options = $options;
	}

	private function overview() {
		global $smarty, $user;

		$smarty->assign("user", $user->getUid());
		$mailadresses = $user->getMails();
		$mails = array();
		foreach ($mailadresses as $mail) {
			$mails[] = array($mail, $user->isVerified($mail));
		}
		$smarty->assign("mails", $mails);
		return $smarty->fetch("profile.tpl");
	}

	private function addMail() {
		global $config, $smarty, $user, $userdb;

		$mail = stripslashes($_POST["mail"]);
		$smarty->assign("mail", $mail);
		if (isset($_POST["act"])) {
			if (!$userdb->isValidMailAddress($mail)) {
				$smarty->assign("mailinvalid", 1);
				return $smarty->fetch("add_mail.tpl");
			} else if (is_array($user->getMails()) && in_array($mail, $user->getMails())) {
				return $this->overview();
			} else if ($config["misc"]["singletonmail"] && $userdb->mailUsed($mail)) {
				$smarty->assign("mailinuse", 1);
				return $smarty->fetch("add_mail.tpl");
			} else {
				$user->addMail($mail);
				$user->save();
				echo "<p>Die E-Mail Adresse wurde erfolgreich hinzugef&uuml;gt.</p>";
				if (!$user->isVerified($mail)) {
					header("Location: index.php?module=profile&do=verify_mail&mail=" . urlencode($mail));
					exit;
				}
				return $this->overview();
			}
		} else {
			return $smarty->fetch("add_mail.tpl");
		}
	}

	private function deleteMail() {
		global $smarty, $user, $userdb;

		$smarty->assign("mail", stripslashes($_REQUEST["mail"]));
		$allmails = $user->getMails();
		$mails = array();
		foreach ($allmails as $mail) {
			if ($user->isVerified($mail)) {
				$mails[] = $mail;
			}
		}
		$smarty->assign("mails", $mails);
		if (isset($_POST["act"])) {
			$mail = stripslashes($_POST["mail"]);
			$listsoption = stripslashes($_POST["listsoption"]);
			$movemail = stripslashes($_REQUEST["movemail"]);
			if (!in_array($mail, $user->getMails())) {
				$smarty->assign("mailnotinuse", 1);
				return $this->overview();
			} else if ($listsoption == "move" && !$user->isVerified($movemail)) {
				$smarty->assign("notverified", 1);
				return $smarty->fetch("delete_mail.tpl");
			} else if ($listsoption == "move" && $mail == $movemail) {
				$smarty->assign("sourceequalsdestination", 1);
				return $smarty->fetch("delete_mail.tpl");
			} else {
				$mailman = new Mailman($user);
				if ($listsoption == "delete") {
					$mailman = new Mailman($user);
					foreach ($mailman->getLists() as $list) {
						if ($list->hasMember($mail)) {
							$list->removeMember($mail);
						}
					}
				}
				if ($listsoption == "move") {
					$mailman = new Mailman($user);
					foreach ($mailman->getLists() as $list) {
						if ($list->hasMember($mail)) {
							$list->removeMember($mail);
							$list->addMember($movemail);
						}
					}
				}
				$user->deleteMail($mail);
				$user->save();
				$smarty->assign("success", 1);
				return $this->overview();
			}
		} else {
			return $smarty->fetch("delete_mail.tpl");
		}
	}

	private function changePassword() {
		global $smarty, $user, $userdb;

		ob_start();

		if (isset($_POST["pass"]) && isset($_POST["pass_repeat"])) {
			if (empty($_POST["pass"])) {
				echo $this->overview();
			} else if (!isset($_POST["old_pass"])) {
				echo "<p>Sie haben das alte Passwort nicht angegeben.</p>";
				$smarty->display("change_password.tpl");
			} else if (!$userdb->authenticate($user->getUid(), $_POST["old_pass"])) {
				echo "<p>Das alte Passwort ist falsch.</p>";
				$smarty->display("change_password.tpl");
			} else if ($_POST["pass"] != $_POST["pass_repeat"]) {
				echo "<p>Die beiden Passw&ouml;rter stimmen nicht &uuml;berein.</b>";
				$smarty->display("change_password.tpl");
			} else if (strlen($_POST["pass"]) < 6) {
				echo "<p>Das Passwort muss mindestens 6 Zeichen lang sein.";
				$smarty->display("change_password.tpl");
			} else {
				echo "<p>Das Passwort wurde erfolgreich ge&auml;ndert.";
				$user->changePassword($_POST["pass"]);
				$user->save();
				echo $this->overview();
			}
		} else {
			$smarty->display("change_password.tpl");
		}

		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	private function verify_mail() {
		global $smarty, $user, $config;

		$mail = stripslashes($_REQUEST["mail"]);
		if ($user->isVerified($mail)) {
			return "<p>Diese Mailadresse wurde bereits verifiziert.</p>"; 
		}

		if (isset($_POST["send"])) {
			$hash = new Hash($user->getUid() . "\0" . $mail);
			$verification_link = $config['site']['url'] . "/index.php?module=verify&v=" . urlencode($hash);
			$text = <<<verification_mail
Ahoi {$user->getUid()},

jemand hat einen Account im User Control Panel der Jungen Piraten
( http://ucp.junge-piraten.de/ ) erstellt und dabei deine E-Mail
Adresse benutzt.
Um diese E-Mail Adresse zu bestätigen, klick bitte auf:
{$verification_link}

Falls du den Account nicht erstellt hast, ignoriere diese E-Mail
einfach :o)

Klarmachen zum Ändern
verification_mail;
			mail($mail, "[Junge Piraten] =?UTF-8?Q?Best=C3=A4tigung?= deiner E-Mail Adresse", $text, "From: " . $config["mail"]["from"] . "\n" . "Content-Type: text/plain; Charset=UTF-8");
			echo "<p>Die Best&auml;tigungsmail wurde versandt.</p>";
			echo $this->overview();
		} else {
			$smarty->assign("mail", $mail);
			return $smarty->fetch("verify_mail.tpl");
		}
	}

	public function main() {
		global $config, $smarty, $user;

		$do = isset($_REQUEST["do"]) ? stripslashes($_REQUEST["do"]) : "";
		switch ($do) {
			case "add_mail":
				return $this->addMail();
			case "delete_mail":
				return $this->deleteMail();
			case "verify_mail":
				return $this->verify_mail();
			case "change_password":
				return $this->changePassword();
			default:
				return $this->overview();
		}
	}

}

?>
