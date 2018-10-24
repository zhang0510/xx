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
class ApiController extends CommonController{
    public $enableCsrfValidation = false;
    //新闻查询接口
    public function actionNews(){
        @$arr=$_REQUEST;
        @$selects=$arr['selects']!=""?$arr['selects']:'area_id,area_pid,area_name';
        $sql="SELECT $selects FROM `area` where area_pid = 1";
        // $sql="SELECT $selects FROM `news_content`";
        $res=Yii::$app->db->createCommand($sql)->queryAll();
        if ($res) {
            $this->json("200","成功",$res);
        }
    }
    public function actionNews_id(){
        @$arr=$_REQUEST;
        @$id = $arr['id']!=""?$arr['id']:"";
        @$selects=$arr['selects']!=""?$arr['selects']:'n_id,t_id,n_title,n_author,n_desc,n_sort,n_content,n_img,n_status,n_view,n_like,n_time';
        @$limit=$arr['limit']!=""?$arr['limit']:3;
        $sql="SELECT $selects FROM `news_content` where t_id='$id' limit $limit";
        // $sql="SELECT $selects FROM `news_content`";
        $res=Yii::$app->db->createCommand($sql)->queryAll();
        if ($res) {
            $this->json("200","成功",$res);
        }
    }

    //新闻删除接口
    public function actionNewsdel(){
        @$arr = $_REQUEST;
        @$n_id = $arr['n_id']!=""?$arr['n_id']:"";
        if($n_id == ""){
            $this -> json("404","失败","没有找到id");
        }else{
            $res = Yii::$app->db->createCommand("delete from news_content where n_id = '$n_id'")->execute();
            if($res){
                $this ->json("200","成功","");
            }else{
                $this -> json("403","失败","此id不存在");
            }
        }
    }
    //新闻添加接口 url字段要与数据库字段相同
    public function actionNewsadd(){
        @$arr = $_REQUEST;
        unset($arr['r']);
        @$n_title = $arr['n_title']!=""?$arr['n_title']:"";
        if($n_title == ""){
            $this -> json("408","失败","标题不能为空");
        }else{
            $res = Yii::$app->db->createCommand()->insert('news_content',$arr)->execute();
            if($res){
                $this -> json("200","成功","添加成功");
            }else{
                $this -> json("406","失败","添加失败");
            }
        }

    }
    //新闻修改接口
    public function actionNewsupdate(){
        @$arr = $_REQUEST;
        @$n_id = $arr['n_id']!=""?$arr['n_id']:"";
        if($n_id == ""){
            $this -> json("404","失败","没有参数id");
        }else{
            $res = Yii::$app->db->createCommand("select * from news_content where n_id = '$n_id'")->queryOne();
            if($res){
                $this -> json("200","成功",$res);
            }else{
                $this -> json("404","失败","没有这条数据");
            }
        }
    }
    public function actionNewsupd(){
        @$arr = $_REQUEST;
        unset($arr['r']);
        $n_id = $arr['n_id']!=""?$arr['n_id']:"";
        if($n_id == ""){
            $this -> json("401","失败","没有找到id");
        }else{
            unset($arr['n_id']);
            $res = Yii::$app->db->createCommand()->update('news_content',$arr,"n_id = $n_id")->execute();
            if($res){
                $this -> json("200","成功","修改成功");
            }else{
                $this -> json("402","失败","修改失败");
            }
        }
    }
    //新闻分类添加接口 分类字段必填项
    public function actionNewsfadd(){
        @$arr = $_REQUEST;
        unset($arr['r']);
        @$classify = $arr['classify']!=""?$arr['classify']:"";
        if($classify == ""){
            $this -> json("404","失败","该字段为必填项不能为空");
        }else{
            $res = Yii::$app->db->createCommand()->insert('news_classify',$arr)->execute();
            if($res){
                $this -> json("200","成功","添加成功");
            }else{
                $this -> json("403","失败","添加失败");
            }
        }
    }
    //新闻分类查询接口
    public function actionNewsfquery(){
        @$arr = $_REQUEST;
        @$classify = $arr['classify']!=""?$arr['classify']:'t_id,classify,sort,status,time';
        @$limit = $arr['limit']!=""?$arr['limit']:5;
        $res = Yii::$app->db->createCommand("select $classify from news_classify limit $limit")->queryAll();
        if($res){
            $this -> json("200","成功",$res);
        }

    }
    //新闻删除接口 id不能为空
    public function actionNewsfdel(){
        @$arr = $_REQUEST;
        @$id = $arr['id']!=""?$arr['id']:"";
        if($id == ""){
            $this -> json("406","失败","没有找到id");
        }else{
            $res = Yii::$app->db->createCommand("delete from news_classify where id = '$id'")->execute();
            if($res){
                $this -> json("200","成功","删除成功");
            }else{
                $this -> json("407","失败","删除失败");
            }
        }
    }
    //新闻修改接口 id不能为空
    public function actionNewsfupdate(){
        @$arr = $_REQUEST;
        @$id = $arr['id']!=""?$arr['id']:"";
        if($id == ""){
            $this -> json("408","失败","没有找到这条数据");
        }else{
            $res = Yii::$app->db->createCommand("select * from news_classify where id = '$id'")->queryAll();
            if($res){
                $this -> json("200","成功",$res);
            }else{
                $this -> json("408","失败","没有找到这条数据");
            }
        }
    }
    public function actionNewsfupd(){
        @$arr = $_REQUEST;
        // print_r($arr);die;
        @$id = $arr['id']!=""?$arr['id']:"";
        if($id == ""){
            $this -> json("409","失败","你要修改的数据不存在");
        }else{
            unset($arr['r']);
            unset($arr['id']);
            $res = Yii::$app->db->createCommand()->update('news_classify',$arr,"id = $id")->execute();
            if($res){
                $this -> json("200","成功","修改成功");
            }else{
                $this -> json("400","失败","修改失败");
            }
        }
    }
    //对称加密
    public function actionYan(){
        @$arr=$_REQUEST;
        $session = Yii::$app->session;
        $s=$session->get('s');
        @$access_token = $arr['access_token']!=""?$arr['access_token']:"";
        if($access_token == $s['access_token']){
            if($s['time']+20 < time()){
                $this -> json("307","失败","token已过期");
            }else{
                echo "11";
            }
        }else{
            $this -> json("308","失败","token验证失败");
        }
    }
    public function actionUrl(){
        @$arr = $_REQUEST;
        @$appid = $arr['appid']!=""?$arr['appid']:"";
        @$secret = $arr['secret']!=""?$arr['secret']:"";
        $time = time();
        if($appid == ""){
            $this -> json("300","失败","APPID不能为空");
        }else{
            if($secret == ""){
                $this -> json("301","失败","secret不能为空");
            }else{
                $token = md5(sha1($appid.$secret).$time);
                $this -> token($time,$appid,$secret,$token);
            }
        }
    }
    function token($time,$appid,$secret,$token){
        $session = Yii::$app->session;
        $access_token = md5(sha1($appid.$secret).$time);
        if($access_token == $token){
            $s=array('access_token'=>$access_token,'time'=>$time);
            $session->set('s', $s);
            $this -> json("200","成功",array('access_token'=>$access_token));
        }else{
            $this -> json("302","失败","获取token失败");
        }
    }
    public function actionDeta(){
        @$arr = $_REQUEST;
        @$n_desc = $arr['n_desc']!=""?$arr['n_desc']:"n_id,t_id,n_title,n_author,n_desc,n_sort,n_content,n_img,n_status,n_view,n_like,n_time";
        @$limit = $arr['limit']!=""?$arr['limit']:3;
        $res = Yii::$app->db->createCommand("select $n_desc from news_content limit $limit")->queryAll();
        if($res){
            $this -> json("200","成功",$res);
        }else{
            $this -> json("401","失败","查询失败");
        }
    }
    public function actionDeta_two(){
        @$arr = $_REQUEST;
        @$n_desc = $arr['n_desc']!=""?$arr['n_desc']:"n_id,t_id,n_title,n_author,n_desc,n_sort,n_content,n_img,n_status,n_view,n_like,n_time";

        @$id=$arr['id']!=""?$arr['id']:"";
        $res = Yii::$app->db->createCommand("select $n_desc from news_content where n_id='$id'")->queryOne();
        if($res){
            $this -> json("200","成功",$res);
        }else{
            $this -> json("401","失败","查询失败");
        }
    }
    public function actionRegister(){
        $arr = $_REQUEST;
        unset($arr['r']);
        $u_name = $arr['u_name']!=""?$arr['u_name']:"";
        $pwd = $arr['pwd']!=""?$arr['pwd']:"";
        if($u_name == ""){
            $this -> json("401","失败","用户名为必填项不能为空");
        }else{
            $res = Yii::$app->db->createCommand("select * from user where u_name = '$u_name'")->queryAll();
            if($res){
                $this -> json("407","失败","此用户已存在");
            }else{
                if($pwd == ""){
                    $this -> json("402","失败","用户密码为必填项不能为空");
                }else{
                    $res = Yii::$app->db->createCommand()->insert('user',$arr)->execute();
                    if($res){
                        $this -> json("200","成功","注册成功");
                    }else{
                        $this -> json("403","失败","注册失败");
                    }
                }
            }
        }
    }
    public function actionLogin(){
        $arr = $_REQUEST;
        $u_name = $arr['u_name']!=""?$arr['u_name']:"";
        $pwd = $arr['pwd']!=""?$arr['pwd']:"";
        if($u_name == ""){
            $this ->json("404","失败","用户名不能为空");
        }else{
            if($pwd == ""){
                $this -> json("405","失败","用户密码不能为空");
            }else{
                $res = Yii::$app->db->createCommand("select * from user where u_name = '$u_name' AND pwd = '$pwd'")->queryOne();
                if($res){
                    $this -> json("200","成功",$res);
                }else{
                    $this -> json("405","失败","用户名错误或账号密码错误");
                }
            }
        }
    }
    function json($code,$msg,$res){
        $data['code']=$code;
        $data['msg']=$msg;
        $data['json']=$res;
        echo json_encode($data);
    }
}
?>