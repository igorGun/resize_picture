<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/app.php');

//need_manager(true);
$id = abs(intval($_GET['id']));
if (!$id || !$team = Table::Fetch('team', $id)) {
	Utility::Redirect( WEB_ROOT . '/team/create.php');
}

if ($_POST) {
	$insert = array(
		'title'/*,'alias','marked'*/,'team_comission', 'percentage', 'end_time', 'begin_time', 'expire_time', 'summary', 'notice', 'per_number',
		'product', 'image',  'detail', 'userreview', 'systemreview', 'resize_image', 'image1', 'image2', 'flv', 'card', 'btn_address',
		'delivery', 'mobile', 'address', 'fare', 'express', 'credit',
		'user_id', 'city_id', 'group_id', 'partner_id','sms','ontop'
		);
	$_POST['summary'] = makeCQuotes($_POST['summary']);
	$_POST['title'] = makeCQuotes($_POST['title']);
	

    $_POST['product'] = $_POST['title'];
	$table = new Table('team', $_POST);
	$table->SetStrip('detail', 'systemreview', 'notice');
	$table->begin_time = strtotime($_POST['begin_time']);
	$table->city_id = abs(intval($table->city_id));
	$table->end_time = strtotime($_POST['end_time']);
	$table->expire_time = strtotime($_POST['expire_time']);
	$table->image = upload_image('upload_image', $team['image'], 'team');
	$table->resize_image = upload_image('upload_image', $team['resize_image'], 'resize');
	$table->image1 = upload_image('upload_image1',$team['image1'],'team');
	$table->image2 = upload_image('upload_image2',$team['image2'],'team');
	$table->sms = (isset($_POST['sms'])?'Y':'N');
	/*$table->marked = $POST['marked'];*/
	$table->ontop = 0;
	if(isset($_POST['ontop'])) {
		if ($_POST['ontop'] == 1 || $_POST['ontop'] == 2 || $_POST['ontop'] == 3 || ($_POST['ontop'] < 7 && $_POST['ontop'] > 3)) {

			$table->ontop = $_POST['ontop'];
			$result = DB::Query("UPDATE `team` SET `ontop`='0' WHERE `ontop`='".$_POST['ontop']."'");
		}
	}

	$error_tip = array();
	if ( !$error_tip)  {
		if ( $table->update($insert) ) {

			$field = strtoupper($table->conduser)=='Y' ? null : 'quantity';
			$now_number = Table::Count('order', array(
						'team_id' => $table->id,
						'state' => 'pay',
						), $field);
			Table::UpdateCache('team', $table->id, array(
				'now_number' => $now_number,
			));

			Session::Set('notice', 'Данные успешно обновлены');
			Utility::Redirect( WEB_ROOT. "/manage/team/edit.php?id={$team['id']}");
		} else {
			Session::Set('error', 'Произошла ошибка');
		}
	}
}

$groups = DB::LimitQuery('category', array(
			'condition' => array( 'zone' => 'group', ),
			));
$groups = Utility::OptionArray($groups, 'id', 'name');

$partners = DB::LimitQuery('partner', array(
			'order' => 'ORDER BY id DESC',
			));
$partners = Utility::OptionArray($partners, 'id', 'title');
include template('manage_team_edit');

function makeCQuotes($string) {
	$v = str_replace('\"', '"', $string);
	$v = preg_replace('/([^"]*)"([^"]*)"/', '$1«$2»', $v); 
	return str_replace('"', '«', $v);
}