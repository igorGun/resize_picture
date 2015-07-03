<div class="index_small_recent">
    <div class="small_top">
            <a href="/<?php echo $one['id'].'_'.$one['alias']; ?>"><img src="<? if (stripos($one['image'],'http')===false):?>/static/<?=$one['image']; ?><?else:?><?=$one['image']; ?><?endif;?>" alt="<?=$one['title']; ?>" width="380" border="0" height="213"/></a>
    </div>
    
    <div class="small_bottom">
        <div class="small_bottom_txt">
        <a href="/<?php echo $one['id'].'_'.$one['alias']; ?>"><?=$one['title'];?></a>
        </div>
        
        <div class="recent_left">
            <div class="small_bottom_time">
                    <?php echo date("d.m.Y",$one['begin_time']); ?> - <?php echo date("d.m.Y",$one['end_time']); ?>
            </div>
            <div class="small_bottom_kupon">
                Получено купонов: <?=$one['now_number'];?>
            </div>
        </div>
        
        <div class="recent_right">
        
            <?=moneyit($one['team_comission'])."&nbsp".$currency;?><br />
            Завершено
        </div>
    </div>
        
        
    
</div>