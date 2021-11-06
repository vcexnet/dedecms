<?php 
/**
 * 商品订单
 * 
 * @version        $Id: shops_orders.php 1 8:38 2010年7月9日 $
 * @package        DedeCMS.Member
 * @founder        IT柏拉图, https://weibo.com/itprato
 * @author         DedeCMS团队
 * @copyright      Copyright (c) 2007 - 2021, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__)."/config.php");
require_once(dirname(__FILE__)."/../plus/eacpay/base.php");
include_once DEDEINC.'/datalistcp.class.php';
CheckRank(0,0);
$menutype = 'mydede';
$menutype_son = 'op';
if(!isset($dopost)) $dopost = 'withdrawal';

/**
 *  获取状态
 *
 * @access    public
 * @param     string  $sta  状态ID
 * @param     string  $oid  订单ID
 * @return    string
 */
function GetSta($sta,$oid)
{
    global $dsql;
    $row = $dsql->GetOne("SELECT p.name FROM #@__shops_orders AS s LEFT JOIN #@__payment AS p ON s.paytype=p.id WHERE s.oid='$oid'");
    if($sta==0)
    {
        return  '未付款('.$row['name'].') < <a href="../plus/carbuyaction.php?dopost=memclickout&oid='.$oid.'" target="_blank">去付款</a>';
    } else if ($sta==1){
        return '已付款,等发货';
    } else if ($sta==2){
        return '<a href="shops_products.php?do=ok&oid='.$oid.'">确认</a>';
    } else {
        return '已完成';
    }
}
if($dopost=='withdrawal')
{
    require_once(DEDEMEMBER."/config.php");
    if($csetting['allow_cash']){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $cash_address = $_POST['cash_address'];
            $amount = intval($_POST['money']);
            if(!$cash_address){			
                ShowMsg("提现地址必须填写","eacpay.php");
            }
            
            $moneymin =  1;
            if ($amount < $moneymin || $amount == '') {
                ShowMsg("提现金额不能小于".$moneymin,"eacpay.php");
                exit;
            }
            $mid = $cfg_ml->M_ID;
			$canCav = $cfg_ml->M_Money;
            if ($amount > $canCav) {
                ShowMsg("提现金额不能大于可提现金币：".$canCav."个","eacpay.php");
                exit;
            }
            
            $isok = $dsql->ExecuteNoneQuery("update #@__member set `money` = `money`-{$amount} where `mid`={$mid}");
            if(!$isok)
            {
                ShowMsg("数据库出错，请重新尝试！".$dsql->GetError(),"eacpay.php");
                exit;
            }
            $exchangeData = getExchange();
            $eac = round($amount/$csetting['moneybl']/$exchangeData,4);
            list($msec, $sec) = explode(' ', microtime());
            $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
            $vo = array(
                "uid"			=>	$mid,
                "order_id"		=>	$_SERVER['SERVER_NAME']."_withdraw_".$uid.'_'.$msectime.rand(100000,999999),
                "amount"		=>	$amount,
                "eac"			=>	$eac,
                "real_eac"			=>	0,
                'address'		=>	$cash_address,
                'block_height'  =>  0,
                "create_time"	=>	time(),
                "last_time"		=>	time(),
                "pay_time"		=>	0,
                "type"			=>	'cash',
                "status"		=>	'wait',
            );
            $fields = array();
            $values = array();
            foreach($vo as $field => $value){
                array_push($fields,"`".$field."`");
                array_push($values,"'".$value."'");
            }
            $sql = 'insert into #@__eacpay_order ('.implode(',',$fields).') values ('.implode(',',$values).');';
            $isok = $dsql->ExecuteNoneQuery($sql);
            
            if(!$isok)
            {
                $dsql->ExecuteNoneQuery("update #@__member set `money` = `money`+{$amount} where `mid`={$mid}");
                ShowMsg("数据库出错，请重新尝试1！".$dsql->GetError(),"eacpay.php");
                exit;
            }
            $addressVo = $dsql->GetOne("select * from #@__eacpay_address where `uid` = {$mid}");
            if(!$addressVo){
                $sql = "insert into #@__eacpay_address (`uid`,`address`) values ({$mid},'{$cash_address}');";
                $isok = $dsql->ExecuteNoneQuery($sql);
            }
            ShowMsg('申请成功,等待审核',"eacpay.php?dopost=withdrawallog");
        }else{
            $addressVo = $dsql->GetOne('select * from #@__eacpay_address where `uid` = '.$mid);
            if(!$addressVo){
                $cash_address = '';
            }else{
                $cash_address = $addressVo['address'];
            }
            $bizhong = $bizhongtxtarr[$csetting['bizhong']];
            $exchangeData = getExchange();
            include_once(DEDETEMPLATE.'/plus/eacpay/withdrawal.htm');
        }
    }else{
        ShowMsg('系统没有开启提现功能',"eacpay.php");
    }
    exit;
}
elseif($dopost=='withdrawallog')
{
    require_once(DEDEMEMBER."/config.php");
    include_once DEDEINC.'/datalistcp.class.php';
    $mid = $cfg_ml->M_ID;
    $sql="select * from #@__eacpay_order where uid=$mid and type='cash' order by create_time desc";
    if($order_id){
        $sql="select * from #@__eacpay_order where uid=$mid and order_id like '%{$order_id}%' and type='cash' order by create_time desc";
    }
    //初始化
    $dlist = new DataListCP();
    $dlist->pageSize = 30;
    
    //GET参数
    $dlist->SetParameter('dopost', 'withdrawallog');
    if(!empty($mid)) $dlist->SetParameter('mid', $mid);
    $dlist->SetParameter('flag', $flag);
    $dlist->SetParameter('f', $f);
    
    //模板
    $dlist->SetTemplate(DEDETEMPLATE.'/plus/eacpay/withdrawallog.htm');
    //查询
    $dlist->SetSource($sql);
    
    //显示
    $dlist->Display();
    // echo $dlist->queryTime;
    $dlist->Close();
    exit;
}
elseif($dopost=='order')
{
	$getExchange = getExchange();
	$rs = $dsql->GetOne("SELECT * FROM `#@__eacpay_order` WHERE order_id='".$orderid."'");
	$eac = $rs['eac'];
	require_once DEDETEMPLATE.'/plus/eacpay/order.htm';
    exit;
}

