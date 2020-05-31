<?php
   namespace app\controllers;
   use yii\rest\ActiveController;
   use app\models\Type;

   class FoodController extends ActiveController {
      public $modelClass = 'app\models\Type';


      public function actionType(){
        $result = Type::find()
      ->all();

      return \Yii::createObject([
             'class' => 'yii\web\Response',
             'format' => \yii\web\Response::FORMAT_JSON,
             'data' => $result
         ]);
      }
   }
?>
