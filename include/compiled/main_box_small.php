<div class="index_small">
    <div class="small_top">
        	<a href="/<?php echo $team['id'].'_'.$team['alias']; ?>"><img src="<?php if(1==preg_match('/http:/',$team['resize_image']))echo $team['resize_image']; 
            else echo '/static/'.$team["resize_image"]; ?>" alt="<?=$team['title']; ?>" width="380" border="0" height="213"/></a>
    </div>
    
    
    <!-- <a href="/team.php?id=<?php echo $team['id']; ?>"> -->
    
    
    
    <div class="small_bottom">
        	<div class="small_bottom_txt">
            <a href="/<?php echo $team['id'].'_'.$team['alias']; ?>"><?=$team['title'];?></a>
            </div>
            <div class="small_bottom_time" diff="<?php echo $diff_time; ?>000">
             <?php //countdown($team['end_time']); ?>
                    <span id="counter"><span class="date" rel="<?php echo ($team['end_time']); ?>"><?php countdown($team['end_time']); ?></span></span>
            </div>
            <div class="small_bottom_kupon">
            <?php echo $all_days[$team['id']] ? $all_days[$team['id']]['q'] : 0; ?>
            </div>
            
            <a href="/<?php echo $team['id'].'_'.$team['alias']; ?>" class="knopka_ok"><?=(moneyit($team['team_comission'])!=0?'Купить':'Получить')?></a>
     </div>
  
</div>