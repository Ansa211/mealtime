<?php
function topolova() {
    $page = new DOMDocument();
    $pageRaw = getURL('https://www.restauracetopolova.cz/denni-menu/');
    // Sanitize HTML
    $pageRaw = str_replace(array("\r", "\n"), '', $pageRaw);
    @$page->loadHTML($pageRaw);

    $content = $page->C14N();
    $menuClean = $content;

    $finder = new DomXPath($page);
    $nodes = $finder->query("//picture//img");
    $menuClean = $nodes[2]->C14N();
    $menuClean .= $nodes[3]->C14N();

    $menuClean = preg_replace('/<img([^>]*)width="\d*"/', '<img $1', $menuClean);
    $menuClean = preg_replace('/<img([^>]*)height="\d*"/', '<img $1 style="max-width: min(100vw,50vmax)";', $menuClean);


    return $menuClean;
}
?>
