;(function(W, D){
    function removeEvent (el, type, fn) {
        el && el.removeEventListener(type, fn, false);
    }
    function dispatchEvent (el, type){
        var evt = document.createEvent('Event');
        evt.initEvent(type,true,true);
        el && el.dispatchEvent(evt);
    }

    function closeShareTips(callback){
        D.querySelector('body').removeChild(D.querySelector('.shareTips'))
        if(callback){
            callback();    
        }
    }
    function showShareFriend(callback){
        var div = D.createElement('div');
        div.className = 'shareTips';
        div.innerHTML = '<div class="tips_friend"></div><a class="close" href="javascript:;"></a>';
        div.addEventListener("click", function(){
            closeShareTips(callback);   
        });
        D.querySelector('body').appendChild(div);
    }

    function initWxShare(shareData){
        //分享
        wx.onMenuShareTimeline({
            title: shareData.desc, // 分享标题
            link: shareData.link, // 分享链接
            imgUrl: shareData.imgUrl, // 分享图标
            success: function () {
                shareData.success && shareData.success();
            },
            cancel: function () {}
        });
        wx.onMenuShareAppMessage({
            title: shareData.title,
            desc: shareData.desc,
            link: shareData.link,
            imgUrl: shareData.imgUrl,
            type: shareData.type, // 分享类型,music、video或link，不填默认为link
            dataUrl: shareData.dataUrl, // 如果type是music或video，则要提供数据链接，默认为空
            success: function () { 
                shareData.success && shareData.success();
            },
            cancel: function () { }
        });
        wx.onMenuShareQQ({
            title: shareData.title,
            desc: shareData.desc,
            link: shareData.link,
            imgUrl: shareData.imgurl,
            success: function () { 
               shareData.success && shareData.success();
            },
            cancel: function () {}
        });
    }
    function back(){
        var hisLen = window.history.length;
        if(hisLen == 1){
            wx.closeWindow();
        }else{
            window.history.back();
        }
    }

    function moneyFormat(value){
        var float = parseFloat(value);
        float = Math.ceil(float*100);
        float = float/100;
        if(Number(float) === float && float % 1 === 0){
            float = float+".00";
        }
        return float;
    }
    var LIB = {
        showShareFriend:showShareFriend,//分享给朋友
        initWxShare:initWxShare,
        back:back,
        moneyFormat:moneyFormat,
        
        removeEvent: removeEvent,
        dispatchEvent: dispatchEvent,
        loadingToastShow: function() {
            D.querySelector('#loadingToast').style.display = 'block';
        },
        loadingToastHide: function(){
            D.querySelector('#loadingToast').style.display = 'none';
        }
    };

    ['Boolean', 'Number', 'String', 'Function', 'Array', 'Date', 'RegExp', 'Object', 'NodeList'].map(function(name) {
      LIB['is' + name] = function(o) {
        return Object.prototype.toString.call(o) === '[object ' + name + ']';
      };
    });

    LIB.addEvent = function (el, type, fn) {
        if(LIB.isNodeList(el)){
            for (i = 0; i < el.length; i++) { 
                LIB.addEvent(el[i], type, fn);
            }
            return;
        }
        el && el.addEventListener(type, fn, false);   
    },

    W.WeiPHP = LIB;

    wx.config({
        debug: false,
        appId: WX_APPID, // 必填，公众号的唯一标识
        timestamp: WXJS_TIMESTAMP, // 必填，生成签名的时间戳
        nonceStr: NONCESTR, // 必填，生成签名的随机串
        signature: SIGNATURE,// 必填，签名，见附录1
        jsApiList: [
            'checkJsApi',
            'onMenuShareTimeline',
            'onMenuShareAppMessage',
            'onMenuShareQQ',
            'onMenuShareWeibo',
            'hideMenuItems',
            'showMenuItems',
            'hideAllNonBaseMenuItem',
            'showAllNonBaseMenuItem',
            'translateVoice',
            'startRecord',
            'stopRecord',
            'onRecordEnd',
            'playVoice',
            'pauseVoice',
            'stopVoice',
            // 'uploadVoice',
            // 'downloadVoice',
            'chooseImage',
            'previewImage',
            'uploadImage',
            'downloadImage',
            // 'getNetworkType',
            // 'openLocation',
            // 'getLocation',
            'hideOptionMenu',
            'showOptionMenu',
            'closeWindow',
            'scanQRCode',
            'chooseWXPay',
            'openProductSpecificView',
            'addCard',
            'chooseCard',
            'openCard'
        ]
    });

    wx.ready(function(){
        wx.hideMenuItems({
            menuList: [
                'menuItem:readMode',
                'menuItem:openWithQQBrowser',
                'menuItem:openWithSafari',
                'menuItem:share:email',
                'menuItem:setFont',
                'menuItem:copyUrl',
                'menuItem:favorite'
            ] 
        });
    });
})(window, document);
