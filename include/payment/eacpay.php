<?php
if(!defined('DEDEINC')) exit('Request Error!');
/**
 * eac地球币支付接口类 www.eacpay.com
 */
require_once(DEDEROOT."/plus/eacpay/base.php");
class eacpay
{
    var $dsql;
    var $mid;
    var $return_url = "/plus/eacpay/home.php?dopost=return";
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function eacpay()
    {
        global $dsql;
        $this->dsql = $dsql;
    }

    function __construct()
    {
        $this->eacpay();
    }
    
    /**
     *  设定接口会送地址
     *
     *  例如: $this->SetReturnUrl($cfg_basehost."/tuangou/control/index.php?ac=pay&orderid=".$p2_Order)
     *
     * @param     string  $returnurl  会送地址
     * @return    void
     */
    function SetReturnUrl($returnurl='')
    {
        if (!empty($returnurl))
        {
            $this->return_url = $returnurl;
        }
    }

    /**
    * 生成支付代码
    * @param   array   $order      订单信息
    * @param   array   $payment    支付方式信息
    */
    function GetCode($order, $payment)
    {        
        global $mid,$cfg_cmspath,$bizhongTxt;
        //对于二级目录的处理
        $sdorderno= $order['out_trade_no'];
        $total_fee=$order['price'];
        $getExchange = getExchange();
        $eac = round($total_fee / $getExchange,4);
        $orderid = $_SERVER['SERVER_NAME']."_recharge_".$mid.'_'.$sdorderno;
        $block_height = get_block_height();
        
	    $rs = $this->dsql->GetOne("SELECT * FROM `#@__eacpay_order` WHERE order_id='".$orderid."'");
        if(!$rs){
            $inquery = "INSERT INTO #@__eacpay_order(`uid`,`order_id`,`amount`,`eac`,`real_eac`,`address`,`block_height`,`create_time`,`pay_time`,`last_time`,`status`,`type`) VALUES ('$mid', '{$orderid}', '{$total_fee}' , '{$eac}' , '0' , '' , '{$block_height}' , ".time()." ,0, ".time().",'wait','recharge');";
            $isok = $this->dsql->ExecuteNoneQuery($inquery);
            if(!$isok)
            {
                echo "数据库出错，请重新尝试！".$this->dsql->GetError();
                exit();
            }
        }else{
            $inquery = "update #@__eacpay_order set `amount` = $total_fee,`eac`=$eac,`block_height`=$block_height,`create_time`=".time().",`last_time`=".time().",`status`='wait',`type`='recharge' where order_id='".$orderid."';";
            $isok = $this->dsql->ExecuteNoneQuery($inquery);
            if(!$isok)
            {
                echo "数据库出错，请重新尝试！".$this->dsql->GetError();
                exit();
            }
        }
        /* 清空购物车 */
        require_once DEDEINC.'/shopcar.class.php';
        $cart     = new MemberShops();
        $cart->clearItem();
        $cart->MakeOrders();
        $getLastUpdateDate = strtotime(getLastUpdateDate());
        if($getLastUpdateDate<strtotime('2021-10-22')){
            return $cfg_cmspath.'/member/eacpay.php?dopost=order&orderid='.$orderid;
        }else{
            $button = '<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
                <script src="/templets/plus/eacpay/pay.js"></script>
                </td></tr></tfoot><tbody>
                <tr>
                    <td width="180" class="td1">EAC即时价:</td>
                    <td><strong id="exchangeData">'.$getExchange. $bizhongTxt.'</strong></td>
                </tr>
                <tr>
                    <td width="180" class="td1">约合EAC:</td>
                    <td><strong id="exchangeData">'.$eac.'</strong></td>
                </tr>
                <tr>
                    <td width="180" class="td1">扫描支付:</td>
                    <td>
                        <img src="/plus/eacpay/home.php?dopost=qrcode&orderid='.$orderid.'&eac='.$eac.'" />
                    </td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                <td>
                </td>
                    <td style="text-align:left;">
                        <input type="submit" value="先支付，后点我" id="ajaxgetresult" data-orderid="'.$orderid.'" style="width: auto;background-repeat: repeat-x;padding: 5px 10px;background: #ff5f00;border: 1px solid #b3a5a5;color: #fff;">
                        <div id="eacpayresult" style="display: none;">
                            <div class="resultmsg" style="text-align: center;font-size: 16px;margin-bottom: 15px;">正在确认订单，请稍等...</div>
                            <div class="loading" style="width: 100%;height: 8px;background: #999999;border-radius: 2px;">
                                <div class="bar" style="width: 0%;background: #ff5f00;height: 100%;transition: all 0.2s;border-radius: 2px;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                    </td>
                    <td style="text-align:left;">
                        <div class="eacpay_remark" style="position: absolute;top: 0;right: 0;background: #fff;padding: 10px;margin-top: 25px;">
                            <p>人民币现金 1元 = '.round(1 / $getExchange,4).'个eac</p>
                            <p>EACPAY手机端区块链钱包下载:</p>
                            <p>1、google play</p>
                            <p>2、<a href="http://www.eacpay.com" target="_blank">eacpay.com官网下载</a></p>
                            <p>3、手机浏览器扫一扫，下载EACPAY</p>
                            <img src="/templets/plus/eacpay/app.jpg" width="160" height="190"/>
                        </div>
            ';
            return $button;
        }
    }

    
    function checkOrder($vo=array()){
        if($vo['status'] == 'complete'){
            return 'ok';
        }
        global $csetting;
        $exp = ceil((time()-$vo['create_time'])/60);
        $exp = $exp < 10 ? 10 : $exp;
        if($vo['type']=='cash'){

        }else{
            //$vo['eac'] = 1;
            $url = $csetting['eacpay_server']."/checktransaction/".$csetting['recive_token']."/".$vo['eac'].'/'.$vo['order_id'].'/'.$vo['block_height'].'/100';
        }
        //echo $url;
        $ret = cansnow_get($url);
        //P($ret);
        $ret = json_decode($ret,true);
        if($ret['Error']){
            return array("code"=>4,"msg"=>$ret['Error'] == 'Payment not found' ? '等待用户支付' : $ret['Error']);
        }
        
        $data =array(
            'last_time' => time(),
            'pay_time' => time(),
            'status' 	=> 'payed',
            'real_eac'	=>0
        );
        if ($ret['confirmations'] >= $csetting['receiptConfirmation']) {
            foreach($ret['vout'] as $v){
                if($v['scriptPubKey']['addresses'][0] == $csetting['recive_token']){
                    $data['real_eac'] = $v['value'];
                    //检查支付金额是否相符
                    if(round($v['value'],strlen(explode('.',$vo['eac'])[1])) == $vo['eac']){
                        $this->dsql->ExecuteNoneQuery("update #@__eacpay_order set `last_time`='.time().',`pay_time`='.time().',`status`='complete',`real_eac`=".$v['value']." where `order_id`='".$vo['order_id']."'");
                        $order_sn=explode('_',$vo['order_id'])[3];
                        $ret = $this->updateOrder($order_sn);
                        return array("code"=>$ret===true ? 1:0,"msg"=>$ret);
                    }else{
                        $this->dsql->ExecuteNoneQuery("update #@__eacpay_order set `last_time`='.time().',`pay_time`='.time().',`status`='payed',`real_eac`=".$v['value']." where `order_id`='".$vo['order_id']."'");
                        return array("code"=>3,"msg"=>'交易数值不一致，请自行联系站长解决');
                    }
                    break;
                }
            }
        }else{
            return array("code"=>2,"confirmations"=>$ret['confirmations'],"receiptConfirmation"=>$csetting['receiptConfirmation']);
        }
    }
    function updateOrder($order_sn)
    {
        if(preg_match ("/S-P[0-9]+RN[0-9]/",$order_sn)) {
            $row = $this->dsql->GetOne("SELECT * FROM #@__shops_orders WHERE oid = '{$order_sn}'");
            $this->mid = $row['userid'];
            if($this->success_db($order_sn)) {
                return true;//支付成功
            }else{
                return '支付失败';//支付失败
            }
        }else if (preg_match ("/M[0-9]+T[0-9]+RN[0-9]/", $order_sn)){
            $row = $this->dsql->GetOne("SELECT * FROM #@__member_operation WHERE buyid = '{$order_sn}'");
            //获取订单信息，检查订单的有效性
            if(!is_array($row)||$row['sta']==2) return "您的订单已经处理，请不要重复提交!";
            $this->mid  =   $row['mid'];
            $oldinf = $this->success_mem($order_sn,$row['pname'],$row['product'],$row['pid']);
            return true;//支付成功
        } else {    
            return "支付失败，您的订单号有问题！";
        }
        return true;
    }

