<?php

include("function.php");

if (!file_exists('code.txt')) {
	echo "Error code.txt file not found \n";
	exit;
}

if (!file_exists('token.txt')) {
	touch('token.txt');
}


$file = file_get_contents('token.txt');
$token = array_filter(explode(PHP_EOL, $file));
$ttoken = count($token);

$file2 = file_get_contents('code.txt');
$code = array_filter(explode(PHP_EOL, $file2));
$tcode = count($code);

function nama()
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://ninjaname.horseridersupply.com/indonesian_name.php");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	$ex = curl_exec($ch);
	preg_match_all('~(&bull; (.*?)<br/>&bull; )~', $ex, $name);
	return $name[2][mt_rand(0, 14)];
}
function register($no)
{
	$nama = nama();
	$email = str_replace(" ", "", $nama) . mt_rand(100, 999);
	$data = '{"name":"' . $nama . '","email":"' . $email . '@mail.com","phone":"+1' . $no . '","signed_up_country":"ID"}';
	$register = request("/v5/customers", "", $data);
	echo "\n";
	echo "Your Name  : " . $nama . "\n";
	echo "Your Email : " . $email . "@mail.com\n";
	echo "\n";
	if ($register['success'] == 1) {
		return $register['data']['otp_token'];
	} else {
		save("error_log.txt", json_encode($register));
		return false;
	}
}
function verif($otp, $token)
{
	$data = '{"client_name":"gojek:cons:android","data":{"otp":"' . $otp . '","otp_token":"' . $token . '"},"client_secret":"83415d06-ec4e-11e6-a41b-6c40088ab51e"}';
	$verif = request("/v5/customers/phone/verify", "", $data);
	if ($verif['success'] == 1) {
		echo "\n";
		echo "Token: " . $verif['data']['access_token'] . "\n";
		echo "Saving token...\n";
		echo "\n";
		save("token.txt", $verif['data']['access_token']);
		return $verif['data']['access_token'];
	} else {
		save("error_log.txt", json_encode($verif));
		return false;
	}
}
function login($no)
{
	$data = '{"phone":"+1' . $no . '"}';
	$register = request("/v4/customers/login_with_phone", "", $data);

	if ($register['success'] == 1) {
		return $register['data']['login_token'];
	} else {
		save("error_log.txt", json_encode($register));
		return false;
	}
}
function veriflogin($otp, $token)
{
	$data = '{"client_name":"gojek:cons:android","client_secret":"83415d06-ec4e-11e6-a41b-6c40088ab51e","data":{"otp":"' . $otp . '","otp_token":"' . $token . '"},"grant_type":"otp","scopes":"gojek:customer:transaction gojek:customer:readonly"}';
	$verif = request("/v4/customers/login/verify", "", $data);
	if ($verif['success'] == 1) {
		echo "\n";
		echo "Token: " . $verif['data']['access_token'] . "\n";
		echo "Saving token...\n";
		echo "\n";
		save("token.txt", $verif['data']['access_token']);
		return $verif['data']['access_token'];
	} else {
		save("error_log.txt", json_encode($verif));
		return false;
	}
}
function claim($token, $code)
{
	foreach ($code as $m => $b) {
		$data = '{"promo_code":"' . $b . '"}';
		$claim = request("/go-promotions/v1/promotions/enrollments", $token, $data);
		if ($claim['success'] == 1) {
			$num = $m + 1;
			echo "[$num] " . $b . "\n";
			echo $claim['data']['message'] . "\n";
			sleep(5);
		} else {
			$num = $m + 1;
			echo "[$num] " . $b . "\n";
			save("error_log.txt", json_encode($claim));
			echo $claim['errors']['0']['message'] . "\n";
			sleep(5);
		}
	}
}
function profile($token)
{
	$get = request("/gopoints/v3/wallet/vouchers?limit=10&page=1", $token);
	if ($get['success'] == 1) {
		foreach ($get['data'] as $no => $data) {
			$num = $no + 1;
			echo "[$num] " . $data['title'] . " Exp: " . $data['expiry_date'] . "\n";
			if ($data['title'] == "Voucher Rp 20.000 pakai GoFood") {
				echo "You got 20k Voucher GoFood!!!\n";
				save('20k.txt', $token);
			}
		}
	} else {
		save("error_log.txt", json_encode($get));
		return $get['errors']['0']['message'];
	}
}
echo "\n";
echo "Total Token: " . $ttoken . "\n";
echo "Total Code: " . $tcode . "\n";
echo "\n";
echo "What do you want?\n";
echo "1 => Register\n";
echo "2 => Login\n";
echo "3 => Claim code with Token\n";
echo "4 => Check Account Vouchers\n";
echo "\n";
echo "Option: ";
$type = trim(fgets(STDIN));
if ($type == 1) {
	echo "\n";
	echo "It's Register Way\n";
	echo "Input US Phone Number\n";
	echo "Enter Number: ";
	$nope = trim(fgets(STDIN));
	$register = register($nope);
	if ($register == false) {
		echo "Failed to Get OTP, Use Unregistered Number!\n";
	} else {
		echo "Enter Your OTP: ";
		$otp = trim(fgets(STDIN));
		$verif = verif($otp, $register);
		if ($verif == false) {
			echo "Failed to Registering Your Number!\n";
		} else {
			echo "Ready to Claim... \n";
			echo "\n";
			claim($verif, $code);
			echo "\n";
			echo "Your Voucher:\n";
			profile($verif);
			echo "\n";
		}
	}
} else if ($type == 2) {
	echo "\n";
	echo "It's Login Way\n";
	echo "Input US Phone Number\n";
	echo "Enter Number: ";
	$nope = trim(fgets(STDIN));
	$login = login($nope);
	if ($login == false) {
		echo "Failed to Get OTP!\n";
	} else {
		echo "Enter Your OTP: ";
		$otp = trim(fgets(STDIN));
		$verif = veriflogin($otp, $login);
		if ($verif == false) {
			echo "Failed to Login with Your Number!\n";
		} else {
			echo "Ready to Claim... \n";
			echo "\n";
			claim($verif, $code);
			echo "\n";
			echo "Your Voucher:\n";
			profile($verif);
			echo "\n";
		}
	}
} elseif ($type == 3) {
	echo "\n";
	echo "Ready to Claim... \n";
	echo "\n";
	foreach ($token as $n => $a) {
		echo "Token: " . $a;
		echo "\n";
		claim($a, $code);
		echo "\n";
	}
} elseif ($type == 4) {
	foreach ($token as $n => $a) {
		echo "\n";
		echo "Token: " . $a;
		echo "\n";
		profile("$a");
	}
	echo "\n";
	$makan = file_get_contents('makan2.txt');
	$makan2 = array_filter(explode(PHP_EOL, $makan));
	$voc20 = count($makan2);
	echo "Total Voucher 20k = " . $voc20;
	echo "\n\n";
}
