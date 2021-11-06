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
if(!$dsql){
    require_once(dirname(__FILE__)."/../../include/common.inc.php");
    require_once(dirname(__FILE__)."/base.php");
}
$dsql->safeCheck = false;
$config = array ( 
    'allow_cash' => array ( 
        'title' => '允许提现', 
        'description' => '', 
        'type' => 'select', 
        'value' => '1', 
        'iterm' => '1:允许,0:关闭',
    ), 
    'moneybl' => array ( 
        'title' => '提现比例', 
        'description' => '例子:填1表示1RMB=1金币,填10表示1RMB=10金币', 
        'type' => 'text', 
        'value' => '1', 
    ) ,
    'recive_token' => array ( 
        'title' => '收款地址', 
        'description' => '', 
        'type' => 'text', 
        'value' => 'eZcwRzRDPiPvM6WUGQXMRLa5MAHkrwWP9t', 
    ),
    'receiptConfirmation' => array ( 
        'title' => '确认数量', 
        'description' => '区块链中有多少数量确认才算交易成功,默认3', 
        'type' => 'text', 
        'value' => '3', 
    ),
    'exhangeapi' => array ( 
        'title' => 'EAC定价基准交易所', 
        'description' => '', 
        'type' => 'text', 
        'value' => 'https://api.aex.zone/v3/depth.php'
    ), 
    'eacpay_server' => array ( 
        'title' => 'Earthcoin区块链浏览器', 
        'description' => '', 
        'type' => 'text', 
        'value' => 'https://blocks.deveac.com:4000', 
    ), 
    'bizhong' => array (
        'title' => '定价基准币种',
        'description' => '请选择您最后一次跟支付宝签订的协议里面说明的接口类型',
        'type' => 'select',
        'iterm' => 'RMB:人民币,USD:美元,EUR:欧元',
        'value' => 'RMB', 
    ),
    'receiptConfirmation' => array ( 
        'title' => '通知提示', 
        'description' => '', 
        'type' => 'text', 
        'value' => '请不要修改付款页面的任何信息，否则系统无法识别订单将导致不会自动发货', 
    ) 
);
if($cfg_soft_lang == 'utf-8')
{
    $config = AutoCharset($config,'utf-8','gb2312');
    $config = serialize($config);
    $config = gb2utf8($config);
}else{
    $config = serialize($config);
}
$config = str_replace('"','\"',$config);
$sqls = array(
  "DROP TABLE IF EXISTS `#@__eacpay_address`;",
  "CREATE TABLE `#@__eacpay_address` (`uid` mediumint(11) NOT NULL,`address` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',INDEX `plid`(`address`) USING BTREE) ENGINE = MyISAM CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;",
  "DROP TABLE IF EXISTS `#@__eacpay_order`;",
  "CREATE TABLE `#@__eacpay_order`  (`uid` mediumint(11) NOT NULL,`order_id` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0',`amount` float(10, 4) NULL DEFAULT 0.0000,`eac` float(10, 4) NULL DEFAULT 0.0000,`real_eac` float(10, 4) NULL DEFAULT 0.0000,`address` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,`block_height` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,`create_time` int(11) NULL DEFAULT 0,`pay_time` int(11) NULL DEFAULT 0,`last_time` int(11) NULL DEFAULT 0,`status` enum('reject','wait','complete','payed','cancel') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,`type` enum('recharge','cash') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,PRIMARY KEY (`order_id`) USING BTREE,INDEX `plid`(`order_id`) USING BTREE
  ) ENGINE = MyISAM CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;",
  "Delete From `#@__payment` where `code`='eacpay';",
  "INSERT INTO `#@__payment` (`code`, `name`, `fee`, `description`, `rank`, `config`, `enabled`, `cod`, `online`) VALUES ('eacpay', 'eacpay地球币支付', '0', 'eacpay地球币支付(www.eacpay.com)', '5', '".$config."', '1', '0', '1' );"
);

//$sqls=array();
/*for ($h=0; $h < 24; $h++) { 
    for ($i=0; $i < 60; $i+=5) { 
      array_push($sqls,'INSERT INTO `#@__sys_task` (`taskname`, `dourl`, `islock`, `runtype`, `runtime`, `starttime`, `endtime`, `freq`, `lastrun`, `description`, `parameter`, `settime`, `sta`) VALUES ("eacpay", "dede_eacpay.php", 0, 0, "'.$h.':'.$i.'", 0, 0, 1, 0, "eacpay订单监控", "", 1635524074, NULL);');
    }
}*/
$isok = true;
foreach($sqls as $sql){
    $isok = $dsql->ExecuteNoneQuery($sql);
    if(!$isok)
    {
        ShowMsg("数据库出错，请重新尝试！".$dsql->GetError(), "module_main.php");
        exit;
    }
}
$dsql->safeCheck = true;
ShowMsg('安装成功', "module_main.php");
exit;