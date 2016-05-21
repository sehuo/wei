<?php

namespace Addons\Pages\Controller;

use Addons\Pages\Controller\BaseController;

class PagesController extends BaseController {
    function config() {
        // 使用提示
        $publicid = get_token_appinfo('','id');
        
        if (IS_POST) {
            $flag = D ( 'Common/AddonConfig' )->set ( _ADDONS, $_POST ['config'] );
            
            if ($flag !== false) {
                $this->success ( '保存成功', Cookie ( '__forward__' ) );
            } else {
                $this->error ( '保存失败' );
            }
            exit ();
        }
        
        parent::config ();
    }
    // 详情
    function detail() {
        $map ['id'] = I ( 'get.id', 0, 'intval' );
        $info = M ( 'custom_reply_news' )->where ( $map )->find ();
        $this->assign ( 'page_title', $info['title'] );
        $this->assign ( 'info', $info );

        M( 'custom_reply_news' )->where ( $map )->setInc ( 'view_count' );

        $this->display ( ONETHINK_ADDON_PATH . 'Pages/View/default/detail/detail.html' );
    }

    function _getNewsUrl($info) {
        $param ['token'] = get_token ();
        $param ['openid'] = get_openid ();
        
        if (! empty ( $info ['jump_url'] )) {
            $url = replace_url ( $info ['jump_url'] );
        } else {
            $param ['id'] = $info ['id'];
            $url = U ( 'detail', $param );
        }
        return $url;
    }
}
