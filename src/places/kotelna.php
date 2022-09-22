<?php
function kotelna() {
    $pageRaw = getURL('https://restauracekotelna.cz/');
    // Sanitize HTML
    $pageRaw = str_replace(array("\r", "\n"), '', $pageRaw);
    $page = new DOMDocument();
    @$page->loadHTML($pageRaw);
    

    $finder = new DomXPath($page);
    $nodes = $finder->query("//div[@id='todayspecial']//div[@class='toggle-container']//div[@class='toggle-content']");

    $dayOfWeek = (date('w')-1 + 7) % 7;
    $menuClean =$nodes[$dayOfWeek]->C14N();
    
    $menuClean = strip_tags($menuClean);
    $menuClean = preg_replace("/(\d*,-)/", "\n$1", $menuClean);
    $menuClean = preg_replace("/(\d*,-)\s*(.*)/", "$2&nbsp;&nbsp;&nbsp;$1", $menuClean);

    return $menuClean;
}
?>
