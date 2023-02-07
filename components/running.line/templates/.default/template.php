<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if(!isset($arResult['ITEMS']) || empty($arResult['ITEMS'])) return;?>

<?CJSCore::Init('owl_carousel');?>
<section id="header-running_line" class="owl-carousel">
    <?foreach($arResult['ITEMS'] as $item):?>
        <div class="running_line-item">
            <?if(!empty($item['UF_LINK'])):?>
                <a href="<?=$item['UF_LINK']?>"<?if($item['UF_TARGET']) {?> target="_blank"<?}?> class="slide__link d-none d-md-inline"><?=htmlspecialchars($item['UF_TEXT'])?></a>
                <a href="<?=$item['UF_LINK']?>"<?if($item['UF_TARGET']) {?> target="_blank"<?}?> class="slide__link d-nline d-md-none"><?=htmlspecialchars($item['UF_TEXT_MOBILE'])?></a>
            <?else:?>
                <span class="d-none d-md-inline"><?=htmlspecialchars($item['UF_TEXT'])?></span>
                <span class="d-nline d-md-none"><?=htmlspecialchars($item['UF_TEXT_MOBILE'])?></span>
            <?endif;?>
        </div>
    <?endforeach;?>
</section>
