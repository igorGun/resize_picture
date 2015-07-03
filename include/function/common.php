<?php
/* import other */
import('current');
import('utility');
import('mailer');
import('sms');
import('upgrade');

function template($tFile) {
    global $INI;
    if ( 0===strpos($tFile, 'manage') ) {
        return __template($tFile);
    }
    if ($INI['skin']['template']) {
        $templatedir = DIR_TEMPLATE. '/' . $INI['skin']['template'];
        $checkfile = $templatedir . '/html_header.html';
        if ( file_exists($checkfile) ) {
            return __template($INI['skin']['template'].'/'.$tFile);
        }
    }
    return __template($tFile);
}

function render($tFile, $vs=array()) {
    ob_start();
    foreach($GLOBALS AS $_k=>$_v) {
        ${$_k} = $_v;
    }
    foreach($vs AS $_k=>$_v) {
        ${$_k} = $_v;
    }
    include template($tFile);
    return render_hook(ob_get_clean());
}

function render_hook($c) {
    global $INI;
    $c = preg_replace('#href="/#i', 'href="'.WEB_ROOT.'/', $c);
    $c = preg_replace('#src="/#i', 'src="'.WEB_ROOT.'/', $c);
    $c = preg_replace('#action="/#i', 'action="'.WEB_ROOT.'/', $c);

    /* theme */
    $page = strval($_SERVER['REQUEST_URI']);
    if($INI['skin']['theme'] && !preg_match('#/manage/#i',$page)) {
        $themedir = WWW_ROOT. '/static/theme/' . $INI['skin']['theme'];
        $checkfile = $themedir. '/css/index.css';
        if ( file_exists($checkfile) ) {
            $c = preg_replace('#/static/css/#', "/static/theme/{$INI['skin']['theme']}/css/", $c);
            $c = preg_replace('#/static/img/#', "/static/theme/{$INI['skin']['theme']}/img/", $c);
        }
    }
    return $c;
}

function output_hook($c) {
    global $INI;
    if ( 0==abs(intval($INI['system']['gzip'])))  die($c);
    $HTTP_ACCEPT_ENCODING = $_SERVER["HTTP_ACCEPT_ENCODING"];
    if( strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false )
        $encoding = 'x-gzip';
    else if( strpos($HTTP_ACCEPT_ENCODING,'gzip') !== false )
        $encoding = 'gzip';
    else $encoding == false;
    if (function_exists('gzencode')&&$encoding) {
        $c = gzencode($c);
        header("Content-Encoding: {$encoding}");
    }
    $length = strlen($c);
    header("Content-Length: {$length}");
    die($c);
}

$lang_properties = array();
function I($key) {
    global $lang_properties, $LC;
    if (!$lang_properties) {
        $ini = DIR_ROOT . '/i18n/' . $LC. '/properties.ini';
        $lang_properties = Config::Instance($ini);
    }
    return isset($lang_properties[$key]) ?
            $lang_properties[$key] : $key;
}

function json($data, $type='eval') {
    $type = strtolower($type);
    $allow = array('eval','alert','updater','dialog','mix', 'refresh');
    if (false==in_array($type, $allow))
        return false;
    Output::Json(array( 'data' => $data, 'type' => $type,));
}

function redirect($url=null) {
    header("Location: {$url}");
    exit;
}
function write_php_file($array, $filename=null) {
    $v = "<?php\r\n\$INI = ";
    $v .= var_export($array, true);
    $v .=";\r\n?>";
    return file_put_contents($filename, $v);
}

function write_ini_file($array, $filename=null) {
    $ok = null;
    if ($filename) {
        $s =  ";;;;;;;;;;;;;;;;;;\r\n";
        $s .= ";; SYS_INIFILE\r\n";
        $s .= ";;;;;;;;;;;;;;;;;;\r\n";
    }
    foreach($array as $k=>$v) {
        if(is_array($v)) {
            if($k != $ok) {
                $s  .=  "\r\n[{$k}]\r\n";
                $ok = $k;
            }
            $s .= write_ini_file($v);
        }else {
            if(trim($v) != $v || strstr($v,"["))
                $v = "\"{$v}\"";
            $s .=  "$k = \"{$v}\"\r\n";
        }
    }

    if(!$filename) return $s;
    return file_put_contents($filename, $s);
}   

