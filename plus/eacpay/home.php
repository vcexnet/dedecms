<?php
/**
 *
 * EACPAY
 *
 * @version        $Id: flink.php 1 15:38 2010年7月8日 $
 * @package        cansnow.cc
 * @founder        IT柏拉图, https://weibo.cc/itprato
 * @author         DedeCMS团队
 * @copyright      Copyright (c) 2007 - 2021, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.cansnow.cc/usersguide/license.html
 * @link           http://www.cansnow.ccs
 */
require_once(dirname(__FILE__)."/../../include/common.inc.php");
require_once(dirname(__FILE__)."/base.php");
if(empty($dopost)) $dopost = 'withdrawal';

if($dopost=='qrcode')
{
    
	$eac = floatval($_GET['eac']);
	$orderid = trim($_GET['orderid']);
	/*$vo =DB::fetch_first("select * from ".DB::table("eacpay_order")." where order_id='".$_GET['orderid']."'");
	if($vo){
		$eac = $vo['eac'];
	}*/
	require_once DEDEINC.'/qrcode.class.php';
	$str = "earthcoin:".$csetting['recive_token']."?amount=".$eac."&message=".$orderid;
	ob_clean();
    $qrcode = new DedeQrcode();
    header('Content-Type:image/png;');
	$qrcode->generate(array(
        'data'=>$str,
        'level'=>4,
        //'size'=>4
    ));
	exit;
}elseif($dopost=='check')
{
    require_once DEDEINC.'/payment/eacpay.php';
	$vo = $dsql->GetOne("SELECT * FROM `#@__eacpay_order` WHERE order_id='$orderid'");
    $pay = new eacpay();
	if($vo){
		$ret = $pay->checkOrder($vo);
		ob_clean();
		exit(json_encode($ret));
	}
}elseif($dopost=='success')
{
    ShowMsg("支付成功", $cfg_cmspath."/member/operation.php", 0, 3000);
    exit;
}