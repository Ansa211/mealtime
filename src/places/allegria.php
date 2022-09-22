
<?php
function allegria() {
    $date=date('d-m-Y');
    $pageRaw = getURL('https://pizzeriaallegria.cz/');
    // Sanitize HTML
    $pageRaw = str_replace(array("\r", "\n"), '', $pageRaw);

    $page = new DOMDocument();
    @$page->loadHTML($pageRaw);

    // $content = $page->getElementById('site-main')->C14N();
    //$menuClean = $content;

    $finder = new DomXPath($page);
    $nodes = $finder->query("//div[@id='poledni-menu']//div[@class='container']");
    $menuClean =$nodes[0]->C14N();

    
    $menuClean = preg_replace('@<h2.*<\/h2>@', "", $menuClean);
    $menuClean = preg_replace('@<h3.*<\/h3>@', "", $menuClean);
    $menuClean = preg_replace('@<h4.*<\/h4>@', "", $menuClean);
    $menuClean = preg_replace('@(</strong>)(\s*<br>)*@', "$1", $menuClean);
    //$menuClean = preg_replace('@(<\/div>)@', "$1<br>", $menuClean);

    return $menuClean;
}
?>
