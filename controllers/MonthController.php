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
    class MonthController extends Controller{
        public $enableCsrfValidation = false;
        public function actionLogin(){
            return $this -> renderPartial('login');
        }
        public function actionLogine(){
            $request = Yii::$app->request;
            $user = new Month();
            $u_name = $request->post('u_name');
            $pwd = $request->post('pwd');
            $res = Month::find()->where(['u_name'=>$u_name,'pwd'=>$pwd])->One();
            if($res){
                $this -> redirect(array('add'));
            }else{
                echo "登录失败";
            }
        }
        public function actionAdd(){
            $up = new UploadForm();
            return $this -> renderPartial('add',['model'=>$up]);
        }
        public function actionDoadd(){
            $request = Yii::$app->request;
            $model = new UploadForm();
            if (Yii::$app->request->isPost) {
                $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
                if ($model->upload()) {
                    return '成功';
                }else{
                    //文件上傳失敗
                    return '失败';
                }
            }
        //return $this->render('add', ['model' => $model]);
        }
    }
?>