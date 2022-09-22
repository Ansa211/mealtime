<?php
function malinova() {
    $page = new DOMDocument();
    $pageRaw = getURL('http://restauracevmalinove.cz/denni-menu/');
    // Sanitize HTML
    $pageRaw = str_replace(array("\r", "\n"), '', $pageRaw);
    @$page->loadHTML($pageRaw);
    $content = $page->getElementById('tablepress-1')->C14N();

    $menuClean = $content;
    // $menuClean = preg_replace('/<\/p><p>/', "<br>", $menuClean);

    return $menuClean;
}
?>
