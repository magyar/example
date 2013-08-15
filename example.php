<?php
/**
* Created by it-group.
* @autor: Kasymov Alim alimexe@ya.ru
* @copyright (c) 2013, RiK-Studio
* Time: 15:58
*/

class ParIterator {

/**
* Выходной массив
* @var array $out
*/
private static $_out = array();

/**
* Входной массив
* @var array $levels
*/
private static $_levels = array();

/**
* Линия начального (первого) уровня пункта
* @var string $starLine
*/
public static $startLine = ' | ';

/**
* Линия остальных пунктов
* @static string $continousLine
*/
public static $continousLine = ' -- ';

private static $menu = array();

/**
* Временный массив для пунктов меню
* @var array $menu
*/
private static $_menu = array();
private static $_menuChield = array();

/**
* Временный массив с разделами категорий и уровнями
* $itemLevel[parent_id][level]
* @var array $itemLevel
*/
private static $itemLevel = array();

/**
* Готовим наши массивы для заполнения, создаем уровни
* @param string $className
* @param array $params массив параметров.
* @throws ParIteratorException
*/
private static function prepareData($className,$params = array()){
$data = $className::model()->findAll($params);

/**
* разбираем массив на родителей и детей
*/
foreach($data AS $value) {
if($value->level == 0) {self::$_menu[] = $value;}
else {self::$_menuChield[] = $value;}
}

/**
* выводим родителя и ищем его ребенка
*/
foreach(self::$_menu AS $itemParent) {
foreach(self::$_menuChield AS $itemChield) {
/**
* собираем массив подпунктов с уровнями
* вида $itemLevel[parent_id][level]
*/
if($itemParent->id == $itemChield->parent_id) {
self::$itemLevel[$itemParent->id][$itemChield->level][] = $itemChield;
}
}
}

foreach(self::$_menu AS $itemParent) {
//echo '| '.$itemParent->title.' - ' . $itemParent->level.' - ' .$itemParent->id.'<br>';
//собираем меню
$visible = ($itemParent->published == '1') ? true : false;
$alias = '#';
if($itemParent->alias !== '#' or $itemParent->alias !== ''){
$alias = Yii::app()->createUrl('post/view',array(
'id'=>$itemParent->link));
}

self::$menu[$itemParent->id] = array(
'label'=>$itemParent->title,
'url'=>$alias,
'visible'=>$visible,
'htmlOptions'=>array('class'=>'topnav'),
//тут метод вызывает сам себя, но только уже с переданным parent_id
//соответственно сюда мы уже не попадем, а пойдем ниже в ELSE
'items'=>self::iterateForMenu($itemParent->id, $itemParent->level+1),
);
}

}

/**
* Проводим хирургическую операцию по рекурсивной отдаче
* см. комментарии по ходу кода
* @param type $parent_id
* @param type $level level
* @return array
*/
private static function iterateForMenu($parent_id, $level,$deep=0,$parent_level=false){
if(!isset(self::$itemLevel[$parent_id][$level][$deep]) or !is_array(self::$itemLevel[$parent_id][$level])) return array();
//мы вызвали метод НЕ из цикла, поэтому parent_id у нас будет NULL
//соответственно мы только начинаем заполнять массив
if($parent_id !== null){
foreach(self::$itemLevel[$parent_id][$level] AS $item){
if($parent_level==false or $parent_level == self::$itemLevel[$parent_id][$level][$deep]->parent_level){
$visible = ($item->published == '1') ? true : false;
//создаем динамически массивы
$alias = '#';
if($item->alias !== '#' or $item->alias !== '')
$alias = Yii::app()->createUrl('post/view',array(
'id'=>$item->link));
$arr[] = array(
'label'=>$item->title,
'url'=>$alias,
'visible'=>$visible,
//'active'=>true,
//'htmlOptions'=>array('class'=>'subnav'),
//опять вызываем себя, с parent_id,и сюда же вернемся
'items'=>self::iterateForMenu($item->parent_id,$level+1,$deep,$item->id));
}else
return array();
}
if(isset($arr)) return $arr;
}
}


public static function getForMenu($className) {
/**
* готовим массивы для обработки
*/
self::prepareData($className,array('order'=>'level ASC, parent_id ASC'));

foreach(self::$menu AS $value):
self::$_out[] = $value;
endforeach;

return self::$_out;
}

public function __destruct() {
unset(self::$_levels,self::$_menu,self::$_out);
}
}
?>