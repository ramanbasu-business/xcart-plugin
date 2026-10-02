<?php

getProducts();

function getProducts()
{
    global $db;
    $scoc_lib = new scoc_lib();
    $scoc_utility = new scoc_utility();
    $scoc_encoder = new scoc_encoder();
    //

    $qtrStrArray = $scoc_encoder->authenticate(true, false);
    if ($qtrStrArray == null) {
        unset($scoc_utility, $scoc_encoder);
        return;
    }

    $sku = isset($_GET['sku']) ? $_GET['sku'] : null;
    $currentPageIndex = isset($_GET['currentpageindex']) ? (int) $_GET['currentpageindex'] : 1;
    $currentPageIndex--;
    $rowsPerPage = isset($_GET['rowsperpage']) ? $_GET['rowsperpage'] : 10;

    $output = '<?xml version="1.0" encoding="UTF-8" ?>';
    $output .= '<products>';
    $output .= '<channel>';
    $output .= '<link>' . '' . '</link>';
    $output .= '</channel>';

    $splitter = $scoc_lib->getProducts($currentPageIndex, $rowsPerPage, $sku);
    //var_dump($splitter);    exit;
    $cDataHeaders = array("products_model", "manufacturers_name", "products_name",
        "products_description", "products_gpc");

    $recordCount = $splitter->number_of_rows;
    $totalPages = (int) ($recordCount / $rowsPerPage) + ((int) ($recordCount % $rowsPerPage) > 0 ? 1 : 0);
    $output .= '<recordcount>' . ($recordCount) . '</recordcount>';
    $output .= '<currentpageindex>' . ($currentPageIndex + 1) . '</currentpageindex>';
    $output .= '<totalpages>' . $totalPages . '</totalpages>';

    $products = $db->Execute($splitter->sql_query);

    while (!$products->EOF) :
        $product_id = $products->fields["products_id"];
        $output .= '<product>';
        //var_dump($products);
        foreach ($products->fields as $key => $value) {
            //echo $key;
            if (in_array($key, $cDataHeaders)) {
                $output .= '<' . (string) $key . ' lang="en"><![CDATA[' . $value . ']]></' . $key . '>';
            }
            else {
                if ($key == 'products_image') {
                    $output .= '<products_image>' . HTTP_SERVER . DIR_WS_HTTPS_CATALOG . DIR_WS_IMAGES . $value . '</products_image>';
                }
                else {
                    $output .= '<' . $key . '>' . $value . '</' . $key . '>';
                }
            }
        }

        if ($terms) {
            $output .= '<categories>';

            $output .= '</categories>';
        }
        //echo '<li><a href="' . get_permalink() . '">' . get_the_post_thumbnail($loop->post->ID, 'thumbnail') . '</a></li>';
        $output .= '</product>';
        $products->MoveNext();
    endwhile;

    $output .= '</products>';

    $scoc_utility->printHeader();
    echo $output;
    unset($scoc_utility);
}

exit;
