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
/*
    [DEDEADMIN] => G:/eacpay/eac.pay/dede
    [DEDE_ENVIRONMENT] => production
    [DEDEINC] => G:/eacpay/eac.pay/include
    [DEDEROOT] => G:/eacpay/eac.pay
    [DEDEDATA] => G:/eacpay/eac.pay/data
    [DEDEMEMBER] => G:/eacpay/eac.pay/member
    [DEDETEMPLATE] => G:/eacpay/eac.pay/templets
    [DEDEMODEL] => ./model
    [DEDECONTROL] => ./control
    [DEDEAPPTPL] => ./templates
*/
require_once(dirname(__FILE__).'/../../include/common.inc.php');
require_once(dirname(__FILE__)."/base.php");
require_once(DEDEINC.'/payment/eacpay.php');
require_once(DEDEINC.'/datalistcp.class.php');
if(empty($dopost)) $dopost = '';

if($dopost=='recharge')
{
    $map=array("o.`type`='recharge'");
    if($keyword){
        array_push($map,"o.`order_id`='{$keyword}'");
    }
    if($flag){
        array_push($map,"o.`status`='{$flag}'");
    }
    $query = "SELECT o.*,m.uname FROM `#@__eacpay_order` o join `#@__member` m on m.mid=o.`uid` where ".implode(' and ',$map)." ORDER BY o.`create_time` DESC";
    
    //初始化
    $dlist = new DataListCP();
    $dlist->pageSize = 30;
    
    //GET参数
    $dlist->SetParameter('dopost', 'recharge');
    $dlist->SetParameter('keyword', $keyword);
    if(!empty($mid)) $dlist->SetParameter('mid', $mid);
    $dlist->SetParameter('cid', $cid);
    $dlist->SetParameter('flag', $flag);
    $dlist->SetParameter('orderby', $orderby);
    
    //模板
    $dlist->SetTemplate(DEDETEMPLATE.'/plus/eacpay/admin_recharge.htm');
    
    //查询
    $dlist->SetSource($query);
    
    //显示
    $dlist->Display();
    // echo $dlist->queryTime;
    $dlist->Close();
}elseif($dopost=='changestatus'){
	$orderid = trim($_REQUEST['order_id']);
    $dsql->ExecuteNoneQuery("update #@__eacpay_order set `real_eac` = `eac` where `order_id`={$vo['order_id']}");
    $vo = $dsql->GetOne("select * from #@__eacpay_order where `order_id` = '".$orderid."'");
    if(!$vo){
        exit('没有找到订单');
    }
    $data=array(
        'status'=>trim($_REQUEST['status'])
    );
    if($data['status']=='complete'){
        $data['pay_time'] = time();
        $data['last_time'] = time();
    }
    $values = array();
    foreach($data as $field => $value){
        array_push($values,"{$field}='{$value}'");
    }
    $sql = "update #@__eacpay_order set ".implode(',',$values)." where order_id='{$orderid}';";
    ob_clean();
    $isok = $dsql->ExecuteNoneQuery($sql);
    if(!$isok)
    {
        exit("数据库出错，请重新尝试11！");
    }else{
        $amount = $vo['amount'];
        if($data['status']=='reject' && $vo['type'] == 'cash'){
            $amount = $vo['amount'];
            $dsql->ExecuteNoneQuery("update #@__member set `money` = `money`+{$amount} where `mid`={$vo['uid']}");
        }
        if($data['status'] == 'complete' && $vo['type']=='recharge'){
            $dsql->ExecuteNoneQuery("update #@__eacpay_order set `real_eac` = `eac` where `order_id`={$vo['order_id']}");
            $eacpay = new eacpay();
            $order_sn=explode('_',$orderid)[3];
            $ret === $eacpay->updateOrder($order_sn);
            if($ret === true){

            }else{
                exit($ret);
            }
        }
        exit("ok");
    }

}elseif($dopost=='withdrawal'){
    $map=array("o.`type`='cash'");
    if($keyword){
        array_push($map,"o.`order_id`='{$keyword}'");
    }
    if($flag){
        array_push($map,"o.`status`='{$flag}'");
    }
    $query = "SELECT o.*,m.uname FROM `#@__eacpay_order` o join `#@__member` m on m.mid=o.`uid` where ".implode(' and ',$map)." ORDER BY o.`create_time` DESC";
    

    if(empty($f) || !preg_match("#form#", $f)) $f = 'form1.arcid1';
    
    //初始化
    $dlist = new DataListCP();
    $dlist->pageSize = 30;
    $flagsArr =array(
        array(
        "dopost"=>"dopost",
        )
    );
    //GET参数
    $dlist->SetParameter('flagsArr', 'flagsArr');
    $dlist->SetParameter('dopost', 'withdrawal');
    $dlist->SetParameter('keyword', $keyword);
    if(!empty($mid)) $dlist->SetParameter('mid', $mid);
    $dlist->SetParameter('cid', $cid);
    $dlist->SetParameter('flag', $flag);
    $dlist->SetParameter('orderby', $orderby);
    $dlist->SetParameter('f', $f);
    
    //模板
    $dlist->SetTemplate(DEDETEMPLATE.'/plus/eacpay/admin_withdrawal.htm');
    
    //查询
    $dlist->SetSource($query);
    
    //显示
    $dlist->Display();
    // echo $dlist->queryTime;
    $dlist->Close();
}elseif($dopost=='qrcode')
{
    
	$orderid = trim($_GET['orderid']);
    $vo = $dsql->GetOne("select * from #@__eacpay_order where `order_id` = '".$orderid."'");
	require_once DEDEINC.'/qrcode.class.php';
	$str = "earthcoin:".$vo['address']."?amount=".$vo['eac']."&message=".$orderid;
	ob_clean();
    $qrcode = new DedeQrcode();
    header('Content-Type:image/png;');
	$qrcode->generate(array(
        'data'=>$str,
        'level'=>4,
        //'size'=>4
    ));
	exit;
}elseif($dopost=='success')
{
    ShowMsg($msg, "javascript:;", 0, 3000);
    exit;
}elseif($dopost=='settings'){
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        file_put_contents(DEDEROOT."/plus/eacpay/settings.php",'<?php return '.var_export($_POST,true).';?>');
        ob_clean();
        exit('ok');
        ShowMsg("修改成功", "admin.php?dopost=settings", 0, 3000);
    }else{
        include_once(DEDETEMPLATE.'/plus/eacpay/admin_settings.htm');
    }
    exit;
}