<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @package   news_related
 * @author    Fast & Media | Christian Schmidt <info@fast-end-media.de>
 * @license   LGPL
 * @copyright Fast & Media 2013-2017 <http://www.fast-end-media.de>
 */


/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace NewsRelated;

class NewsRelatedHelper extends \Frontend
{

	public function getRelated($news_archives, $related_match, $related_priority, $limit)
	{

    // Defaults
    $arrTags = array();

		if(\Input::get('auto_item')) { $news_alias = \Input::get('auto_item'); }
		else { $news_alias = \Input::get('items'); }

    $tags = true;
    $archive = true;

		// Check if extension 'news_categories' is installed
		if (in_array('news_categories', $this->Config->getActiveModules()))
		{
			$extension = true;
      $category = true;
		}
		else {
      $extension = false;
			$category = false;
		}

		// Parent id and categories of current news
    $objPid = $this->Database->prepare("SELECT pid".($extension ? ",categories" : "")." FROM tl_news WHERE id=? OR alias=?")->execute($news_alias,$news_alias);
		$pid = $objPid->pid;

    $newsID = \NewsModel::findPublishedByParentAndIdOrAlias($news_alias, $news_archives)->id;

		// Quellen festlegen
		$arrMatch = deserialize($related_match);

    if($arrMatch)
		{
			if(!in_array('tags', $arrMatch))
			{
        $tags = false;
			}

			if(!in_array('category', $arrMatch))
			{
        $category = false;
			}

			if(!in_array('archive', $arrMatch))
			{
        $archive = false;
			}
		}

		if($tags) {
	    // Tags of current news
	     $objTags = $this->Database->prepare("SELECT t.tag,n.id AS newsid FROM tl_tag t LEFT JOIN tl_news n ON n.id=? WHERE t.tid = n.id AND t.from_table=?")->execute($newsID,'tl_news');

	    while($objTags->next()) {
	      $arrTags[] = $objTags->tag;
	    }
			if(!$arrTags) { $tags = false; }
		}

		$time = time();

		// Change sql select if extension is installed
		if($extension && $category) {
			$categories = deserialize($objPid->categories);
			if(empty($categories) || !is_array($categories)) { $category = false; }
		}

		// Sorting by priority
    if($related_priority == 'random')
		{
			$order1 = 'rand()';
			$order2 = 'rand()';
		}
		elseif($related_priority == 'date')
		{
			$order1 = 'n.date DESC';
			$order2 = 'n.date DESC';
		}
		elseif($related_priority == 'comments')
		{
			$order1 = 'count_comments DESC, count_tags DESC, n.date DESC';
			$order2 = 'count_comments DESC, n.date DESC';
			$join_comments = true;
		}
		elseif($related_priority == 'relevance' || !$related_priority)
		{
			$order1 = 'count_tags DESC, n.date DESC';
			$order2 = 'n.date DESC';
		}

		if($tags || $category || $archive)
		{
	    //1. Select all news with same tags | 2. (Optional) Select all news with same category | 3. Read all news with same archive
	    $objArticles = $this->Database->execute("
			SELECT * FROM
			(
        " . ($tags ? "
				(
					SELECT n.*, count(t.id) AS count_tags, 1 AS type ".($join_comments ? ", count(com.id) AS count_comments" : "")."
					FROM tl_news n
					LEFT JOIN tl_tag t
						ON n.id = t.tid
					".($join_comments ? "LEFT JOIN tl_comments com ON n.id = com.parent" : "")."
					WHERE n.pid IN(" . implode(',', array_map('intval', $news_archives)) . ")
						AND (t.tag IN('" . implode("','", $arrTags) . "'))
						".($join_comments ? "AND com.source = 'tl_news'" : "")."
						AND n.id!='$newsID'
						".(!BE_USER_LOGGED_IN ? "	AND (n.start = '' OR n.start < '$time') AND (n.stop = '' OR n.stop > '$time') AND n.published = 1" : "")."
					GROUP BY n.id
					ORDER BY $order1
					LIMIT $limit
				)
				" : "") . "
        " . ($tags && $category ? "
				UNION
				" : "") . "
        " . ($category ? "
				(
					SELECT n.*, 0 AS count_tags, 2 AS type ".($join_comments ? ", count(com.id) AS count_comments" : "")."
					FROM tl_news n
					LEFT JOIN tl_news_categories c
						ON n.id = c.news_id
					".($join_comments ? "LEFT JOIN tl_comments com ON n.id = com.parent" : "")."
					WHERE n.pid IN(" . implode(',', array_map('intval', $news_archives)) . ")
						AND c.category_id IN(" . implode(',', array_map('intval', $categories)) . ")
            ".($join_comments ? "AND com.source = 'tl_news'" : "")."
						AND n.id!='$newsID'
						".(!BE_USER_LOGGED_IN ? "	AND (n.start = '' OR n.start < '$time') AND (n.stop = '' OR n.stop > '$time') AND n.published = 1" : "")."
					GROUP BY n.id
					ORDER BY $order2
					LIMIT $limit
				)
				" : "") . "
        " . (($tags || $category) && $archive ? "
				UNION
				" : "") . "
        " . ($archive ? "
				(
					SELECT n.*, 0 AS count_tags, 3 AS type ".($join_comments ? ", count(com.id) AS count_comments" : "")."
					FROM tl_news n
					".($join_comments ? "LEFT JOIN tl_comments com ON n.id = com.parent" : "")."
					WHERE n.pid='$pid'
	          ".($join_comments ? "AND com.source = 'tl_news'" : "")."
						AND n.id!='$newsID'
						".(!BE_USER_LOGGED_IN ? " AND (n.start = '' OR n.start < '$time') AND (n.stop = '' OR n.stop > '$time') AND n.published = 1" : "")."
					ORDER BY $order2
					LIMIT $limit
				)
				" : "") . "
			)
			AS n
			GROUP BY n.id
			ORDER BY n.type ASC,n.count_tags DESC,n.date DESC
			LIMIT $limit
			");

			return $objArticles;
		}
	}
}