function save_config($type='ini') {
    global $INI;
    $q = ZSystem::GetSaveINI($INI);
    if ( strtoupper($type) == 'INI' ) {
        if (!is_writeable(SYS_INIFILE)) return false;
        return write_ini_file($q, SYS_INIFILE);
    }
    if ( strtoupper($type) == 'PHP' ) {
        if (!is_writeable(SYS_PHPFILE)) return false;
        return write_php_file($q, SYS_PHPFILE);
    }
    return false;
}

function save_system($ini) {
    $system = Table::Fetch('system', 1);
    $ini = ZSystem::GetUnsetINI($ini);
    $value = Utility::ExtraEncode($ini);
    $table = new Table('system', array('value'=>$value));
    if ( $system ) $table->SetPK('id', 1);
    return $table->update(array( 'value'));
}

/* user relative */
function need_login($force=false) {
    if ( isset($_SESSION['user_id']) ) {
        if (is_post()) {
            unset($_SESSION['loginpage']);
            unset($_SESSION['loginpagepost']);
        }
        return $_SESSION['user_id'];
    }
    if ( is_get() ) {
        Session::Set('loginpage', $_SERVER['REQUEST_URI']);
    } else {
        Session::Set('loginpage', $_SERVER['HTTP_REFERER']);
        Session::Set('loginpagepost', json_encode($_POST));
    }
    return redirect(WEB_ROOT . '/account/loginup.php');
}
function need_post() {
    return is_post() ? true : redirect(WEB_ROOT . '/index.php');
}
function need_manager($super=false) {
    if ( ! is_manager() ) {
        redirect( WEB_ROOT . '/account/login.php' );
    }
    if ( ! $super ) return true;
    if ( abs(intval($_SESSION['user_id'])) == 1 ) return true;
    return redirect( WEB_ROOT . '/manage/misc/index.php');
}
function need_partner() {
    return is_partner() ? true : redirect( WEB_ROOT . '/biz/login.php');
}

function need_auth($b=true) {
    global $AJAX, $INI, $login_user;
    if (is_string($b)) {
        $auths = $INI['authorization'][$login_user['id']];
        $b = is_manager(true)||in_array($b, $auths);
    }
    if (true===$b) {
        return true;
    }
    if ($AJAX) json('no permission', 'alert');
    Session::Set('error', 'no permission');
    redirect( WEB_ROOT . '/account/login.php');
}

function is_manager($super=false) {
    global $login_user;
    if ( ! $super ) return ($login_user['manager'] == 'Y');
    return $login_user['id'] == 1;
}
function is_partner() {
    return ($_SESSION['partner_id']>0);
}

function is_newbie() {
    return (cookieget('newbie')!='N');
}
function is_get() {
    return ! is_post();
}
function is_post() {
    return strtoupper($_SERVER['REQUEST_METHOD']) == 'POST';
}

function is_login() {
    return isset($_SESSION['user_id']);
}

function get_loginpage($default=null) {
    $loginpage = Session::Get('loginpage', true);
    if ($loginpage)  return $loginpage;
    if ($default) return $default;
    return WEB_ROOT . '/index.php';
}

function cookie_city($city) {
    global $INI;
    if($city) {
        cookieset('city', $city['id']);
        return $city;
    }
    $city_id = cookieget('city');

    if (!$city_id) {
        $city = get_city();
        if (!$city) {
            $city = Table::Fetch('category', $INI['hotcity'][0]);
        }
        if ($city) cookie_city($city);
        return $city;
    } else {
        if (in_array($city_id, $INI['hotcity'])) {
            return Table::Fetch('category', $city_id);
        }
        $city = Table::Fetch('category', $INI['hotcity'][0]);
    }
    return $city;
}

function cookie_group($group) {
    global $INI;
    if($group) {
        cookieset('group', $group['id']);
        return $group;
    }
    $group_id = intval(cookieget('group'));

    if (!$group_id) {
        $group = get_group();
        if (!$group) {
            $group = Table::Fetch('category', $group_id);
        }
        if ($group) cookie_group($group);
        return $group;
    } else {
        $group = Table::Fetch('category', $group_id);
    }
    return $group;
}
function cookie_type($group) {
    global $INI;
    if($group) {
        cookieset('type', $group);
        return $group;
    }
    $group_id = intval(cookieget('type'));
    return $group_id;
}


