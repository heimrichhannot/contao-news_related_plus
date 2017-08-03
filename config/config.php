<?php

/**
 * Hooks
 */
if (TL_MODE == 'FE')
{
    $GLOBALS['TL_HOOKS']['parseArticles']['newsRelatedPlus_addRelatedNews'] = ['HeimrichHannot\NewsRelatedPlus\NewsRelatedPlus', 'addRelatedNews'];
}