    /*处理物品交易*/
    function success_db($order_sn)
    {
        //获取订单信息，检查订单的有效性
        $row = $this->dsql->GetOne("SELECT state FROM #@__shops_orders WHERE oid='$order_sn' ");
        if($row['state'] > 0)
        {
            return TRUE;
        }    
        /* 改变订单状态_支付成功 */
        $sql = "UPDATE `#@__shops_orders` SET `state`='1' WHERE `oid`='$order_sn' AND `userid`='".$this->mid."'";
        if($this->dsql->ExecuteNoneQuery($sql))
        {
            $this->log_result("verify_success,订单号:".$order_sn); //将验证结果存入文件
            return TRUE;
        } else {
            $this->log_result ("verify_failed,订单号:".$order_sn);//将验证结果存入文件
            return FALSE;
        }
    }

    /*处理点卡，会员升级*/
    function success_mem($order_sn,$pname,$product,$pid)
    {
        //更新交易状态为已付款
        $sql = "UPDATE `#@__member_operation` SET `sta`='1' WHERE `buyid`='$order_sn' AND `mid`='".$this->mid."'";
        $this->dsql->ExecuteNoneQuery($sql);

        /* 改变点卡订单状态_支付成功 */
        if($product=="card")
        {
            $row = $this->dsql->GetOne("SELECT cardid FROM #@__moneycard_record WHERE ctid='$pid' AND isexp='0' ");;
            //如果找不到某种类型的卡，直接为用户增加金币
            if(!is_array($row))
            {
                $nrow = $this->dsql->GetOne("SELECT num FROM #@__moneycard_type WHERE pname = '{$pname}'");
                $dnum = $nrow['num'];
                $sql1 = "UPDATE `#@__member` SET `money`=money+'{$nrow['num']}' WHERE `mid`='".$this->mid."'";
                $oldinf ="已经充值了".$nrow['num']."金币到您的帐号！";
            } else {
                $cardid = $row['cardid'];
                $sql1=" UPDATE #@__moneycard_record SET uid='".$this->mid."',isexp='1',utime='".time()."' WHERE cardid='$cardid' ";
                $oldinf='您的充值密码是：<font color="green">'.$cardid.'</font>';
            }
            //更新交易状态为已关闭
            $sql2=" UPDATE #@__member_operation SET sta=2,oldinfo='$oldinf' WHERE buyid='$order_sn'";
            if($this->dsql->ExecuteNoneQuery($sql1) && $this->dsql->ExecuteNoneQuery($sql2))
            {
                $this->log_result("verify_success,订单号:".$order_sn); //将验证结果存入文件
                return $oldinf;
            } else {
                $this->log_result ("verify_failed,订单号:".$order_sn);//将验证结果存入文件
                return "支付失败！";
            }
        /* 改变会员订单状态_支付成功 */
        } else if ( $product=="member" ){
            $row = $this->dsql->GetOne("SELECT rank,exptime FROM #@__member_type WHERE aid='$pid' ");
            $rank = $row['rank'];
            $exptime = $row['exptime'];
            /*计算原来升级剩余的天数*/
            $rs = $this->dsql->GetOne("SELECT uptime,exptime FROM #@__member WHERE mid='".$this->mid."'");
            if($rs['uptime']!=0 && $rs['exptime']!=0 ) 
            {
                $nowtime = time();
                $mhasDay = $rs['exptime'] - ceil(($nowtime - $rs['uptime'])/3600/24) + 1;
                $mhasDay=($mhasDay>0)? $mhasDay : 0;
            }
            //获取会员默认级别的金币和积分数
            $memrank = $this->dsql->GetOne("SELECT money,scores FROM #@__arcrank WHERE rank='$rank'");
            //更新会员信息
            $sql1 =  " UPDATE #@__member SET rank='$rank',money=money+'{$memrank['money']}',
                       scores=scores+'{$memrank['scores']}',exptime='$exptime'+'$mhasDay',uptime='".time()."' 
                       WHERE mid='".$this->mid."'";
            //更新交易状态为已关闭
            $sql2=" UPDATE #@__member_operation SET sta='2',oldinfo='会员升级成功!' WHERE buyid='$order_sn' ";
            if($this->dsql->ExecuteNoneQuery($sql1) && $this->dsql->ExecuteNoneQuery($sql2))
            {
                $this->log_result("verify_success,订单号:".$order_sn); //将验证结果存入文件
                return "会员升级成功！";
            } else {
                $this->log_result ("verify_failed,订单号:".$order_sn);//将验证结果存入文件
                return "会员升级失败！";
            }
        }    
    }

    function  log_result($word) 
    {
        global $cfg_cmspath;
        $fp = fopen(dirname(__FILE__)."/../../data/eacpay/log.txt","a");
        flock($fp, LOCK_EX) ;
        fwrite($fp,$word.",执行日期:".strftime("%Y-%m-%d %H:%I:%S",time())."\r\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }


}//End API