function cookieset($k, $v, $expire=0) {
    $pre = substr(md5($_SERVER['HTTP_HOST']),0,4);
    $k = "{$pre}_{$k}";
    if ($expire==0) {
        $expire = time() + 365 * 86400;
    } else {
        $expire += time();
    }
    setCookie($k, $v, $expire, '/');
}

function cookieget($k) {
    $pre = substr(md5($_SERVER['HTTP_HOST']),0,4);
    $k = "{$pre}_{$k}";
    return strval($_COOKIE[$k]);
}

function moneyit($k) {
    return rtrim(rtrim(sprintf('%.2f',$k), '0'), '.');
}

function debug($v, $e=false) {
    global $login_user_id;
    if ($login_user_id==100000) {
        echo "<pre>";
        var_dump( $v);
        if($e) exit;
    }
}

function getparam($index=0, $default=0) {
    if (is_numeric($default)) {
        $v = abs(intval($_GET['param'][$index]));
    } else $v = strval($_GET['param'][$index]);
    return $v ? $v : $default;
}
function getpage() {
    $c = abs(intval($_GET['page']));
    return $c ? $c : 1;
}
function pagestring($count, $pagesize) {
    $p = new Pager($count, $pagesize, 'page');
    return array($pagesize, $p->offset, $p->genBasic());
}

function uencode($u) {
    return base64_encode(urlEncode($u));
}
function udecode($u) {
    return urlDecode(base64_decode($u));
}
/* facebook share link */
function share_facebook($team) {
    global $login_user_id;
    global $INI;
    if ($team)  {
        $query = array(
                'u' => $INI['system']['wwwprefix'] . "/team.php?id={$team['id']}",
                't' => $team['title'],
                );
    }
    else {
        $query = array(
                'u' => $INI['system']['wwwprefix'] . "/team.php?id={$team['id']}",
                't' => $team['title'],
                );
    }

    $query = http_build_query($query);
    return 'http://www.facebook.com/sharer.php?'.$query;
}


/* share link  with refferals commented by [go6o]  facebook sucks! fb sharer sucks too!
function share_facebook($team) {
    global $login_user_id;
    global $INI;
    //Ui4o must be corrected!!!
    if(false) {
        //Ui4o must be corrected!!!
        //if ($team) {
        $query = array(
                'u' => $INI['system']['wwwprefix'] . "/team.php?id={$team['id']}&r={$login_user_id}",
                't' => $team['title'],
        );
    }
    else {

        $query = array(
                'u' => $INI['system']['wwwprefix'] . "/r.php?r={$login_user_id}",
                't' => $team['title'],
                //     't' => "trun!!!",
        );
    }

    $query = http_build_query($query);
    return 'http://www.facebook.com/sharer.php?'.$query;
}
*/
//go6o facebook custom function no referals from main page
function share_facebook_no_referals($team, $share) {
    global $login_user_id;
    global $INI;
    //$flag4e = $share
    if($share===1) {
    //if(true){
        $query = array(
                'u' => $INI['system']['wwwprefix'] . "/r.php?r={$login_user_id}",);
        
    }else {
        $query =array('u'=> "www.skidkoman.com.ua",);

    }
    $query = http_build_query($query);
    return 'http://www.facebook.com/sharer.php?'.$query;

}
//go6o facebook custom function no referals from main page




/* twitter */
function share_twitter($team) {
    global $login_user_id;
    global $INI;
    if ($team) {
        $query = array(
                'status' => $INI['system']['wwwprefix'] . "/r.php?r={$login_user_id}" . ' ' . $team['title'],
        );
    }
    else {
        $query = array(
                'status' => $INI['system']['wwwprefix'] . "/r.php?r={$login_user_id}" . ' ' . $INI['system']['sitename'] . '(' .$INI['system']['wwwprefix']. ')',
        );
    }

    $query = http_build_query($query);
    return 'http://twitter.com/?'.$query;
}

//custom twitter no referals for main page [go6o]
function sharee_twitter($team) {
    global $login_user_id;
    global $INI;
    if ($team) {
        $query = array(
                'status' => $INI['system']['wwwprefix'] . "/team.php?id={$team['id']}" . ' ' . $team['title'],
        );
    }
    else {
        $query = array(
                'status' => $INI['system']['wwwprefix'] . "/team.php?id={$team['id']}" . ' ' . $INI['system']['sitename'] . '(' .$INI['system']['wwwprefix']. ')',
        );
    }

    $query = http_build_query($query);
    return 'http://twitter.com/?'.$query;
}
//end custom twitter no referals [go6o]


