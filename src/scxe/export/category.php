<?php

getCategories();

function getCategories() {
    global $db;
    //var_dump($db);exit;
    $scoc_utility = new scoc_utility();
    $scoc_encoder = new scoc_encoder();
    $scoc_utility->printHeader();

    $qtrStrArray = $scoc_encoder->authenticate(TRUE, FALSE);
    if ($qtrStrArray == NULL) {
	unset($scoc_utility, $scoc_encoder);
	return;
    }

    $output = '<?xml version="1.0" encoding="UTF-8" ?>';
    $output .= '<categories>';
    $output .= '<channel>';
    $output .= '<link>' . '' . '</link>';
    $output .= '</channel>';

    $categories_tab_query = "select c.categories_id, c.parent_id, cd.categories_name, cd.categories_description from " .
	    TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id=cd.categories_id and cd.language_id='" . 1 . "' and c.categories_status='1'" .
	    " order by c.sort_order, cd.categories_name ";

    $categories_tab = $db->Execute($categories_tab_query);
    //var_dump($categories_tab);exit;

    if ($categories_tab) {
	while (!$categories_tab->EOF):

	    if (TRUE):

		$output .= '<category>';
		$output .= '<id>' . $categories_tab->fields['categories_id'] . '</id>';
		$output .= '<name><![CDATA[' . $categories_tab->fields['categories_name'] . ']]></name>';
		$output .= '<description><![CDATA[' . $categories_tab->fields['categories_description'] . ']]></description>';
		$output .= '<parent_id>' . $categories_tab->fields['parent_id'] . '</parent_id>';

		$output .= '</category>';
	    endif;
	    $categories_tab->MoveNext();
	endwhile;
    }
    $output .= '</categories>';

    $scoc_utility->printHeader();
    echo $output;
    unset($scoc_utility);
    unset($categories_tab);
}

exit;
?>
