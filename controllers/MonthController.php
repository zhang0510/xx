<?php
/**
 * @Author: Marte
 * @Date:   2018-09-18 09:30:50
 * @Last Modified by:   Marte
 * @Last Modified time: 2018-09-19 10:51:17
 */
namespace app\controllers;

use Yii;
use app\models\Month;
use app\models\News;
use app\models\AreaPrice;

class MonthController extends CommonController{
    public $enableCsrfValidation = false;
    public function actionGetname(){
        $session = Yii::$app->session;
        $userInfo = $session->get("userInfo");
        if(empty($userInfo)){
            $request = Yii::$app->request;
            $code = $request->get('code');
            $userInfo = $this->memberAuthorization($code);
        }
        echo $userInfo['nickname'];
        echo $userInfo['openid'];
    }
    public function actionGetsex(){
        $session = Yii::$app->session;
        $userInfo = $session->get("userInfo");
        if(empty($userInfo)){
            $request = Yii::$app->request;
            $code = $request->get('code');
            $userInfo = $this->memberAuthorization($code);
        }
        echo $userInfo['sex'] == 1?'男':'女';
    }
    public function actionSelect_price(){
        $sql = "select * from `tuo_area_price` where area_pid='0'";
        $area = Yii::$app->db->createCommand($sql)->queryAll();
        return $this->renderPartial('select_price',array('area'=>$area));
    }
    public function actionGet_city(){
        $request = Yii::$app->request;
        $id = $request->post('id');
        $sql = "select * from `tuo_area_price` where area_pid='{$id}'";
        $area = Yii::$app->db->createCommand($sql)->queryAll();
        $str='';
        if($area){
            foreach ($area as $k=>$v){
                $str .= '<option value="'.$v['area_id'].'">'.$v['area_name'].'</option>';
            }
        }
        echo $str;
    }

    public function actionGetprice(){
        $request = Yii::$app->request;
        //查询地址表，并将id作为key
        $sql = "select * from `tuo_area_price`";
        $area_price = Yii::$app->db->createCommand($sql)->queryAll();
        $area = $zhi = array();
        foreach($area_price as $k=>$v){
            $area[$v['area_id']] = $v['area_name'];
        }

        //查询价格表并将key设置为：出发地省会/出发地城市
        $sql_zhi = "select * from `tuo_zhi`";
        $arr = Yii::$app->db->createCommand($sql_zhi)->queryAll();
        foreach($arr as $k=>$v){
            $zhi[$v['start_prov'].'/'.$v['start_city']][] = $v;
        }
        //接收值
        $start = $request->post('start');
        $start_city = $request->post('start_city');
        $end = $request->post('end');
        $end_city = $request->post('end_city');
        $z_k = $start.'/'.$start_city;

        //查询目的地符合条件的信息
        $sql_zhi_end = "select * from `tuo_zhi` where end_prov='{$end}' and end_city='{$end_city}'";
        $e_arr = Yii::$app->db->createCommand($sql_zhi_end)->queryAll();
        $end_arr = $end_content = array();
        foreach($e_arr as $k=>$v){
            $end_arr[$v['start_prov'].'/'.$v['start_city']] = $v['start_prov'].'/'.$v['start_city'];
            $end_content[$v['start_prov'].'/'.$v['start_city']] =$v;
        }

        if(empty($end_arr)){
            echo '系统暂无目的地为此地的价格';die;
        }

        if(!isset($zhi[$z_k])){
            echo '系统暂无出发地为此地的价格';die;
        }
        $return = array();
        //循环出发地符合查询条件的价格信息
        foreach ($zhi[$z_k] as $k=>$v) {
            if ($v['end_prov'] == $end && $v['end_city'] == $end_city) {
                $str = $area[$v['start_prov']].'/'.$area[$v['start_city']].'——'.$area[$v['end_prov']].'/'.$area[$v['end_city']];
                $return[0][] = "直发：".$str.'；成本->'.$v['cb_price'].'；最终价格->'.$v['zz_price'].'；备注->'.$v['zhi_mark'].'；联系人->'.$v['zhi_man'];
            }else{
                $z_k1 = $v['end_prov'].'/'.$v['end_city'];
                $zhi[$z_k1] = isset($zhi[$z_k1])?$zhi[$z_k1]:array();
                foreach ($zhi[$z_k1] as $key=>$value) {
                    if ($value['end_prov'] == $end && $value['end_city'] == $end_city) {
                        $arr = array($v,$value);
                        $re = $this->line_content($arr,$area);
                        $return[$re['0']][] = $re['1'];
                    }else{
                        $z_k2 = $value['end_prov'].'/'.$value['end_city'];
                        $zhi[$z_k2] = isset($zhi[$z_k2])?$zhi[$z_k2]:array();
                        foreach ($zhi[$z_k2] as $key2=>$value2) {
                            if ($value2['end_prov'] == $end && $value2['end_city'] == $end_city) {
                                $arr = array($v,$value,$value2);
                                $re = $this->line_content($arr,$area);
                                $return[$re['0']][] = $re['1'];
                            }/*else{
                                $z_k3 = $value2['end_prov'].'/'.$value2['end_city'];
                                if(in_array($z_k3,$end_arr)){
                                    $arr = array($v,$value,$value2,$end_content[$z_k3]);
                                    $re = $this->line_content($arr,$area);
                                    $return[$re['0']][] = $re['1'];
                                }
                            }*/
                        }
                    }
                }
            }
        }
        $num = 1;
        ksort($return);
        foreach ($return as $k=>$v){
            foreach ($v as $key=>$val){
                echo "<br/><br/><span style='font-weight: 800;'>线路$num</span>";
                echo $val;
                $num++;
            }
        }
    }

    public function line_content($arr,$area){
        $str = $area[$arr['0']['start_prov']].'/'.$area[$arr['0']['start_city']];
        $sum = $price = 0;
        foreach($arr as $k=>$v){
            $str .= '——'.$area[$v['end_prov']].'/'.$area[$v['end_city']];
            $line = $area[$v['start_prov']].'/'.$area[$v['start_city']].'——'.$area[$v['end_prov']].'/'.$area[$v['end_city']];
            $return[] = $line.'：成本->'.$v['cb_price'].'；最终价格->'.$v['zz_price'].'；备注->'.$v['zhi_mark'].'；联系人->'.$v['zhi_man'];
            $price_jia = $v['zz_price'] == '0'?0:$v['zz_price']-$v['cb_price'];
            if($price_jia>$price){
                $price = $price_jia;
            }
            $sum+=$v['cb_price'];
        }
        $sum += $price;
        $return[] = '总价：'.$sum;
        $retu = implode('<br>',$return);
        $return_str = '<br/>线路：'.$str.'<br/>'.$retu;
        return array($sum,$return_str);
    }

}
?>