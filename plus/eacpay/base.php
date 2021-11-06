
<?php
if(!defined('DEDEINC')) {
	exit('Access Denied');
}

$bizhongarr = array(
    'RMB'=>'￥',
    'USD'=>'$',
    'EUR'=>'€',
);

$bizhongtxtarr = array(
    'RMB'=>'人民币',
    'USD'=>'美元',
    'EUR'=>'欧元',
);
function initConfig(){
    if(file_exists(DEDEROOT."/plus/eacpay/settings.php")){
        $settings=require_once DEDEROOT."/plus/eacpay/settings.php";
    }else{
        $settings=array(
			"allow_cash"=>"0",
			"moneybl"=>"1",
			"recive_token"=>"",
			"bizhong"=>"RMB",
			"eacpay_server"=>"https://blocks.deveac.com:4000",
			"exhangeapi"=>"https://api.aex.zone/v3/depth.php",
			"receiptConfirmation"=>"3",
			"maxwaitpaytime"=>120,
			"notice"=>"请不要修改付款页面的任何信息，否则系统无法识别订单将导致不会自动发货",
		);
    }
	if(file_exists(DEDEDATA.'/payment/eacpay.php')){
		require_once DEDEDATA.'/payment/eacpay.php';
	}else{
		$payment=array();
	}
	$config = array_merge($payment,$settings);
	return $config;
}
function P($arr=""){
	echo '<pre>';
	print_r($arr);
	echo '</pre>';
}
$csetting = initConfig();
function cansnow_get($url) {
	if (function_exists('curl_init')) {
		$curl = curl_init(); 
		curl_setopt($curl, CURLOPT_URL, $url); 
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		//curl_setopt($curl, CURLOPT_REFERER, $_G['siteurl']); 
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
		$result = curl_exec($curl); 
		curl_close($curl);
	} else {
		$result = file_get_contents($url);
	}
	return $result;
}
function cansnow_post($url,$data=array()) {
	if (function_exists('curl_init')) {
		$curl = curl_init(); 
		curl_setopt($curl, CURLOPT_URL, $url); 
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

		$result = curl_exec($curl); 
		curl_close($curl);
	} else {
		die('need curl');
	}
	return $result;
}

$bizhong = $bizhongarr[$csetting['bizhong']];
$bizhongTxt = $bizhongtxtarr[$csetting['bizhong']];
function get_block_height(){
	global $csetting;
	return cansnow_get($csetting['eacpay_server']."/getblockcount/Block_height");
}
function test($vo){
	global $csetting;
	//P($csetting);
	$exp = ceil((time()-$vo['create_time'])/60);
	$exp = $exp < 10 ? 10 : $exp;
	//$vo['eac'] = 5.498;
	$url = $csetting['eacpay_server']."/checktransaction/".$csetting['recive_token']."/".$vo['eac'].'/'.$vo['order_id'].'/'.($vo['block_height']+1).'/1000';
	//echo $url;
	$ret = cansnow_get($url);
	echo ($ret);
}

function getExchange(){
	global $csetting;
	$priceType = 'CNY';
	switch($csetting['bizhong']){
		case 'USD':
			$priceType = 'USD';
			break;
		case 'EUR':
			$priceType = 'EUR';
			break;
		default:
			break;
	}
	$ret = cansnow_post($csetting['exhangeapi'],array('mk_type'=>'usdt','coinname'=>'eac'));
	$ret = json_decode($ret,true);
	$unitPrice = 0;
	$ret = $ret['data']['bids'];
	//P(json_encode($ret));
	
	foreach( $ret as $k=>$v){
		$unitPrice +=$v[0];
		if($k==4){
			break;
		}
	}
	$unitPrice = round($unitPrice/5,6);
	$hl = huiulv($priceType);
	$unitPrice=$unitPrice * $hl;
	return round($unitPrice,6);
}
function getLastUpdateDate(){
	$verLockFile = DEDEDATA.'/admin/ver.txt';
	$fp = fopen($verLockFile,'r');
	$upTime = trim(fread($fp,64));
	fclose($fp);
	$oktime = substr($upTime,0,4).'-'.substr($upTime,4,2).'-'.substr($upTime,6,2);
	return $oktime;
}
function UsdtPrice($priceType='CNY'){
    if($priceType =='USD'){return 1;}
	$hlret = cansnow_get('http://data.bank.hexun.com/other/cms/fxjhjson.ashx?callback=cansnow');
	$hlret = iconv('GB2312','UTF-8',$hlret);
	$USDrate = 1;
	preg_match("/\{currency:'美元',refePrice:'(.*?)',code:'USD.*?\}/",$hlret,$macths);
	if(count($macths)!=0){
		$USDrate = floatval($macths[1])/100;
	}
    if($priceType =='CNY'){
        return $USDrate;
    }else if($priceType =='EUR'){
    	$EURrate=1;
    	preg_match("/\{currency:'歐元',refePrice:'(.*?)',code:'EUR.*?\}/",$hlret,$macths);
    	if(count($macths)!=0){
    		$EURrate = floatval($macths[1])/100;
    	}
        return $USDrate / $EURrate;
    }
	return 1;
}

function huiulv($priceType='CNY'){
    if($priceType =='USD'){return 1;}
	$hlret = cansnow_get('https://api.exchangerate-api.com/v4/latest/USD');
	$hlret=json_decode($hlret,true);
	$rate = $hlret['rates'];
	switch($priceType){
		case 'CNY':
			return $rate['CNY'];
			break;
		case 'EUR':
			return $rate['EUR'];
			break;
		default:
			return 1;
			break;
	}
}