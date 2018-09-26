<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class IndexController extends Controller
{
    public function actionIndex(){
        return $this->renderPartial('index');
    }

    public function actionShow(){
        $request = \Yii::$app->request;
        $name = $request->get('bookname', '谁的青春不迷茫');
        $curl = curl_init();
        $url = "https://www.qidian.com/search?kw=".urlencode($name);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($curl);
        curl_close($curl);
        preg_match_all('/<li class="res-book-item" data-bid="[0-9]+" data-rid="[0-9]+">(.*?)<\/li>/is',$data,$match);
        preg_match_all('/<h4><a href="(.*?)" target="_blank" data-eid="qd_S05" data-bid="[0-9]+" data-algrid="0.0.0"><cite class="red-kw">(.*?)<\/cite><\/a><\/h4>/is',$match['0']['0'],$match1);
        echo "<pre/>";
        if(empty($match1['0'])){
            echo "<<".$name.">>未找到";die;
        }
        $this->redirect($match1['1']['0']);
    }
}
