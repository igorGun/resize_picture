<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/app.php');
require_once("translitihor.php");

//need_manager(true);

if ($_POST) {
	$_POST['summary'] = makeCQuotes($_POST['summary']);
	$_POST['title'] = makeCQuotes($_POST['title']);
	
    $_POST['product'] = $_POST['title'];
  
	$team = $_POST;
	
	$insert = array(
		'title', 'alias', 'market_price', 'team_price', 'team_comission', 'percentage', 'end_time', 'begin_time', 'expire_time', 'min_number', 'max_number', 'summary', 'notice', 'conduser', 'per_number',
		'product', 'image', 'detail', 'userreview', 'systemreview', 'image1', 'image2', 'flv', 'card',
		'mobile', 'address', 'fare', 'express', 'delivery', 'credit',
		'user_id', 'state', 'city_id', 'group_id', 'partner_id', 'create_time' ,
		);
	$team['user_id'] = $login_user_id;
	$team['state'] = 'none';
	$team['begin_time'] = strtotime($team['begin_time']);
	$team['city_id'] = abs(intval($team['city_id']));
	$team['end_time'] = strtotime($team['end_time']);
	$team['expire_time'] = strtotime($team['expire_time']);
	$team['image'] = upload_image('upload_image', null, 'team');
	$team['image1'] = upload_image('upload_image1', null, 'team');
    $team['image2'] = upload_image('upload_image2', null, 'team');
	$team['create_time'] = time();
	$team['alias'] = get_seo_keyword($_POST['title']);
	$table = new Table('team', $team);
	$table->SetStrip('detail', 'systemreview', 'notice');
	if ( $team_id = $table->insert($insert) ) {
		Utility::Redirect( WEB_ROOT . "/manage/team/index.php");
	}
}
else {
	$profile = Table::Fetch('leader', $login_user_id, 'user_id');
	//1
	$team = array();
	$team['user_id'] = $login_user_id;
	$team['begin_time'] = strtotime('+0 days');
	$team['end_time'] = strtotime('+1 days'); 
	$team['expire_time'] = strtotime('+1 months');
	$team['min_number'] = 1;
	$team['per_number'] = 10;
	$team['market_price'] = 0;
	$team['team_price'] = 0;
	$team['team_comission'] = 0;
	//3
	$team['delivery'] = 'coupon';
	$team['address'] = $profile['address'];
	$team['mobile'] = $profile['mobile'];
	$team['fare'] = 5;
	$team['conduser'] = $INI['system']['conduser'] ? 'Y' : 'N';
}

$groups = DB::LimitQuery('category', array(
			'condition' => array( 'zone' => 'group', ),
			));
$groups = Utility::OptionArray($groups, 'id', 'name');

$partners = DB::LimitQuery('partner', array(
			'order' => 'ORDER BY id DESC',
			));
$partners = Utility::OptionArray($partners, 'id', 'title');
$selector = 'create';
include template('manage_team_create');




function makeCQuotes($string) {
	$v = str_replace('\"', '"', $string);
	$v = preg_replace('/([^"]*)"([^"]*)"/', '$1«$2»', $v); 
	return str_replace('"', '«', $v);
}