function share_mail($team) {
    global $login_user_id;
    global $INI;
    if (!$team) {
        $team = array(
                'title' => $INI['system']['sitename'] . '(' . $INI['system']['wwwprefix'] . ')',
        );
    }
    // да се провери дали е така!
    $pre[] = "{$INI['system']['sitename']} - Покупай выгодно! Самые лучшие предложения в городе {$city['name']}";
    if ( $team['id'] ) {
        $pre[] = "Днешна оферта: {$team['title']}";
        $pre[] = $INI['system']['wwwprefix'] . "/team.php?id={$team['id']}&r={$login_user_id}";
        $pre = mb_convert_encoding(join("\n\n", $pre), 'UTF-8', 'UTF-8');
        $sub = "Интересува ли Ви: {$team['title']}";
    } else {
        $sub = $pre[] = $team['title'];
    }
    $sub = mb_convert_encoding($sub, 'UTF-8', 'UTF-8');
    $query = array( 'subject' => $sub, 'body' => $pre, );
    $query = http_build_query($query);
    return 'mailto:?'.$query;
}

function domainit($url) {
    if(strpos($url,'//')) {
        preg_match('#[//]([^/]+)#', $url, $m);
    } else {
        preg_match('#[//]?([^/]+)#', $url, $m);
    }
    return $m[1];
}

// that the recursive feature on mkdir() is broken with PHP 5.0.4 for [go6o] 
function RecursiveMkdir($path) {
    if (!file_exists($path)) {
        RecursiveMkdir(dirname($path));
        @mkdir($path, 0777);
    }
}

function upload_image($inputname, $image=null, $type='team', $width=440) {
    $year = date('Y');
    $day = date('md');
    $n = time().rand(1000,9999).'.jpg';
    $z = $_FILES[$inputname];
    if ($z && strpos($z['type'], 'image')===0 && $z['error']==0) {
        if (!$image) {
            RecursiveMkdir( IMG_ROOT . '/' . "{$type}/{$year}/{$day}" );
            $image = "{$type}/{$year}/{$day}/{$n}";
            $path = IMG_ROOT . '/' . $image;
        } else {
            RecursiveMkdir( dirname(IMG_ROOT .'/' .$image) );
            $path = IMG_ROOT . '/' .$image;
        }
        if ($type=='user') {
            Image::Convert($z['tmp_name'], $path, 48, 48, Image::MODE_CUT);
        }
        else if($type=='team') {
            move_uploaded_file($z['tmp_name'], $path);
        }
        return $image;
    }
    return $image;
}

function user_image($image=null) {
    global $INI;
    if (!$image) {
        return $INI['system']['imgprefix'] . '/static/img/user-no-avatar.gif';
    }
    return $INI['system']['imgprefix'] . '/static/' .$image;
}

function team_image($image=null) {
    global $INI;
    if (!$image) return null;
    return $INI['system']['imgprefix'] . '/static/' .$image;
}

function userreview($content) {
    $line = preg_split("/[\n\r]+/", $content, -1, PREG_SPLIT_NO_EMPTY);
    $r = '<ul>';
    foreach($line AS $one) {
        $c = explode('|', htmlspecialchars($one));
        $c[2] = $c[2] ? $c[2] : '/';
        $r .= "<li>{$c[0]}<span>--<a href=\"{$c[2]}\" target=\"_blank\">{$c[1]}</a>";
        $r .= ($c[3] ? "({$c[3]})":'') . "</span></li>\n";
    }
    return $r.'</ul>';
}

function team_state(&$team) {
    $team['close_time'] = 0;
    if ( $team['now_number'] >= $team['min_number'] ) {
        if ($team['max_number']>0) {
            if ( $team['now_number']>=$team['max_number'] ) {
                if ($team['close_time']==0) {
                    $team['close_time'] = $team['end_time'];
                }
                return $team['state'] = 'soldout';
            }
        }
        if ( $team['end_time'] <= time() ) {
            $team['close_time'] = $team['end_time'];
        }
        return $team['state'] = 'success';
    } else {
        if ( $team['end_time'] <= time() ) {
            $team['close_time'] = $team['end_time'];
            return $team['state'] = 'failure';
        }
    }
    return $team['state'] = 'none';
}

