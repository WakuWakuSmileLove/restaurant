<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\FoodForm;  
use app\models\OutCome;

use app\models\SectionForm;
use yii\web\Response;
use app\models\Order;
use app\models\OrderHistory;
use app\models\Food;
use app\models\Daytable;
use app\models\Type;
use app\models\Unit;
use app\models\Liquorstock;



class RestaurantController extends Controller
{
  /**
   * Displays homepage.
   *
   * @return string
   */
  public function actionIndex()
  {

$result = Yii::$app->db->createCommand('SELECT * FROM Seat ')->queryAll();

   return $this->render('index',[
     'data' => $result,
     ]);
  }

  public function actionList($id,$tax = 1){
     $result = Yii::$app->db->createCommand('SELECT food.food_name,food.price,order.id,SUM(order_history.qty) as qty,order.id FROM
      `food`,`order`,`order_history` WHERE
      food.id=order_history.food_id  AND order_history.order_id= order.id AND order.flag=0 AND order.seat_num = :id GROUP BY food.food_name')
           ->bindValue(':id', $_GET['id'])
            ->queryAll();

      $no_ord = Yii::$app->db->createCommand('SELECT food.food_name,food.price,order_history.qty,order.id,order_history.id as order_history_id FROM
             `food`,`order`,`order_history` WHERE
             food.id=order_history.food_id  AND order_history.order_id= order.id AND order.flag=0 AND order.seat_num = :id ')
                  ->bindValue(':id', $_GET['id'])
                   ->queryAll();




   $ord = array();



    $data =  array();
    $order_id=0;
    $total = 0;
    $ordtotal = 0;
    $index = 0;
    $ss = 0;
   foreach ($result as $food) {
  // code...

  $qty = $food['qty'];
  $price = $food['price'];
  $sub_total  = $qty * $price;
  $order_id = $food['id'];

  $total = $sub_total+$total;

  $data[$index] =  array('food_name' =>$food['food_name'] ,'sub_total'=>$sub_total,'qty'=>$food['qty'], );
  $index = $index +1;
//  $data['sub_total'] = $sub_total;

}

foreach ($no_ord as $food) {
// code...

$qty = $food['qty'];
$price = $food['price'];
$sub_total  = $qty * $price;
$order_history_id = $food['order_history_id'];

$ordtotal = $sub_total+$ordtotal;

$ord[$ss] =  array('food_name' =>$food['food_name'] ,'sub_total'=>$sub_total,'qty'=>$food['qty'],'order_history_id'=>$order_history_id );
$ss = $ss +1;
//  $data['sub_total'] = $sub_total;

}



if($tax == 0){
  $tax = 0;
}else{
  $tax = ($total*5)/100;

}


  $data_receipt =  array('total' =>$total ,'data' =>$data,'tax'=>$tax,'id'=>$id,'seat_num'=>$_GET['id'],'order_id'=>$order_id );

  $data_receipt_noord=  array('total' =>$ordtotal ,'data' =>$ord,'tax'=>$tax,'id'=>$id,'seat_num'=>$_GET['id'],'order_id'=>$order_id );



    return $this->render('receipt',[
      'data' => $data_receipt,
      'data_noord' =>$data_receipt_noord,
      ]);

            // return \Yii::createObject([
            //        'class' => 'yii\web\Response',
            //        'format' => \yii\web\Response::FORMAT_JSON,
            //        'data' => $data_receipt
            //    ]);

  }
public function actionClosetable(){

   $update =  Yii::$app->db->createCommand()
                ->update('order', array('flag'=>'1',))->execute();

                $update_seat =  Yii::$app->db->createCommand()
                             ->update('Seat', array('active'=>"0",))->execute();

     return $this->redirect(['/restaurant/index']);

}

public function actionOrder(){
         $init = json_decode($_GET['data']);
         date_default_timezone_set("Asia/Yangon");
         $seat_num = $init->seat_num;
         $data = $init->data;
         $update =  Yii::$app->db->createCommand()
                      ->update('Seat', array('active'=>"1",),'name=:id', array(':id'=>$seat_num))->execute();


         $check = Yii::$app->db->createCommand("SELECT * FROM `order` WHERE seat_num = :id AND flag=0")
                               ->bindValue(":id",$seat_num)
                               ->queryAll();

         $order_id = 0;
         if($check != null){
            foreach ($check as $key) {
              // code...
              $order_id = $key['id'];
            }
         }else{
           $order = new Order();
           $order->flag = 0;
           $order->seat_num = $seat_num;
           $order->date = Yii::$app->formatter->asDate('now', 'yyyy-MM-dd');
           $order->save();


             $order_id  = $order->id;
         }



      foreach ($data as $d) {
        // code...
        $order_history = new OrderHistory();
        $order_history->order_id = $order_id;
        $order_history->food_id = $d->id;
        $food = Yii::$app->db->createCommand("SELECT * FROM `food` WHERE id = :id")
                               ->bindValue(":id",$d->id)
                               ->queryAll();
        $fstr = 0;
        foreach ($food as $fd) {
         // code...
        $fstr = $fd['price'];      
       }
        $order_history->qty = $d->qty;
        $order_history->desc = $d->desc;
        $order_history->take_away = $d->take_away;
        $order_history->price = $fstr;
        $order_history->save();
      }

         //
         return \Yii::createObject([
                'class' => 'yii\web\Response',
                'format' => \yii\web\Response::FORMAT_JSON,
                'data' => [
                    'status'=>200
                  ]
            ]);



         // Yii::$app->db->createCommand()->batchInsert('order', ['flag','seat_num'], [
         //     [0,$seat_num],
         //       ])->execute();




         }

            public function actionData(){
           $result = Food::find()
         ->all();

         return $this->render('foodlist',[
           'data'=>$result
         ]);



                     // return \Yii::createObject([
                     //        'class' => 'yii\web\Response',
                     //        'format' => \yii\web\Response::FORMAT_JSON,
                     //        'data' => $result
                     //    ]);

         }
  public function actionCashout($order_id,$total,$tax,$seat_num){


   $update =  Yii::$app->db->createCommand()
                ->update('order', array('flag'=>'1',),'id=:id', array(':id'=>$order_id))->execute();

  $update_seat =  Yii::$app->db->createCommand()
                             ->update('Seat', array('active'=>"0",),'name=:id', array(':id'=>$seat_num))->execute();

  Yii::$app->db->createCommand()->batchInsert('income', ['income','tax', 'date'], [
                    [$total,$tax,Yii::$app->formatter->asDate('now', 'yyyy-MM-dd')],
                      ])->execute();



$result = Yii::$app->db->createCommand('SELECT food.type ,food.id as food_id,food.food_name,food.price,order.id,SUM(order_history.qty) as qty FROM
      `food`,`order`,`order_history` WHERE
      `food`.id=`order_history`.food_id  AND `order_history`.order_id= `order`.id  AND `order`.id = :id GROUP BY food.food_name')
           ->bindValue(':id', $order_id)
            ->queryAll();



   
   foreach ($result as $food) {

  $qty = $food['qty']; 
  $name =$food['food_name'];
  $price = $food['price'];
  $id = $food['id'];
  $food_id = $food['food_id'];
  $food_type = (int)$food['type'];
  
    if ($food_type == 2){
      $total_qty = Yii::$app->db->createCommand('Select total_qty from liquorstock Where food_id=:id')
      ->bindValue(':id',$food_id)
      ->queryAll();

      foreach($total_qty as $tqty){
        $total_stock_qty = $tqty; 
      }

      $final_qty = (int)$total_stock_qty - (int)$qty;
      echo (int)$qty;
      

      $Cupdate = Yii::$app->db->createCommand('Update liquorstock set total_qty=:qty where food_id=:id')
      ->bindValue(':qty',$final_qty)
      ->bindValue(':id',$food_id)
      ->execute();
    }

  Yii::$app->db->createCommand()->batchInsert('daytable', ['order_id','food_name', 'price','quantity','date','food_type','food_id'], [
                    [$id,$name,$price,$qty,Yii::$app->formatter->asDate('now','yyyy-MM-dd'),$food_type,$food_id],
                      ])->execute();

//  $data['sub_total'] = $sub_total;

}

	 // $model = Yii::$app->db->createCommand()
	 //                         ->delete('order_history', "order_id = $order_id")
	 //                         ->execute();

	 // $de = Yii::$app->db->createCommand()
	 //                         ->delete('order', "id = $order_id")
	 //                         ->execute();


                      $this->redirect(array('restaurant/list', 'id'=>$seat_num));

  }

  public function actionDeleteorder($order_history_id,$seat_num){
    $model = Yii::$app->db->createCommand()
                         ->delete('order_history', "id = $order_history_id")
                         ->execute();

                         $no_ord = Yii::$app->db->createCommand('SELECT food.food_name,food.price,order_history.qty,order.id,order_history.id as order_history_id FROM
                                `food`,`order`,`order_history` WHERE
                                food.id=order_history.food_id  AND order_history.order_id= order.id AND order.flag=0 AND order.seat_num = :id ')
                                     ->bindValue(':id', $seat_num)
                                      ->queryAll();


                                  if($no_ord == null){
                                    $update_seat =  Yii::$app->db->createCommand()
                                                 ->update('Seat', array('active'=>"0",),'name=:id', array(':id'=>$seat_num))->execute();

                                  }

        $this->redirect(array('restaurant/list', 'id'=>$seat_num));


  }


  /*public function actionOrder(){
         $init = json_decode($_GET['data']);

         $seat_num = $init->seat_num;
         $data = $init->data;


 $result = Yii::$app->db->createCommand('SELECT * from `order` where seat_num = :seat_num AND flag = 0 ')
           ->bindValue(":seat_num",$seat_num)
            ->queryAll();

$order_id = 0;
foreach ($result as $r){
$order_id = $r['id '];
}


         if($order_id == 0){
            $order = new Order();
          $order->flag = 0;
          $order->seat_num = $seat_num;
          $order->date = Yii::$app->formatter->asDate('now', 'yyyy-MM');
          $order->save();




            $order_id  = $order->id;
          }




      foreach ($data as $d) {
        // code...
        $order_history = new OrderHistory();
        $order_history->order_id = $order_id;
        $order_history->food_id = $d->id;
        $order_history->qty = $d->qty;
        $order_history->save();
      }

         //
         return \Yii::createObject([
                'class' => 'yii\web\Response',
                'format' => \yii\web\Response::FORMAT_JSON,
                'data' => [
                    'status'=>200
                  ]
            ]);



         // Yii::$app->db->createCommand()->batchInsert('order', ['flag','seat_num'], [
         //     [0,$seat_num],
         //       ])->execute();




         }*/


        //---Liq functions start
         public function actionLiquorstock(){
          $model = new Liquorstock();
  
  
          $liqstock = Yii::$app->db->createCommand('SELECT * FROM liquorstock')
          ->queryAll();
          
          return $this->render('liquorstock',[
            'model'=>$model,
            'liquor'=>$liqstock,
          ]);
        }
  
      public function actionLiqedit($id){
        $model = new Liquorstock();
      $liquorstock=Yii::$app->db->createCommand('select * from liquorstock where id =:d')
      ->bindValue(':d',$id)
      ->queryAll();
         return $this->render('liquoredit',[
          'model'=>$model,
          'liquorstock'=>$liquorstock]);
    }
  
      public function actionLiqupdate(){
          $total_qty = $_GET['cqty'] + $_GET['fqty'];
          $fid =$_GET['fid'];
  Yii::$app->db->createCommand('UPDATE Liquorstock SET total_qty= :total WHERE food_id=:d')
    ->bindValue(':d',$fid)
    ->bindValue(':total',$total_qty)
    ->execute();
  
    return $this->redirect(['/restaurant/liquorstock']);
        
            }

    //---Liq functions stop


    	public function actionOutcome(){
    		$model=new Outcome();
    		$unit =Yii::$app->db->createCommand('SELECT * FROM unit')->queryAll();
    		$section = Yii::$app->db->createCommand('SELECT * FROM section')->queryAll();
    		$date = Yii::$app->formatter->asDate('now', 'php:Y-m-d');
    		$data_unit = array();
    		$data_section=array();

        $allout=Yii::$app->db->createCommand('SELECT outcome.date,outcome.outcome_name,SUM(outcome.outcome_quantity)as qty,SUM(outcome.outcome_quantity*outcome.unit_price*unit.unit2) as total,outcome.unit_price,outcome.section_num FROM  outcome,`unit` WHERE outcome.outcome_unit=unit.id AND outcome.date=:d ')
          ->bindValue(':d',$date)
          ->queryAll();

    		foreach ($unit as $key ) {
    			$data_unit[$key['id']]=$key['unit_name'];
    		}
    		foreach ($section as $key ) {

    			$data_section[$key['id']]=$key['name'];
    			# code...
    		}

    		if($model->load(Yii::$app->request->post()) && $model->validate()){
    			Yii::$app->db->createCommand()->batchInsert('outcome',['outcome_title','outcome_name','outcome_unit','outcome_quantity','unit_price','date','section_num'],[
    				[$model->outcome_title,$model->outcome_name,$model->outcome_unit,$model->outcome_quantity,$model->unit_price,$date,$model->section_num],
    			])->execute();


          
    			
    			return $this->render('outcome',[

    			'model'=>$model,
    			'unit'=>$data_unit,
    			'section'=>$data_section,
          'allout'=>$allout,


    			]);

    		}else {
    			return $this->render('outcome',[


    			'model'=>$model,
    			'unit'=>$data_unit,
    			'section'=>$data_section,
          'allout'=>$allout,

    			]);
    		}
    	}

 public function actionFood(){
    $model = new FoodForm();

    $food = Yii::$app->db->createCommand('SELECT * FROM food ')->queryAll();
    
    $section = Yii::$app->db->createCommand('SELECT * FROM section')
            ->queryAll();

    $type = Yii::$app->db->createCommand('SELECT * FROM type')
                         ->queryAll();

   $data =  array();
   $data_type = array();

   foreach ($type as $key) {
     // code...
     $data_type[$key['id']] = $key['name'];
   }
   foreach($section as $d){
     $data[$d['id']] = $d['name'];
   }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
          Yii::$app->db->createCommand()->batchInsert('food', ['food_name', 'type','section_id','price'], [
              [$model->food_name, $model->type,$model->section,$model->price],
                ])->execute();

                //Insert the food and total qty in Liquor stock table
                //---start
                $fid = Yii::$app->db->createCommand('SELECT * FROM food')
          ->queryAll();

          foreach($fid as $food_id){
              $fidd = $food_id['id'];
              $fname = $food_id['food_name'];
          }
           Yii::$app->db->createCommand()->batchInsert('liquorstock', ['food_id','food_name','total_qty'], [
              [$fidd,$fname,0],
                ])->execute();
                //---stop

                return $this->render('food',[
                  'model' => $model,
                  'foodall'=>$food,
    
                  'section'=>$data,
                  'type' =>$data_type,

                ]);
        }else{

          return $this->render('food',[
            'model' => $model,
            'foodall'=>$food,
            
            'section'=>$data,
            'type' =>$data_type,
          ]);
        }

  }  
  public function actionEdit($id){
    $food=Yii::$app->db->createCommand('select * from food where id =:d')
    ->bindValue(':d',$id)
    ->queryAll();
    return $this->render('foodedit',['food'=>$food]);
  }
  public function actionDeletefood($id){
  	$food=Yii::$app->db->createCommand('delete from order_history where food_id=:d ; delete from food where id=:d')
  	->bindValue(':d',$id)
  	->execute();
  	return $this->redirect(['/restaurant/food']);
  }
  public function actionFoodupdate(){
Yii::$app->db->createCommand('UPDATE food SET food_name=:n , price=:p , type =:t WHERE id=:d')
  ->bindValue(':n',$_GET['fname'])
  ->bindValue(':p',$_GET['fprice'])
  ->bindValue(':t',$_GET['ftype'])
  ->bindValue(':d',$_GET['fid'])
  ->execute();

  return $this->redirect(['/restaurant/foodedit']);
      
          }
   

  public function actionAccounts(){
    // $date =new \DateTime('Y/m',strtotime($_POST['date']));
    // var_dump($date); 
    $section = $_POST['section'];



$check=$_POST['check'];
    if($check ==0 ){
      $d =  Yii::$app->formatter->asDate(strtotime($_POST['date']), 'yyyy-MM-dd'); // 2014-10-06

      $tax_result = Yii::$app->db->createCommand('SELECT SUM(tax) as sum_tax FROM income WHERE income.`date` =:d ')
      ->bindValue(':d',$d)
      ->queryAll();

      $result = Yii::$app->db->createCommand('SELECT daytable.food_name,daytable.price,SUM(daytable.quantity) as qty,SUM(daytable.quantity*daytable.price) as total FROM
       daytable,`food`,`section` WHERE daytable.food_type!=1 AND
        daytable.date =:d AND  food.id = daytable.food_id  AND food.section_id = section.id AND section.id=:section_num GROUP BY daytable.food_name ')
            ->bindValue(':d',$d)
            ->bindValue(':section_num',$section)
             ->queryAll();

      $drink =  Yii::$app->db->createCommand('SELECT daytable.food_name,daytable.price,SUM(daytable.quantity) as qty,SUM(daytable.quantity*daytable.price) as total  FROM
       daytable,`food`,`section` WHERE daytable.food_type=1 AND
        daytable.date =:d AND  food.id = daytable.food_id  AND food.section_id = section.id AND section.id=:section_num GROUP BY daytable.food_name ')
            ->bindValue(':d',$d)
            ->bindValue(':section_num',$section)
             ->queryAll();



    }elseif ($check==1) {
      // code...
      $d =  Yii::$app->formatter->asDate(strtotime($_POST['date']), 'yyyy-MM'); // 2014-10

      $tax_result = Yii::$app->db->createCommand('SELECT SUM(tax) as sum_tax FROM income WHERE income.`date` LIKE :d ')
      ->bindValue(':d',"$d%")
      ->queryAll(); 


      $result = Yii::$app->db->createCommand('SELECT daytable.food_name,daytable.price,SUM(daytable.quantity) as qty,SUM(daytable.quantity*daytable.price) as total FROM
       daytable,`food`,`section` WHERE daytable.food_type!=1 AND
        daytable.date LIKE :d AND  food.id = daytable.food_id  AND food.section_id = section.id AND section.id=:section_num GROUP BY daytable.food_name ')
            ->bindValue(':d',"$d%")
            ->bindValue(':section_num',$section)
             ->queryAll();

      $drink =  Yii::$app->db->createCommand('SELECT daytable.food_name,daytable.price,SUM(daytable.quantity) as qty,SUM(daytable.quantity*daytable.price) as total  FROM
       daytable,`food`,`section` WHERE daytable.food_type=1 AND
        daytable.date LIKE :d AND  food.id = daytable.food_id  AND food.section_id = section.id AND section.id=:section_num GROUP BY daytable.food_name ')
            ->bindValue(':d',"$d%")
            ->bindValue(':section_num',$section)
             ->queryAll();
    }else{
      $d =  Yii::$app->formatter->asDate(strtotime($_POST['date']), 'yyyy'); // 2014

      $tax_result = Yii::$app->db->createCommand('SELECT SUM(tax) as sum_tax FROM income WHERE income.`date` LIKE :d ')
      ->bindValue(':d',"$d%")
      ->queryAll();
      
     
      $result = Yii::$app->db->createCommand('SELECT daytable.food_name,daytable.price,SUM(daytable.quantity) as qty,SUM(daytable.quantity*daytable.price) as total FROM
       daytable,`food`,`section` WHERE daytable.food_type!=1 AND
        daytable.date LIKE :d AND  food.id = daytable.food_id  AND food.section_id = section.id AND section.id=:section_num GROUP BY daytable.food_name ')
            ->bindValue(':d',"$d%")
            ->bindValue(':section_num',$section)
             ->queryAll();

      $drink =  Yii::$app->db->createCommand('SELECT daytable.food_name,daytable.price,SUM(daytable.quantity) as qty,SUM(daytable.quantity*daytable.price) as total  FROM
       daytable,`food`,`section` WHERE daytable.food_type=1 AND
        daytable.date LIKE :d AND  food.id = daytable.food_id  AND food.section_id = section.id AND section.id=:section_num GROUP BY daytable.food_name ')
            ->bindValue(':d',"$d%")
            ->bindValue(':section_num',$section)
             ->queryAll();
    
    }




    $data = Yii::$app->db->createCommand('SELECT * FROM section')
            ->queryAll();






    return $this->render('account',[
      'check'=>$check,
      'drink'=>$drink,
      'data' =>$result,
      'section'=>$data,
      'sec'=>$section,
      'date'=>$d,
    ]);


  }

//   public function actionYear(){
//     // $date =new \DateTime('Y/m',strtotime($_POST['date']));
//     // var_dump($date);
// $d =  Yii::$app->formatter->asDate(strtotime($_POST['date']), 'yyyy'); // 2014-10-06
//     $section = $_POST['section'];


//     $data = Yii::$app->db->createCommand('SELECT * FROM section')
//             ->queryAll();

//             $result = Yii::$app->db->createCommand('SELECT food.food_name,food.price,order_history.qty FROM
//              `food`,`order`,`order_history` WHERE
//              food.id=order_history.food_id  AND order_history.order_id= order.id AND order.date LIKE :d AND order.flag=1 AND food.section_id = :section ')
//                   ->bindValue(':section', $section)
//                   ->bindValue(':d',"$d%")
//                    ->queryAll();

    


// $total = 0;
// $total_outcome = 0;
// $profit=0;
// if($result != null){
//     foreach ($result as $key) {
//       // code...
//       $total = $total + $key['price']*$key['qty'];

//     }
// }


 




//     return $this->render('account',[
//       'data' =>$result,
//       'section'=>$data,
//       'total' =>$total,
//       'sec'=>$section,
//       'total_outcome'=>$total_outcome,
//       'profit'=>$profit,
//       'percent_cash'=>$percent_cash,
//     ]);


//   }

  public function actionAccount($section=1){

    $data = Yii::$app->db->createCommand('SELECT * FROM section')
            ->queryAll();

            // $result = Yii::$app->db->createCommand('SELECT food.food_name,food.price,order_history.qty FROM
            //  `food`,`order`,`order_history` WHERE
            //  food.id=order_history.food_id  AND order_history.order_id= order.id AND order.date = :d AND order.flag=1 AND food.section_id = :section ')
            //       ->bindValue(':section', $section)
            //       ->bindValue(':d',$d)
            //        ->queryAll();






    return $this->render('account',[
      'drink' =>null,
      'total'=>0,
      'data' =>null,
      'cash_out'=>null,
      'section'=>$data,
      'sec'=>0,
      'profit'=>0,
      'percent_cash'=>0,
      'date'=>null,
    ]);




  }
  public function actionTotalprofit($section=1){

    $data = Yii::$app->db->createCommand('SELECT * FROM section')
            ->queryAll();

            // $result = Yii::$app->db->createCommand('SELECT food.food_name,food.price,order_history.qty FROM
            //  `food`,`order`,`order_history` WHERE
            //  food.id=order_history.food_id  AND order_history.order_id= order.id AND order.date = :d AND order.flag=1 AND food.section_id = :section ')
            //       ->bindValue(':section', $section)
            //       ->bindValue(':d',$d)
            //        ->queryAll();






    return $this->render('totalprofit',[
      'top'=>null,
      'expense'=>null,
      'income'=>null,
      'section'=>$data,
      'sec'=>0,
      
    ]);
  }

  public function actionTotalprofits(){
    $section = $_POST['section'];
    $check2=$_POST['check2'];
    if($check2==0){
      $d=Yii::$app->formatter->asDate(strtotime($_POST['date2']),'yyyy-MM');
      $income=Yii::$app->db->createCommand('SELECT SUM(daytable.quantity*daytable.price) as qty ,daytable.date FROM daytable,`food`,`section` WHERE daytable.date LIKE :d AND food.id = daytable.food_id  AND food.section_id = section.id AND section.id=:s GROUP BY daytable.date   
        ORDER BY daytable.date ASC')
     ->bindValue(':d',"$d%")
      ->bindValue(':s',$section)
      ->queryAll();

       $expense=Yii::$app->db->createCommand('SELECT SUM(outcome.outcome_quantity*outcome.unit_price*unit.unit2) as qty,outcome.date FROM `outcome`,`unit` WHERE `date` LIKE  :d AND `section_num`=:s AND outcome.outcome_unit=unit.id  GROUP BY outcome.date ORDER BY outcome.date ASC')
     ->bindValue(':d',"$d%")
      ->bindValue(':s',$section)
      ->queryAll();

      $top5=Yii::$app->db->createCommand('SELECT  daytable.food_name,SUM(daytable.quantity) as qty FROM daytable,`food`,`section` WHERE daytable.date LIKE :d AND food.id = daytable.food_id  AND food.section_id = section.id AND section.id=:s  GROUP BY daytable.food_name ORDER BY sum(daytable.quantity) DESC LIMIT 10 ')
         ->bindValue(':d',"$d%")
      ->bindValue(':s',$section)
      ->queryAll();



}else{

$d=Yii::$app->formatter->asDate(strtotime($_POST['date2']),'yyyy');
      $income=Yii::$app->db->createCommand('SELECT SUM(daytable.quantity*daytable.price) as qty ,MONTHNAME(daytable.date) as `date` FROM daytable,`food`,`section` WHERE daytable.date LIKE :d AND food.id = daytable.food_id  AND food.section_id = section.id AND section.id=:s GROUP BY Month(daytable.date)   
        ORDER BY MONTH(daytable.date) ASC')
     ->bindValue(':d',"$d%")
      ->bindValue(':s',$section)
      ->queryAll();

       $expense=Yii::$app->db->createCommand('SELECT SUM(outcome.outcome_quantity*outcome.unit_price*unit.unit2) as qty,MONTHNAME(outcome.date) as `date` FROM `outcome`,`unit` WHERE `date` LIKE  :d AND `section_num`=:s AND outcome.outcome_unit=unit.id  GROUP BY MONTH(outcome.date) ORDER BY MONTH(outcome.date) ASC')
     ->bindValue(':d',"$d%")
      ->bindValue(':s',$section)
      ->queryAll();

        $top5=Yii::$app->db->createCommand('SELECT  daytable.food_name,SUM(daytable.quantity) as qty FROM daytable,`food`,`section` WHERE daytable.date LIKE :d AND food.id = daytable.food_id  AND food.section_id = section.id AND section.id=:s  GROUP BY daytable.food_name ORDER BY sum(daytable.quantity) DESC LIMIT 10 ')
         ->bindValue(':d',"$d%")
      ->bindValue(':s',$section)
      ->queryAll();


}

      $data=Yii::$app->db->createCommand('SELECT * FROM section')
      ->queryAll();


          return $this->render('totalprofit',[
            'top'=>$top5,
            'expense'=>$expense,
      'income'=>$income,
      'section'=>$data,
      'sec'=>$section,
      
    ]);
    
  }
  public function actionOutcomesz(){
  	$section = $_POST['section'];
  	$check1=$_POST['check1'];
  	if($check1 ==0){
      $d=Yii::$app->formatter->asDate(strtotime($_POST['date1']),'yyyy-MM-dd');
  		$out_kitchen= Yii::$app->db->createCommand('SELECT outcome.outcome_name,SUM(outcome.outcome_quantity) as qty,unit.unit1,unit.unit2,unit.unit1_name,unit.unit2_name,outcome.unit_price FROM `outcome`,`unit` WHERE `date`= :d AND `section_num`= :s AND outcome.outcome_unit=unit.id AND upper(outcome.outcome_title)="KITCHEN" GROUP BY outcome.outcome_name')
  		->bindValue(':d',$d)
  		->bindValue(':s',$section)
  		->queryAll();
  		
  		$out_bar= Yii::$app->db->createCommand('SELECT outcome.outcome_title,outcome.outcome_name,SUM(outcome.outcome_quantity) as qty,unit.unit1,unit.unit2,unit.unit1_name,unit.unit2_name,outcome.unit_price FROM `outcome`,`unit` WHERE `date`= :d AND `section_num`= :s AND outcome.outcome_unit=unit.id AND upper(outcome.outcome_title)="BAR" GROUP BY outcome.outcome_name')
  		->bindValue(':d',$d)
  		->bindValue(':s',$section)
  		->queryAll();

  		$out_bbq= Yii::$app->db->createCommand('SELECT outcome.outcome_title,outcome.outcome_name,SUM(outcome.outcome_quantity) as qty,unit.unit1,unit.unit2,unit.unit1_name,unit.unit2_name,outcome.unit_price FROM `outcome`,`unit` WHERE `date`= :d AND `section_num`= :s AND outcome.outcome_unit=unit.id AND upper(outcome.outcome_title)="BARBEQUE" GROUP BY outcome.outcome_name')
  		->bindValue(':d',$d)
  		->bindValue(':s',$section)
  		->queryAll();
}elseif ($check1==1) {

      $d=Yii::$app->formatter->asDate(strtotime($_POST['date1']),'yyyy-MM');
        $out_kitchen= Yii::$app->db->createCommand('SELECT outcome.outcome_name,SUM(outcome.outcome_quantity) as qty,unit.unit1,unit.unit2,unit.unit1_name,unit.unit2_name,outcome.unit_price FROM `outcome`,`unit` WHERE `date` LIKE :d AND `section_num`= :s AND outcome.outcome_unit=unit.id AND upper(outcome.outcome_title)="KITCHEN" GROUP BY outcome.outcome_name')
      ->bindValue(':d',"$d%")
      ->bindValue(':s',$section)
      ->queryAll();
      
      $out_bar= Yii::$app->db->createCommand('SELECT outcome.outcome_title,outcome.outcome_name,SUM(outcome.outcome_quantity) as qty,unit.unit1,unit.unit2,unit.changed_unit,outcome.unit_price FROM `outcome`,`unit` WHERE `date` LIKE :d AND `section_num`= :s AND outcome.outcome_unit=unit.id AND upper(outcome.outcome_title)="BAR" GROUP BY outcome.outcome_name')
      ->bindValue(':d',"$d%")
      ->bindValue(':s',$section)
      ->queryAll();

      $out_bbq= Yii::$app->db->createCommand('SELECT outcome.outcome_title,outcome.outcome_name,SUM(outcome.outcome_quantity) as qty,unit.unit1,unit.unit2,unit.unit1_name,unit.unit2_name,outcome.unit_price FROM `outcome`,`unit` WHERE `date` LIKE :d AND `section_num`= :s AND outcome.outcome_unit=unit.id AND upper(outcome.outcome_title)="BARBEQUE" GROUP BY outcome.outcome_name')
      ->bindValue(':d',"$d%")
      ->bindValue(':s',$section)
      ->queryAll();
  # code...
}else
{

      $d=Yii::$app->formatter->asDate(strtotime($_POST['date1']),'yyyy');

  $out_kitchen= Yii::$app->db->createCommand('SELECT outcome.outcome_name,SUM(outcome.outcome_quantity) as qty,unit.unit1,unit.unit2,unit.unit1_name,unit.unit2_name,outcome.unit_price FROM `outcome`,`unit` WHERE `date` LIKE :d AND `section_num`= :s AND outcome.outcome_unit=unit.id AND upper(outcome.outcome_title)="KITCHEN" GROUP BY outcome.outcome_name')
      ->bindValue(':d',"$d%")
      ->bindValue(':s',$section)
      ->queryAll();
      
      $out_bar= Yii::$app->db->createCommand('SELECT outcome.outcome_title,outcome.outcome_name,SUM(outcome.outcome_quantity) as qty,unit.unit1,unit.unit2,unit.unit1_name,unit.unit2_name,outcome.unit_price FROM `outcome`,`unit` WHERE `date` LIKE :d AND `section_num`= :s AND outcome.outcome_unit=unit.id AND upper(outcome.outcome_title)="BAR" GROUP BY outcome.outcome_name')
      ->bindValue(':d',"$d%")
      ->bindValue(':s',$section)
      ->queryAll();

      $out_bbq= Yii::$app->db->createCommand('SELECT outcome.outcome_title,outcome.outcome_name,SUM(outcome.outcome_quantity) as qty,unit.unit1,unit.unit2,unit.changed_unit,outcome.unit_price FROM `outcome`,`unit` WHERE `date` LIKE :d AND `section_num`= :s AND outcome.outcome_unit=unit.id AND upper(outcome.outcome_title)="BARBEQUE" GROUP BY outcome.outcome_name')
      ->bindValue(':d',"$d%")
      ->bindValue(':s',$section)
      ->queryAll();
} 




     


  		$data=Yii::$app->db->createCommand('SELECT * FROM section')
  		->queryAll();

  		

  		
  		  	
  		  	return $this->render('outcomes',[
  		  		'kitchen'=>$out_kitchen,
            'bar'=>$out_bar,
            'bbq'=>$out_bbq,
  		  		'sec'=>$section,
  		  		'section'=>$data,
            'date'=>$d,

            
  		  		]);

  
}

  public function actionOutcomes($section=1){

    $data = Yii::$app->db->createCommand('SELECT * FROM section')
            ->queryAll();

            // $result = Yii::$app->db->createCommand('SELECT food.food_name,food.price,order_history.qty FROM
            //  `food`,`order`,`order_history` WHERE
            //  food.id=order_history.food_id  AND order_history.order_id= order.id AND order.date = :d AND order.flag=1 AND food.section_id = :section ')
            //       ->bindValue(':section', $section)
            //       ->bindValue(':d',$d)
            //        ->queryAll();


    





    return $this->render('outcomes',[
      'allout'=>null,
            'kitchen'=>null,
            'bar'=>null,
            'bbq'=>null,
	      		'sec'=>0,
    	  	'section'=>$data,
   	   		'total1'=>0,
          'date'=>null,
  		  		 	

    ]);


  }

  public function actionSection(){
    $model = new SectionForm();
    if ($model->load(Yii::$app->request->post()) && $model->validate()) {
      Yii::$app->db->createCommand()->batchInsert('section', ['name'], [
          [$model->section_name],
            ])->execute();

            return $this->render('section',[
              'model' => $model,
            ]);
    }else{

      return $this->render('section',[
        'model' => $model,
      ]);
    }

  }


  public function actionType(){
    $model = new Type();
    if ($model->load(Yii::$app->request->post()) && $model->validate()) {
      Yii::$app->db->createCommand()->batchInsert('type', ['name'], [
          [$model->name],
            ])->execute();
            Yii::$app->session->setFlash('success', "Data save!");

            return $this->render('type',[
              'model' => $model,
            ]);
    }else{

      return $this->render('type',[
        'model' => $model,
      ]);
    }

  }

  public function actionUnit(){
        $model = new Unit();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
        	Yii::$app->db->createCommand()->batchInsert('Unit',['unit_name','unit1','unit2','unit1_name','unit2_name'],[
        		[$model->unit_name,$model->unit1,$model->unit2,$model->unit1_name,$model->unit2_name],
        	])->execute();
        	Yii::$app->session->setFlash('success', "Data save!");

        	
            return $this->render('unit',[
            	'model'=>$model,
            ]);

        }else{
        return $this->render('unit', [
            'model' => $model,
        ]);
    }
}
  
  public function actionSeat(){
    $model = new Type();
    if ($model->load(Yii::$app->request->post()) && $model->validate()) {
      Yii::$app->db->createCommand()->batchInsert('Seat', ['name'], [
          [$model->name],
            ])->execute();
            Yii::$app->session->setFlash('success', "Data save!");
            return $this->render('seat',[
              'model' => $model,
            ]);
    }else{

      return $this->render('seat',[
        'model' => $model,
      ]);
    }


  }
 
  /*public function actionOrd(){
    $result = Yii::$app->db->createCommand('SELECT order.seat_num,order.id as order_id ,order_history.id as order_history_id,food.food_name,food.price,order_history.qty FROM
     `food`,`order`,`order_history` WHERE
     food.id=order_history.food_id  AND order_history.order_id= order.id AND order.flag=0 AND order_history.finish_order=0')
           ->queryAll();



   $data =  array();

$total = 0;
$index = 0;
foreach ($result as $food) {
 // code...

 $qty = $food['qty'];
 $price = $food['price'];
 $sub_total  = $qty * $price;

 $total = $sub_total+$total;

 $data[$index] =  array('food_name' =>$food['food_name'] ,'sub_total'=>$sub_total,'qty'=>$food['qty'] ,'price'=>$food['price'],'order_id'=>$food['order_id'],'order_history_id'=>$food['order_history_id']
,'seat_num'=>$food['seat_num']);
 $index = $index +1;
//  $data['sub_total'] = $sub_total;

}


 $data_receipt =  array('total' =>$total ,'data' =>$data );




   return $this->render('orderfood',[
     'data' => $data_receipt
     ]);
  }*/
public function actionOrd($section = 1){

    Yii::$app->timeZone = 'Asia/Rangoon';
    $section_data = Yii::$app->db->createCommand('SELECT * FROM section')->queryAll();

$date = Yii::$app->formatter->asDate('now', 'yyyy-MM-dd');


    $result = Yii::$app->db->createCommand('SELECT order.seat_num,order.id as order_id ,order_history.id as order_history_id,order_history.finish_order as finish,food.food_name,food.price,order_history.qty,order_history.desc FROM
     food,`order`,`order_history`,`type` WHERE type.id=food.type AND type.name!="drink" AND
     food.section_id=:id AND food.id=order_history.food_id  AND order_history.order_id= order.id AND order.date=:d  AND order_history.finish_order!=2 Order BY order_history.id desc')
     ->bindValue(":id",$section)
    ->bindValue(":d",$date)
     ->queryAll();



   $data =  array();

$total = 0;
$index = 0;
foreach ($result as $food) {
 // code...

 $qty = $food['qty'];
 $price = $food['price'];
 $sub_total  = $qty * $price;

 $total = $sub_total+$total;

 $data[$index] =  array('food_name' =>$food['food_name'] ,'sub_total'=>$sub_total,'qty'=>$food['qty'] ,'price'=>$food['price'],'order_id'=>$food['order_id'],'order_history_id'=>$food['order_history_id']
,'seat_num'=>$food['seat_num'],'desc'=>$food['desc'],'finish'=>$food['finish']);
 $index = $index +1;
//  $data['sub_total'] = $sub_total;

}


 $data_receipt =  array('total' =>$total ,'data' =>$data );




   return $this->render('orderfood',[
     'section'=>$section,
     'data' => $data_receipt,
     'section_data'=>$section_data,
     'date'=>$date
     ]);
  }

  public function actionOrderf($order_id,$order_history_id,$section){

    Yii::$app->db->createCommand("UPDATE order_history SET finish_order=1 WHERE order_id=$order_id AND id=$order_history_id")
   ->execute();
   //$this->redirect(array('restaurant/list', 'id'=>$seat_num));

  return $this->redirect(array('restaurant/ord', 'section'=>$section));
  }

  public function actionOrderd($order_id,$order_history_id,$section){

    Yii::$app->db->createCommand("UPDATE order_history SET finish_order=2 WHERE order_id=$order_id AND id=$order_history_id")
   ->execute();
   //$this->redirect(array('restaurant/list', 'id'=>$seat_num));

  return $this->redirect(array('restaurant/ord', 'section'=>$section));
  }


public function actionSwap(){

    $update =  Yii::$app->db->createCommand()
                 ->update('order', array('seat_num'=>$_GET['seat'],),'id=:id', array(':id'=>$_GET['order_id']))->execute();

                 $update_seat =  Yii::$app->db->createCommand()
                              ->update('seat', array('active'=>"0",),'name=:id', array(':id'=>$_GET['seat_num']))->execute();
                              $update_seat_real =  Yii::$app->db->createCommand()
                                           ->update('seat', array('active'=>"1",),'name=:id', array(':id'=>$_GET['seat']))->execute();


                                           $this->redirect(array('restaurant/list', 'id'=>$_GET['seat']));


  }

}




?>
