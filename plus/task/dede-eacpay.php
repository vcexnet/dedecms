<?php
require_once(dirname(__FILE__).'/../../include/common.inc.php');
require_once DEDEINC.'/payment/eacpay.php';
$time = time()-60*5;
$maxtime = time()-7200;
$dsql->ExecuteNoneQuery("update #@__eacpay_order set status='cancel' where status='wait' and type='recharge' and create_time<".$maxtime);
$dsql->Execute('nn',"select * from #@__eacpay_order where status='wait' and type='recharge' and last_time<".$time);
$pay = new eacpay();
while($vo = $dsql->GetArray('nn')){
    $ret = $pay->checkOrder($vo);
}
?>