function current_team($city_id=0) {
    $today = strtotime(date('Y-m-d'));
    settype($city_id, 'array');
    $city_id[] = 0;
    $cond = array(
            'city_id' => $city_id,
            "begin_time <= {$today}",
            "end_time > {$today}",
    );
    $team = DB::LimitQuery('team', array(
            'condition' => $cond,
            'one' => true,
            'order' => 'ORDER BY city_id DESC,begin_time DESC,id DESC',
    ));
    return $team;
}

function state_explain($team, $error='false') {
    $state = team_state($team);
    $state = strtolower($state);
    switch($state) {
        case 'none': return 'активная';
        case 'soldout': return 'продана';
        case 'failure': if($error) return 'неактивная';
        case 'success': return 'активная';
        default: return 'законченная';
    }
}

function get_zones($zone=null) {
    $zones = array(
            'city' => 'Город',
            'group' => 'Предложения',
            'public' => 'Бюллетень',
            'grade' => 'Права!',
            
    );
    if ( !$zone ) return $zones;
    if (!in_array($zone, array_keys($zones))) {
        $zone = 'city';
    }
    return array($zone, $zones[$zone]);
}

function down_xls($data, $keynames, $name='dataxls') {
    //go6o edited xls columns first line not working, не трябва




    $xls[] = "<html><meta http-equiv=content-type content=\"text/html;
        charset=UTF-8\"><body><table border='1'>".implode(" ", array_values($keynames));
    foreach($data As $o) {
        $line = array();
        foreach($keynames AS $k=>$v) {
            $line[] = "<td>".$o[$k]."</td>";
        }
        $xls[] = "<tr>".implode(" ", $line)."</tr>";
    }
    $xls = join(" ", $xls)."</table></body></html>";
    header("Content-type:application/xls");
    header('Content-Disposition: attachment; filename="'.$name.'.xls"');
    die(mb_convert_encoding($xls,'UTF-8','UTF-8'));
    // die(mb_convert_encoding($xls,'windows-1251','windows-1251'));

    //go6o edited xls columns first line not working
}

function option_category($zone='city', $force=false) {
    $cache = $force ? 0 : 86400*30;
    $cates = DB::LimitQuery('category', array(
            'condition' => array( 'zone' => $zone, ),
            'cache' => $cache,
    ));
    return Utility::OptionArray($cates, 'id', 'name');
}

function getCK($par,$value='',$full=true,$height="200")
{
    echo '<textarea name="'.$par.'">'.$value.'</textarea>
    <script>
        CKEDITOR.replace( \''.$par.'\',
        {
            '.($full?
            "extraPlugins : 'uicolor',
    language: 'ru',
    toolbar :
    [
        ['Source'],['Cut','Copy','Paste','PasteText','PasteFromWord'], [ 'Find','Replace','-','SelectAll'] , ['Undo','Redo'], [ 'Bold', 'Italic', 'Underline','Strike','Subscript','Superscript','-','RemoveFormat', '-','NumberedList', 'BulletedList','Outdent','Indent'], ['Link', 'Unlink', 'Image','File','Table' ],'/', ['Styles','Format','Font','FontSize','TextColor','BGColor'],['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','RemoveFormat'],['Maximize','ShowBlocks'], ['Templates'],
    ],
    height: $height,
    resize_enabled:false,
    //enterMode:CKEDITOR.ENTER_BR,
    filebrowserBrowseUrl : '/ck/ckfinder/ckfinder.html',
    filebrowserImageBrowseUrl : '/ck/ckfinder/ckfinder.html?Type=Images',
    filebrowserFlashBrowseUrl : '/ck/ckfinder/ckfinder.html?Type=Flash',
    filebrowserUploadUrl : '/ck/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
    filebrowserImageUploadUrl : '/ck/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
    filebrowserFlashUploadUrl : '/ck/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash',
    filebrowserWindowWidth : '1000',
    filebrowserWindowHeight : '700'":
    "extraPlugins : 'uicolor',
    language: 'ru',
    toolbar :
    [
        ['Source'],['Cut','Copy','Paste','PasteText','PasteFromWord'], ['Undo','Redo','RemoveFormat'], [ 'Bold', 'Italic', 'Underline','Strike'],['Maximize'],
    ],
    height: 150,
    resize_enabled:false,
    //enterMode:CKEDITOR.ENTER_BR,").'
        });
    </script>';
}
