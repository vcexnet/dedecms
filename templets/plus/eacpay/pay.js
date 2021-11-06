$(function() {
    var timeId = null;
    orderid = null;

    function check() {
        jQuery.getJSON('/plus/eacpay/home.php?dopost=check&orderid=' + orderid, function(d) {
            if (d.code == "1") {
                clearInterval(timeId);
                $('#eacpayresult .resultmsg').html("充值成功");
                setTimeout(function() {
                    location.href = "/plus/eacpay/home.php?dopost=success";
                }, 2000);
            }else if (d.code == "2") {
                jQuery('#eacpayresult .loading .bar').css('width',(d.confirmations/d.receiptConfirmation)+'%');
                jQuery('#eacpayresult .resultmsg').html('正在确认订单，请稍等...');
            }else if (d.code == "3") {
                jQuery('#eacpayresult .loading .bar').css('width','100%');
                jQuery('#eacpayresult .resultmsg').html(d.msg);
            }else if (d.code == "4") {
                jQuery('#eacpayresult .loading .bar').css('width','0%');
                jQuery('#eacpayresult .resultmsg').html(d.msg);
            } else {
                clearInterval(timeId);
                jQuery('#eacpayresult .resultmsg').html(d.msg);
            }
        });
    }
    $('#ajaxgetresult').on('click', function() {
        orderid = $(this).data('orderid');
        $(this).hide();
        $('#eacpayresult').show();
        timeId = setInterval(check, 3000); //開始任務
    })
    $('.eacpay_remark').parents('table').css('position','relative')
})