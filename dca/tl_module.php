<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_module'];

foreach (['newslist', 'newslist_plus', 'membernewslist'] as $strModule)
{
    $arrDca['palettes'][$strModule] = str_replace(
        ['{template_legend'],
        ['{related_legend},related_numberOfItems,related_match,related_priority,thumbSize;{template_legend'],
        $arrDca['palettes'][$strModule]
    );
}