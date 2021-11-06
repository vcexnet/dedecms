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
$dsql->safeCheck = false;
$sqls=array(
    'DROP TABLE IF EXISTS `#@__eacpay_address`;',
    'DROP TABLE IF EXISTS `#@__eacpay_order`;',
    'Delete From `#@__payment` where `code`="eacpay";',
    'Delete From `#@__sys_task` where `dourl`="dede_eacpay.php";'
);
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
ShowMsg('卸载成功', "module_main.php");

exit;