<?php
   namespace app\controllers;
   use yii\rest\ActiveController;
   use app\models\Food;

   class FoodController extends ActiveController {
      public $modelClass = 'app\models\Food';


      public function actionFood($type){
        $result = Food::find()
      ->where(['type' => $type])
      ->all();

      return \Yii::createObject([
             'class' => 'yii\web\Response',
             'format' => \yii\web\Response::FORMAT_JSON,
             'data' => $result
         ]);
      }
   }
?>
