<?php
/**
 * @Author: Marte
 * @Date:   2018-09-18 09:30:50
 * @Last Modified by:   Marte
 * @Last Modified time: 2018-09-19 10:51:17
 */
namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\Month;
use app\models\News;
use app\models\UploadForm;
use yii\web\UploadedFile;
class CommonController extends Controller{
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
     * 获取要授权页面的url
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
     * 获取当前用户的基本信息
     */
    function getuerInfo($userOpenId=""){
        $access_token=$this->getAccsenToken();
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$userOpenId."&lang=zh_CN";
        $result = $this->https_request($url);
        // $result = '{"subscribe":1,"openid":"oizv4snYKf42nqAdRrGxdfsLA4AI","nickname":"月下蓝貂","sex":1,"language":"zh_CN","city":"丰台","province":"北京","country":"中国","headimgurl":"http:\/\/wx.qlogo.cn\/mmopen\/wXJ5kSJT6ONAprP5e8Ia2kb33LQR2Picy45nAkmcTvLVCS0k7Hib9aUH6aURflSnbX7kGqguFs6NUL6rtunoDsPdn07rKRNsWia\/0","subscribe_time":1434366011,"remark":"","groupid":0}';
        $ret = json_decode($result,true);
        $session = Yii::$app->session;
        $session->set("userInfo",$ret);
        $session->set("tokenInfo",$ret);
        return $ret;
    }
    /**
     * 获取页面授权用户信息
     * @param $code 回调code 微信返回code
     */
    function memberAuthorization($code=''){
        $APPID=Yii::$app->params['wechat']["APPID"];
        $APPSECRET=Yii::$app->params['wechat']["SECRET"];
        $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code";
        $returl = sprintf($url,$APPID,$APPSECRET,$code);
        $result = $this->https_request($returl);
        $tokenInfo = json_decode($result,true);
        $session = Yii::$app->session;
        if(!empty($tokenInfo['access_token']) && !empty($tokenInfo['openid'])){
            $session->set('tokenInfo',$tokenInfo);
            $openid = $tokenInfo['openid'];
        }else{
            $tokenInfo = $session->get("tokenInfo");
            $oauth2 = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=%s&grant_type=refresh_token&refresh_token=%s";
            $info = $this->https_request(sprintf($oauth2,$APPID,$tokenInfo['refresh_token']));
            $tokenInfo = json_decode($info,true);
            $openid = $tokenInfo['openid'];
        }
        $urls = "https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN";
        $returls = sprintf($urls,$tokenInfo['access_token'],$openid);
        $results = $this->https_request($returls);
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

    /**
     *  * 请求接口
     * @param unknown $url
     * @param string $data
     * @param string $httpheader  设置请求格式 例如 $httpheader = "content-type:text/json;charset=UTF-8";
     * @return mixed
     */
    function https_request($url, $data=null,$httpheader="") {
        $curl = curl_init();//初始化一个curl
        if($httpheader!=""){
            $header[] = $httpheader;//"content-type:text/json;charset=UTF-8";
            curl_setopt($curl,CURLOPT_HTTPHEADER,$header);//设置 HTTP 头字段
        }
        curl_setopt($curl, CURLOPT_URL, $url);//需要获取的url
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//FALSE 禁止 cURL 验证对等证书
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);//检查服务器SSL证书中是否存在一个公用名
        if(!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);//1 时会发送 POST 请求
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);// 全部数据使用HTTP协议中的 "POST" 操作来发送
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//1时以字符串返回
        $output = curl_exec($curl);//执行一个会话
        curl_close($curl);//关闭会话
        return $output;
    }

    /**
     *使用带有证书的CURL请求
     * @param string $url url链接
     * @param unknown $vars
     * @param number $second  秒数
     * @param unknown $aHeader
     * @return mixed|boolean
     */
    function curl_post_ssl($url, $vars, $second=30,$aHeader=array()){
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);//允许url执行最大秒数
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);//获取的信息以字符串返回
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL,$url);//获取url地址
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);//FALSE 禁止 cURL 验证对等证书
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);//检查服务器SSL证书中是否存在一个公用名

        //以下两种方式需选择一种

        //第一种方法，cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');//证书类型
        curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/Public/cret/apiclient_cert.pem');//一个包含 PEM 格式证书的文件名。
        //默认格式为PEM，可以注释
        curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');//私钥的加密
        curl_setopt($ch,CURLOPT_SSLKEY,getcwd().'/Public/cret/apiclient_key.pem');//包含 SSL 私钥的文件名。


        curl_setopt($ch,CURLOPT_CAINFO,getcwd().'/Public/cret/rootca.pem');//一个保存着1个或多个用来让服务端验证的证书的文件名
        //第二种方式，两个文件合成一个.pem文件
        //curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');

        if( count($aHeader) >= 1 ){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);//设置 HTTP 头字段
        }
        //使用带有参数的POST请求
        if(!empty($vars)) {
            curl_setopt($ch, CURLOPT_POST, 1);//TRUE 时会发送 POST 请求
            curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);//全部数据使用HTTP协议中的 "POST" 操作来发送
        }

        $data = curl_exec($ch);//执行会话
        if($data){
            curl_close($ch);//关闭会话
            return $data;
        }else{
            $error = curl_errno($ch);//返回最后一次的错误号
            print_log("错误码：".$error);
            echo "call faild, errorCode:$error";
            curl_close($ch);//关闭会话
            return false;
        }
    }

    /**
     * 文件请求CURL
     * @param string $url
     * @return multitype:
     */
    function http_two_dimension_code($url=''){
        $curl = curl_init($url);//创建会话
        curl_setopt($curl,CURLOPT_HEADER,0);//启用时会将头文件的信息作为数据流输出。
        curl_setopt($curl,CURLOPT_NOBODY,0);//TRUE 时将不输出 BODY 部分。同时 Mehtod 变成了 HEAD。修改为 FALSE 时不会变成 GET。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//FALSE 禁止 cURL 验证对等证书
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);//设置为 1 是检查服务器SSL证书中是否存在一个公用名
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//以字符串返回
        $package = curl_exec($curl);//执行会话
        $httpinfo = curl_getinfo($curl);//获取一个cURL连接资源句柄的信息
        curl_close($curl);//关闭会话
        return  array_merge(array("body"=>$package),array("header"=>$httpinfo));
    }
}
?>