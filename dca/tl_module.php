<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_module'];

$arrDca['palettes']['newslist_plus'] = str_replace(
    ['{template_legend'],
    ['{related_legend},related_numberOfItems,related_match,related_priority,thumbSize;{template_legend'],
    $arrDca['palettes']['newslist_plus']
);