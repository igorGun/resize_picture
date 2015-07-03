<?php include template("header");?>
<style type="text/css">
<!--
.style1 {font-size: 12px}
.det {display:none !important}
-->
</style>

<div class="content_cart">
  

      <?php 
	  import('countdown');
	  include template("main_box");

		?>
      <!-- <div id="sidebar" style="margin-top:-15px" >  
      <div class="sep"></div>
       <img src="/static/theme/green/css/i/how.png" width="212" height="420" title="" /> 
	</div> -->
        <!--<div class="boxwhite" style="width:875px">
		<div id="side" style="width:850px">-->
	<div class="content_cart_left">
		<div id="side-business" >
        <?php if(trim(strip_tags($team['detail']))){?>
						<h1>Условия</h1>
						 <?php $tDetail = $team['detail']; $team['detail'] = preg_replace('/(<a[^>]*)\/?>/', '$1 target="_blank" >', $tDetail);  ?>
						<div><?php echo $team['detail']; ?></div>
					<?php }?>
                    
                    
         </div>
         <div id="side-business" >
         <?php if(trim(strip_tags($team['notice']))){?>
						<h1>Особенности</h1>
						<div><?php echo $team['notice']; ?></div>
					<?php }?>
		
		</div>
		<div id="side-business" >
					<?php if(trim(strip_tags($team['systemreview']))){?>
						<h1>Информация</h1><br>
						<div><?php echo $team['systemreview']; ?></div>
					<?php }?>
									
							
		</div>
        <div id="side-business" >
					
						<h1>Контакты</h1><br>
						<div>
                       <strong><?php echo stripslashes($partner['title']); ?></strong><br />
													  
							<?php echo $partner['location']; ?><br />
							<? if ($partner['metro']!=''):?><?php echo $partner['metro']; ?><br /><? endif;?>
							<?php echo $partner['phone']; ?><br />
							<a href="<?php if($partner['show_another'] == 1) {echo $partner['another_site'];} else {echo $partner['homepage'];} ?>" target="_blank"><?php echo domainit($partner['homepage']); ?></a><br />
                        </div>
					
									
							
		</div>
        <div id="side-business" >
        	<div>
				<?php echo $partner['other']; ?>
            </div>
        </div>
			
		
	</div>
    <div class="content_cart_right">
    <?php

$d_table_team=mysql_query("SELECT * FROM `team` WHERE `group_id` = '$team[group_id]' AND `id` not like '$team[id]' AND `end_time` > '".time()."' ORDER BY `now_number` DESC LIMIT 3"); // формирование информации из таблицы
while($stroka=mysql_fetch_array($d_table_team)) // перебор строк таблицы с начала до конца
{
	$all_days = DB::GetQueryResult('SELECT team_id, SUM(quantity) as q FROM `order` where `team_id` = '.$stroka['id'].' GROUP BY team_id', false); 
	$all_days = origCount($all_days);

	?>
    <div class="index_small">
    <div class="small_top">
        	<a href="/<?php echo $stroka['id'].'_'.$stroka['alias']; ?>"><img src="<?php if(1==preg_match('/http:/',$stroka['image']))echo $stroka['image']; else echo '/static/'.$stroka["image"]; ?>" alt="<?=$stroka['title']; ?>" width="380" border="0" height="213"/></a>
    </div>
    
    
    
    
    
    
    <div class="small_bottom">
        	<div class="small_bottom_txt">
            <?=$stroka['title'];?>
            </div>
            <div class="small_bottom_time" diff="<?php echo $diff_time; ?>000">
             <?php //countdown($team['end_time']); ?>
                    <span id="counter"><span class="date" rel="<?php echo ($stroka['end_time']); ?>"><?php countdown($stroka['end_time']); ?></span></span>
            </div>
            <div class="small_bottom_kupon">
            <?php echo $all_days[$stroka['id']] ? $all_days[$stroka['id']]['q'] : 0; ?>
            </div>
            
            <a href="<?php if(strlen($stroka['btn_address'])>3) echo $stroka['btn_address']; else echo $team['id'].'_'.$team['alias']; ?>" class="knopka_ok"><?=(moneyit($stroka['team_comission'])!=0?'Купить':'Получить')?></a>
     </div></div><?
}
?>
   </div> 
  
</div>

</div>
<div class="clear"></div>
<?php include template("footer");?>
