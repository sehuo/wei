<?php

namespace Addons\Signpay\Model;
use Think\Model;

class SignpayOrderModel extends Model{
    function getInfo($id, $update = false, $data = array()) {
        $key = 'PaymentOrder_getInfo_' . $id;
        $info = S ( $key );
        if ($info === false || $update) {
            $info = ( array ) (count ( $data )==0 ? $this->find ( $id ) : $data);
            S ( $key, $info );
        }
        return $info;
    }
}
