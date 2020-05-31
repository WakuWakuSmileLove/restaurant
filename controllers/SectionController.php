<?php
   namespace app\controllers;
   use yii\rest\ActiveController;
   use app\models\Section;
   use app\models\Type;


   class SectionController extends ActiveController {
     public $modelClass = 'app\models\Section';


     public function actionSection(){
       $result = Section::find()->all();

     return \Yii::createObject([
            'class' => 'yii\web\Response',
            'format' => \yii\web\Response::FORMAT_JSON,
            'data' => $result
        ]);
     }

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
