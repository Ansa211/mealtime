
<?php
function usvehly() {
    $date=date('d-m-Y');
    $pageRaw = getURL('https://www.u-svehly.cz/denni-menu-'.$date);
    // Sanitize HTML
    $pageRaw = str_replace(array("\r", "\n"), '', $pageRaw);

    $page = new DOMDocument();
    @$page->loadHTML($pageRaw);

    // $content = $page->getElementById('site-main')->C14N();
    //$menuClean = $content;

    $finder = new DomXPath($page);
    $nodes = $finder->query("//div[@class='blog-post']//div[@class='entry-content']/table");
    $menuClean =$nodes[0]->C14N();

    
    $menuClean = preg_replace('/Denní jídelní lístek/', "", $menuClean);
    $menuClean = preg_replace('/Polední menu 11:00.*/', "</td></tr></tbody></table>", $menuClean);

    return $menuClean;
}
?>
