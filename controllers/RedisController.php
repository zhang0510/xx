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
class RedisController extends CommonController{
    public function actionGetname(){
    	//key=>value
    	//设置键值
        //$source = Yii::$app->redis->set('age','22');
        //加一
        $var2 = Yii::$app->redis->incr('age');
        //减一
        $var2 = Yii::$app->redis->decr('age');
        //获取键的值
        $name = Yii::$app->redis->get('age');
        //删除键
        //$var2 = Yii::$app->redis->del("name");
        //查看键是否存在
        //$var2 = Yii::$app->redis->exists('name');
        //查看所有的键
        //$var2 = Yii::$app->redis->keys("*");
		echo $name;
		//print_r($var2);
        echo "<br>";die;
        //列表
        //设置列表
		$var1 = Yii::$app->redis->rpush("list1","c");
		//取出列表（一段）
		$var3 = Yii::$app->redis->lrange("list1",0,2);
		//对某列表的某索引的值进行修改
		//$var33 = Yii::$app->redis->lset('vari',21,'2323');
		print_r($var3);
        
    }
}
?>