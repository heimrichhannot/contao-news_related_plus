<?php

namespace HeimrichHannot\NewsRelatedPlus;

class NewsRelatedPlus extends \Frontend
{

    public function strClass()
    {
        $strStringClass = version_compare(VERSION . '.' . BUILD, '3.5.1', '<') ? '\String' : '\StringUtil';

        return $strStringClass;
    }


    public function addRelatedNews($objTemplate, $arrNews, $objModule)
    {
        switch ($objModule->type)
        {
            case 'newslist':
            case 'newslist_plus':
                $strStringClass = $this->strClass();

                // Limit
                if ($objModule->related_numberOfItems >= 0 && $objModule->related_numberOfItems <= 50)
                {
                    $limit = $objModule->related_numberOfItems;
                }
                else
                {
                    $limit = 4;
                }

                if ($limit < 0)
                {
                    return '';
                }

                $this->import('NewsRelatedHelper', 'Helper');
                $strTmp = \Input::get('items');
                \Input::setGet('items', $arrNews['alias']);
                $objArticle = $this->Helper->getRelated($objModule->news_archives, $objModule->related_match, $objModule->related_priority, $limit);
                \Input::setGet('items', $strTmp);

                if ($objArticle)
                {
                    // Get page
                    global $objPage;

                    // Defaults
                    $arrArticles = [];
                    while ($objArticle->next())
                    {
                        $arrPic = '';

                        // Add an image
                        if ($objArticle->addImage && $objArticle->singleSRC != '')
                        {
                            $objModel = \FilesModel::findByUuid($objArticle->singleSRC);

                            if ($objModel === null)
                            {
                                if (!\Validator::isUuid($objArticle->singleSRC))
                                {
                                    $objTemplate->text = '<p class="error">' . $GLOBALS['TL_LANG']['ERR']['version2format'] . '</p>';
                                }
                            }
                            elseif (is_file(TL_ROOT . '/' . $objModel->path))
                            {
                                // Do not override the field now that we have a model registry (see #6303)
                                $arrArticle = $objArticle->row();

                                // Override the default image size
                                if ($objTemplate->thumbSize != '')
                                {
                                    $size = deserialize($objTemplate->thumbSize);

                                    if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]))
                                    {
                                        $arrArticle['size'] = $objTemplate->thumbSize;
                                    }
                                }

                                $arrArticle['singleSRC'] = $objModel->path;
                                $this->addImageToTemplate($objTemplate, $arrArticle);

                                $arrPic = [
                                    'picture'  => $objTemplate->picture,
                                    'alt'      => $objArticle->alt,
                                    'fullsize' => $objArticle->fullsize,
                                    'caption'  => $objArticle->caption
                                ];
                            }
                        }

                        // Shorten the teaser
                        $teaser = strip_tags($objArticle->teaser, ['<strong>', '<a>']);
                        if (strlen($teaser) > 120)
                        {
                            $teaser = $strStringClass::substrHtml($teaser, 120) . '...';
                        }

                        $objArchive =
                            $this->Database->prepare("SELECT tstamp, title, jumpTo FROM tl_news_archive WHERE id=?")->execute($objArticle->pid);

                        if (($objTarget = \PageModel::findByPk($objArchive->jumpTo)) !== null)
                        {
                            $url = ampersand(
                                $this->generateFrontendUrl(
                                    $objTarget->row(),
                                    ((isset($GLOBALS['TL_CONFIG']['useAutoItem'])
                                      && $GLOBALS['TL_CONFIG']['useAutoItem']) ? '/' : '/items/') . ((!$GLOBALS['TL_CONFIG']['disableAlias']
                                                                                                      && $objArticle->alias
                                                                                                         != '') ? $objArticle->alias : $objArticle->id)
                                )
                            );
                        }

                        $title = specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objArticle->headline), true);

                        $arrMeta = $this->getMetaFields($objArticle);

                        $more = sprintf(
                            '<a href="%s" title="%s">%s%s</a>',
                            $url,
                            $title,
                            $GLOBALS['TL_LANG']['MSC']['more'],
                            ($blnIsReadMore ? ' <span class="invisible">' . $objArticle->headlines . '</span>' : '')
                        );

                        //Newsdaten hinzufügen
                        $arrArticles[] = [
                            'headline'         => $objArticle->headline,
                            'id'               => $objArticle->id,
                            'subheadline'      => $objArticle->subheadline,
                            'teaser'           => $teaser,
                            'more'             => $more,
                            'image'            => $arrPic,
                            'url'              => $url,
                            'title'            => $title,
                            'numberOfComments' => $arrMeta['ccount'],
                            'commentCount'     => $arrMeta['comments'],
                            'date'             => $arrMeta['date'],
                            'timestamp'        => $objArticle->date,
                            'datetime'         => date('Y-m-d\TH:i:sP', $objArticle->date)
                        ];
                    }

                    // assign articles
                    $objTemplate->info             = $GLOBALS['TL_LANG']['MSC']['related_info'];
                    $objTemplate->related_headline = $GLOBALS['TL_LANG']['MSC']['related_headline'];
                    if (!empty($arrArticles) && is_array($arrArticles))
                    {
                        $objTemplate->newsRelated = $arrArticles;
                    }
                    else
                    {
                        return '';
                    }
                }
                break;
        }
    }

    /**
     * Return the meta fields of a news article as array
     *
     * @param object
     *
     * @return array
     */
    protected function getMetaFields($objArticle)
    {
        global $objPage;
        $return = [];

        $return['date'] = \Date::parse($objPage->datimFormat, $objArticle->date);

        $intTotal           = \CommentsModel::countPublishedBySourceAndParent('tl_news', $objArticle->id);
        $return['ccount']   = $intTotal;
        $return['comments'] = sprintf($GLOBALS['TL_LANG']['MSC']['commentCount'], $intTotal);

        return $return;
    }
}
