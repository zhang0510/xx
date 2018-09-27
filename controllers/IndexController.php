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
		                    "name": "地址", 
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

}
