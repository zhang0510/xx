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
    public $enableCsrfValidation = false;
    //入口
    public function actionIndex(){
        $request = Yii::$app->request;
        $echoStr = $request->get("echostr");
        $signature = $request->get("signature");
        $timestamp = $request->get("timestamp");
        $nonce = $request->get("nonce");
        if ($echoStr != ''){
            if($this->checkSignature($signature,$timestamp,$nonce)){
                echo $echoStr;
                exit;
            }
        }else {
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
            $RX_TYPE =  trim($postObj->MsgType);
            //消息类型分离, 通过RX_TYPE类型作为判断， 每个方法都需要将对象$postObj传入
            switch ($RX_TYPE){
                case "text":
                    $result = $this->receiveText($postObj);     //接收文本消息
                    break;
                case "image":
                    $result = $this->receiveImage($postObj);   //接收图片消息
                    break;
                case "location":
                    $result = $this->receiveLocation($postObj);  //接收位置消息
                    break;
                case "voice":
                    $result = $this->receiveVoice($postObj);   //接收语音消息
                    break;
                case "video":
                    $result = $this->receiveVideo($postObj);  //接收视频消息
                    break;
                case "link":
                    $result = $this->receiveLink($postObj);  //接收链接消息
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
     * 接受文本消息
     */
    public function receiveText($postObj)
    {
        if(strstr($postObj->Content,"你是") || strstr($postObj->Content,"你叫")){
            $content = '我是和你学';
        }elseif(strstr($postObj->Content,"笑话")){
            $content = '神父：“你愿意娶你旁边这位女士为妻吗？”
男：“不，我不愿意。”
神父：“哦，那你愿意嫁给你旁边的男士吗？”
女：“不愿意。”
神父：“好！负负得正！我宣布两人正式成为夫妻！！”';
        }else{
            $content = $postObj->Content;
        }
        return $this->transmitText($postObj, $content);
    }

    /*
     * 接收图片消息
     */
    public function receiveImage($postObj)
    {
        return $this->transmitImage($postObj, $postObj->MediaId);
    }

    /*
     * 接收语音消息
     */
    private function receiveVoice($postObj)
    {
        $result = $this->transmitVoice($postObj, $postObj->MediaId);
        return $result;
    }

    /*
     * 接收视频消息
     */
    private function receiveVideo($postObj)
    {
        $result = $this->transmitVideo($postObj, $postObj->MediaId);
        return $result;
    }

    /*
     * 接收位置消息
     */
    private function receiveLocation($postObj)
    {
        $content = "你发送的是位置，纬度为：".$postObj->Location_X."；经度为：".$postObj->Location_Y."；缩放级别为：".$postObj->Scale."；位置为：".$postObj->Label;
        $result = $this->transmitText($postObj, $content);
        return $result;
    }

    /*
     * 接收链接消息
     */
    private function receiveLink($postObj)
    {
        $content = "你发送的是链接，标题为：".$postObj->Title."；内容为：".$postObj->Description."；链接地址为：".$postObj->Url;
        $result = $this->transmitText($postObj, $content);
        return $result;
    }


    /**
     * 回复文本消息
     */
    private function transmitText($object, $content)
    {
        $xmlTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime><![CDATA[%s]]></CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                    </xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

    //回复图片消息
    public function transmitImage($object,$mediaid){
        $xmlTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime><![CDATA[%s]]></CreateTime>
                        <MsgType><![CDATA[image]]></MsgType>
                        <Image><MediaId><![CDATA[%s]]></MediaId></Image>
                    </xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $mediaid);
        return $result;
    }

    //回复语音消息
    public function transmitVoice($object,$mediaid){
        $xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[voice]]></MsgType>
                        <Voice><MediaId><![CDATA[%s]]></MediaId></Voice>
                    </xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $mediaid);
        return $result;
    }

    //回复视频消息
    public function transmitVideo($object,$mediaid){
        $xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[video]]></MsgType>
                        <Video><MediaId><![CDATA[%s]]></MediaId></Video>
                    </xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $mediaid);
        return $result;
    }

    /**
     * 设置菜单
     */
    function actionSetmenu(){
        //百度
        $bd = "http://www.baidu.com";
        //小米
        $xm = "http://www.mi.com";
        //阿里
        $al = "http://www.aliyun.com";
        //姓名
        $name = $this->getAuthUrl('/month/getname');
        //性别
        $sex = $this->getAuthUrl('/month/getsex');
        //行业
        $industry = $this->getAuthUrl('/month/getarea');
        $jsonmenu = '
        {
		    "button": [
                {
                    "type": "view", 
                    "name": "查价", 
                    "url": "http://xx.tuotuoyunche.com/month/select_price"
                }, 
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
     * 获取要授权页面的url(菜单路径生成)
     * $str格式'/模块名/控制器名/方法名';
     * @param unknown $str
     * @return string
     */
    function getAuthUrl($str,$state = "STATE"){
        $domain = Yii::$app->params['wechat']['REQUEST_PATH'];
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
    private function getEventReturn($object){
        /**
         * 微信点击客服事件,关注/取消,触发
         */
        switch ($object->Event){
            //关注公众号事件
            case "subscribe":
                $content = "欢迎关注安达报价系统";
                break;
            default:
                $content = "";
                break;
        }
        $result = $this->transmitText($object, $content);
        return $result;
    }

}
