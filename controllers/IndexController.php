<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class IndexController extends CommonController
{
    //入口
    public function actionIndex(){
        $request = Yii::$app->request;
        $echoStr = $request->get("echostr");
        $signature = $request->get("signature");
        $timestamp = $request->get("timestamp");
        $nonce = $request->get("nonce");
        if($this->checkSignature($signature,$timestamp,$nonce)){
            echo $echoStr;
            exit;
        }else{
            $this->responseMsg();
        }
    }

    /**
     * 接入验证签名
     * @param string $signature
     * @param string $timestamp
     * @param string $nonce
     * @return boolean
     */
    private function checkSignature($signature="",$timestamp="",$nonce=""){
        $token = Yii::$app->params['wechat']['WXTOKEN'];
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 接收相应数据
     */
    function responseMsg(){
        $postStr =  file_get_contents("php://input");
        if (!empty($postStr)){
            //将接收的消息处理返回一个对象
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            //从消息对象中获取消息的类型 text  image location voice vodeo link
            $RX_TYPE =  strtolower(trim($postObj->MsgType));
            //消息类型分离, 通过RX_TYPE类型作为判断， 每个方法都需要将对象$postObj传入
            switch ($RX_TYPE){
                case "text":
                    //$result =  self::getText($postObj);
                    //$result = $this->receiveText($postObj);     //接收文本消息
                    break;
                case "image":
                    //$result = $this->receiveImage($postObj);   //接收图片消息
                    break;
                case "location":
                    //$this->receiveLocation($postObj);  //接收位置消息
                    break;
                case "voice":
                    //$result = $this->receiveVoice($postObj);   //接收语音消息
                    break;
                case "video":
                    //$result = $this->receiveVideo($postObj);  //接收视频消息
                    break;
                case "link":
                    //$result = $this->receiveLink($postObj);  //接收链接消息
                    break;
                case "event":
                    $result =  $this->getEventReturn($postObj);//关注/取消
                    break;
                default:
                    $result = 0;//"unknown msg type: ".$RX_TYPE;   //未知的消息类型
                    break;
            }
            //输出消息给微新
            echo $result;
        }else {
            //如果没有消息则输出空，并退出
            echo "";
            exit;
        }
    }

    /*
     * 接收文本类型
     */
    public function getText($postObj){
        $wxair = A('Airlines');
        $result = $wxair->wx_sent($postObj);
        return null;
    }

    /**
     * 设置菜单
     */
    function actionSetmenu(){
        //百度
        $bd = "http://www.baidu.com/";
        //小米
        $xm = "http://www.mi.com/";
        //阿里
        $al = "http://www.aliyun.com/";
        //姓名
        $name = $this->getAuthUrl('/Month/getname');
        //性别
        $sex = $this->getAuthUrl('/Month/getsex');
        //行业
        $industry = $this->getAuthUrl('/Month/getindustry');
        $jsonmenu = '
        {
		    "button": [
		        {
		            "name": "个人简介", 
		            "sub_button": [
		                {
		                    "type": "view", 
		                    "name": "姓名", 
		                    "url":"'.$name.'"
		                }, 
		                {
		                    "type": "view", 
		                    "name": "性别", 
		                    "url":"'.$sex.'"
		                }, 
		                {
		                    "type": "view", 
		                    "name": "行业", 
		                    "url":"'.$industry.'"
		                }
		            ]
		        }, 
		        {
		            "name": "友情链接", 
		            "sub_button": [
		                {
		                    "type": "view", 
		                    "name": "百度", 
		                    "url":"'.$bd.'"
		                }, 
		                {
		                    "type": "view", 
		                    "name": "小米", 
		                    "url":"'.$xm.'"
		                }, 
		                {
		                    "type": "view", 
		                    "name": "阿里", 
		                    "url":"'.$al.'"
		                }
		            ]
		        }
		    ]
		}';
        $access_token = $this->getAccsenToken();
        $menuurl = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $result = $this->https_request($menuurl, $jsonmenu);
        $retArr = json_decode($result,true);
        if($retArr['errcode']==0){
            echo "菜单生成成功";
        }else{
            echo "菜单生成失败:".$retArr['errmsg'];
        }
    }

    /**
     * 获取要授权页面的url
     * $str格式'/模块名/控制器名/方法名';
     * @param unknown $str
     * @return string
     */
    function getAuthUrl($str,$state = "STATE"){
        $domain = Yii::$app->request->hostInfo;
        $url = $domain.$str;
        $encodeUrl = urlencode($url);
        $appid = Yii::$app->params['wechat']["APPID"];
        $httpurl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_userinfo&state='.$state.'#wechat_redirect';
        $outputUrl = sprintf($httpurl,$appid,$encodeUrl);
        return $outputUrl;
    }
    /**
     * 菜单事件回复
     * @param string $eventType
     * @param string $mark
     */
    function getEventReturn($object=null){
        /**
         * 微信点击客服事件,关注/取消,触发
         */
        $subscribe = $object->Event;
        $CLICK = $object->EventKey;
        $result="";
        if($subscribe=="subscribe"){//关注后发出信息
            //获取用户OpeniD
            $content = C("CONTENT");
            $xmlTpl = <<<xml
	             <xml>
	             <ToUserName><![CDATA[%s]]></ToUserName>
	             <FromUserName><![CDATA[%s]]></FromUserName>
	             <CreateTime>%s</CreateTime>
	             <MsgType><![CDATA[text]]></MsgType>
	             <Content><![CDATA[%s]]></Content>
	             </xml>
xml;
            $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        }else if($CLICK=="KEFU"){
            //获取用户OpeniD
            $content = C("KE_FU");
            $xmlTpl = <<<xml
	             <xml>
	             <ToUserName><![CDATA[%s]]></ToUserName>
	             <FromUserName><![CDATA[%s]]></FromUserName>
	             <CreateTime>%s</CreateTime>
	             <MsgType><![CDATA[text]]></MsgType>
	             <Content><![CDATA[%s]]></Content>
				 <MsgId>1234567890123456</MsgId>
	             </xml>
	             
xml;
            $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        }
        return $result;
    }

    /**
     * 获取当前用户的基本信息
     */
    function getuerInfo($userOpenId=""){
        $access_token=$this->getAccsenToken();
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$userOpenId."&lang=zh_CN";
        $result = https_request($url);
        // $result = '{"subscribe":1,"openid":"oizv4snYKf42nqAdRrGxdfsLA4AI","nickname":"月下蓝貂","sex":1,"language":"zh_CN","city":"丰台","province":"北京","country":"中国","headimgurl":"http:\/\/wx.qlogo.cn\/mmopen\/wXJ5kSJT6ONAprP5e8Ia2kb33LQR2Picy45nAkmcTvLVCS0k7Hib9aUH6aURflSnbX7kGqguFs6NUL6rtunoDsPdn07rKRNsWia\/0","subscribe_time":1434366011,"remark":"","groupid":0}';
        print_log("获取当前用户的基本信息:".$result);
        $ret = json_decode($result,true);
        session("userInfo",$ret);
        session("tokenInfo",$ret);
        return $ret;
    }
    /**
     * 获取页面授权用户信息
     * @param $code 回调code 微信返回code
     */
    function memberAuthorization($code=''){
        print_log("获取的回调code:".$code);
        $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code";
        $returl = sprintf($url,C("APPID"),C('SECRET'),$code);
        $result = https_request($returl);
        $tokenInfo = json_decode($result,true);
        print_log("------------------------------returl获取信息:".$result);
        if($tokenInfo['openid'] !=""){
            session("tokenInfo",$tokenInfo);
            $openid = $tokenInfo['openid'];
        }else{
            $tokenInfo = session("tokenInfo");
            $oauth2 = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=%s&grant_type=refresh_token&refresh_token=%s";
            $info = https_request(sprintf($oauth2,C("APPID"),$tokenInfo['refresh_token']));
            $tokenInfo = json_decode($info,true);
            $openid = $tokenInfo['openid'];
        }
        print_log("获取当前用户openid：".$openid);
        $urls = "https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN";
        $returls = sprintf($urls,$tokenInfo['access_token'],$openid);
        $results = https_request($returls);
        print_log("拉取当前用户信息：".$results);
        $tokenInfos = json_decode($results,true);
        //$userInfo = $this->getuerInfo($openid);//获取用户信息
        return $tokenInfos;
    }
    /**
     * 获取微信accsen_token
     */
    function getAccsenToken(){
        //获取微信公众号信息
        $APPID=Yii::$app->params['wechat']["APPID"];
        $APPSECRET=Yii::$app->params['wechat']["SECRET"];
        $session = Yii::$app->session;
        $access = $session->get('accesstoken');
        $times = $access['time'];
        $time = time();
        if($time - $times > 7000){
            $TOKEN_URL="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$APPID."&secret=".$APPSECRET;
            $json = $this->https_request($TOKEN_URL);
            $result=json_decode($json,true);
            $data['access_token'] = $result['access_token'];
            $data['time'] = $time;
            $session->set('accesstoken' , $data);
            $access_token = $result['access_token'];
        }else{
            $access_token = $access['access_token'];
        }
        return $access_token;
    }
}
