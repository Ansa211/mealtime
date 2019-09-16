<?php

/* Normalizes dayOfWeek to 0 - Mon, 6 - Sun
 * Add +7 because PHP mod is broken on negative numbers
 * -zouharvi Aug 25 2019
 */
$dayOfWeek = (date('w')-1 + 7) % 7;
$isWeekend = ($dayOfWeek > 5);
$dayOfWeek = min($dayOfWeek, 4);

function get($url, bool $use_include_path = FALSE, $context = NULL) {
    $cachePrefix = 'cache/'; // TODO: cache storage should be probably elsewhere (not accessible over HTTP)
    $fname = $cachePrefix . preg_replace("/[^a-zA-Z0-9]/", "_", $url);
    $maxAge = 5 * 60; // 5 minutes

    // Is it cached and recent?
    if (file_exists($fname) && filemtime($fname) + $maxAge > time()) {
        return file_get_contents($fname);
    }

    // Download and save to cache
    $response = file_get_contents($url, $use_include_path, $context);
    if (!is_dir($cachePrefix)) {
        mkdir($cachePrefix);
    }
    file_put_contents($fname, $response);
    return $response;
}

function ananta() {
    // ananta could follow suit of natureza (strip tags, lots of greps)
    global $dayOfWeek;

    $childIndex = array(1, 4, 7, 10, 13)[$dayOfWeek];
    $page = new DOMDocument();
    $pageRaw = get('http://www.anantasesa.cz/tydenni-menu');
    // Sanitize HTML
    $pageRaw = str_replace(array("\r", "\n"), '', $pageRaw);
    @$page->loadHTML($pageRaw);
    $content = $page->getElementById('content');

    $menu = $content->childNodes->item($childIndex)->C14N();
    $menu = str_replace(array("<br></br>", "<p>", "</p>"), array("\n", "", ""), $menu);
    return $menu;
}

// deprecated
function natureza_plain() {
    global $dayOfWeek;

    $childIndex = array(1, 4, 7, 10, 13)[$dayOfWeek];
    $page = new DOMDocument();
    $pageRaw = get('https://naturezaveget.cz/cs/o-nas');
    $pageRaw = strip_tags($pageRaw);
    $pageRaw = str_replace(array("\n", "\r", "\t"), '<br>', $pageRaw);
    $pageRaw = preg_replace('/(<br>)+/', "<br>", $pageRaw);

    // It is almost guaranteed that the <br>D<br> will break. For debugging
    // echo $pageRaw and see what the current situation looks like.
    // -zouharvi 23 Aug 2019

    $separators = array("Pondělí, \d+ [^\s]+", "Úterý, \d+ [^\s]+", "Středa, \d+ [^\s]+", "Čtvrtek, \d+ [^\s]+", "Pátek, \d+ [^\s]+", "D<br>");
    $sepA = $separators[$dayOfWeek];
    $sepB = $separators[$dayOfWeek+1];
    preg_match('/.*' . $sepA . '(\s|<br>)+(.*)<br>\s*' . $sepB . '.*/', $pageRaw, $menuDirty);
    $menuClean = preg_replace("/(<br>|\s|&nbsp;)+<br>/", "<br>", $menuDirty[2]);
    $menuClean = preg_replace("/<br>/", "\n", $menuClean);
    $menuClean = preg_replace("/[\n]+/", "\n", $menuClean);
    
    // Tmp hack to drop empty lines full of random white characters, \s does not match them
    $tmp = explode("\n", $menuClean);
    $result = '';
    foreach($tmp as $i) {
        if (strlen($i) < 150) {
            $result = $result . "\n" . $i;
        }
    }
    return $result;
}

function natureza() {
    $key = file_get_contents('zomato.key');
    $key = preg_replace('/\n$/', '', $key);

    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Accept: application/json\r\n" .
                        "user_key: " . $key
        ]
    ];
    $context = stream_context_create($opts);

    $menuJSON = get("https://developers.zomato.com/api/v2.1/dailymenu?res_id=16507635", false, $context); 
    $menu = json_decode($menuJSON)->daily_menus;
    if (count($menu) < 1) {
        return 'Not available';
    }
    $menu = $menu[0]->daily_menu->dishes;
    $dishes = array();
    foreach($menu as $dish) {
        $food = $dish->dish->name;
        $food = preg_replace('/\d\d+/', '', $food);
        array_push($dishes, $food);  
    }
    return implode("\n", $dishes);
}


function profdum() {
    /* Zomato requires API key, so here it is. This is actually quite sane were it not for the auth key
     * complications and the 1000 requests per day limits.
     * -zouharvi 23 Aug 2019
     */
    $key = file_get_contents('zomato.key');
    $key = preg_replace('/\n$/', '', $key);

    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Accept: application/json\r\n" .
                        "user_key: " . $key
        ]
    ];
    $context = stream_context_create($opts);

    $menuJSON = get("https://developers.zomato.com/api/v2.1/dailymenu?res_id=16506988", false, $context); 
    $menu = json_decode($menuJSON)->daily_menus;
    if (count($menu) < 1) {
        return 'Not available';
    }
    $menu = $menu[0]->daily_menu->dishes;
    $dishes = array();
    foreach($menu as $dish) {
        $food = $dish->dish->name;
        $food = preg_replace('/\d\d+/', '', $food);
        array_push($dishes, $food);  
    }
    return implode("\n", $dishes);
}

function profdum_plain() {
    // Broken for days other than mondays
    // not used anyway, since Zomato is somewhat more reliable
    global $dayOfWeek;
    
    $pageRaw = get("http://www.ms.mff.cuni.cz/profdum/jidelnicek.htm");
    $pageRaw = iconv('windows-1250', 'utf-8', $pageRaw);
    $pageRaw = strip_tags($pageRaw);
	$pageRaw = str_replace(array("\n", "\r"), "<br>", $pageRaw);
	$pageRaw = str_replace('&nbsp;', ' ', $pageRaw);
	$pageRaw = preg_replace('/\s+/', ' ', $pageRaw);

    $separators = array("Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "");
    $sepA = $separators[$dayOfWeek];
    $sepB = $separators[$dayOfWeek+1];
    preg_match('/.*' . $sepA . '.*<br><br> <br><br>(.*)<br><br> <br><br>.*' . $sepB . '.*/', $pageRaw, $menuDirty);

    $menuClean = $menuDirty[1];

    // Clean prices and weights
    $menuClean = preg_replace('/\d+g<br>/', "", $menuClean);
    // Collapse newlines
    $menuClean = preg_replace('/<br>(<br>|\s)+/', "<br>", $menuClean);
    // Drop prices
    $menuClean = preg_replace('/(<br>|\d|\.)(,|\.)-/', "\n", $menuClean);
    // Remove leading <br>s
    $menuClean = preg_replace('/<br>/', "", $menuClean);
    // Remove grs
    $menuClean = preg_replace('/gr/', "", $menuClean);
    // Drop digits
    $menuClean = preg_replace('/\d(\d|,)*/', "", $menuClean);
    // Drop leading space
    $menuClean = preg_replace('/^[^a-zA-Z]+/', "", $menuClean);
    $menuClean = preg_replace('/\n[^a-zA-Z]+/', "\n", $menuClean);

    return $menuClean;
}

function ferdinanda() {
    $pageRaw = get("http://www.ferdinanda.cz/cs/mala-strana/menu/denni-menu/main.html?ajax=1");
    $pageRaw = strip_tags($pageRaw);
    preg_match('/HLAVNÍ JÍDLA(.*)SALÁTY/', $pageRaw, $mainClean);
    $mainClean = $mainClean[1];
    // Entries end with the price
    $mainClean = preg_replace('/\d+&nbsp;Kč/', "\n", $mainClean);
    // Clean up weights
    $mainClean = preg_replace('/\d+g/', "", $mainClean);
    // Clean up leading spaces
    $mainClean = preg_replace('/^\s+/', "", $mainClean);
    // Clean up trailing spaces and newlines
    $mainClean = preg_replace('/[\s\n]+$/', "", $mainClean);
    return $mainClean;
}

function hamu() {
    global $dayOfWeek;
    
    $pageRaw = get("https://www.hamu.cz/cs/vse-o-fakulte/fakultni-kavarna/");
	$dom = new DomDocument();
    // The web is missing encoding header, so appends it manually.
    @$dom->loadHTML('<?xml encoding="utf-8" ? >' .  $pageRaw);
	$finder = new DomXPath($dom);
	$classname="wysiwyg";
	$nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
    
	$pageRaw = strip_tags($dom->saveHTML($nodes[0]));
	$pageRaw =str_replace(array("\n", "\r"), "<br>", $pageRaw);

    $separators = array("Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "");
    $sepA = $separators[$dayOfWeek];
    $sepB = $separators[$dayOfWeek+1];
    preg_match('/.*' . $sepA . '(.*)' . $sepB . '.*/', $pageRaw, $menuDirty);
    
    $menuClean = $menuDirty[1];
    // Clean prices and weights
    $menuClean = preg_replace('/\d+g<br>/', "", $menuClean);
    // Collapse newlines
    $menuClean = preg_replace('/<br>(<br>|\s)+/', "<br>", $menuClean);
    // Some special two-char symbols
    $menuClean = preg_replace('/<br>..<br>/', "", $menuClean);
    // Drop prices
    $menuClean = preg_replace('/(<br>|\d|\.)(,|\.)-/', "\n", $menuClean);
    // Remove leading <br>s
    $menuClean = preg_replace('/<br>/', "", $menuClean);
    // Drop digits
    $menuClean = preg_replace('/\d(\d|,)*/', "", $menuClean);
    // Drop leading space
    $menuClean = preg_replace('/^[^a-zA-Z]+/', "", $menuClean);
    // Drop lines without letters
    $menuClean = preg_replace('/^[^a-zA-Z]*$/', "", $menuClean);

    return $menuClean;
}

function carmelita() {
    global $dayOfWeek;
    
    $pageRaw = get("http://www.restauracecarmelita.cz/poledni-nabidka-1/");
	$dom = new DomDocument();
    // The web is missing encoding header, so appends it manually.
    @$dom->loadHTML('<?xml encoding="utf-8" ? >' .  $pageRaw);
	$finder = new DomXPath($dom);
	$nodes = $finder->query("//div[@id='content']");
    

	$pageRaw = strip_tags($dom->saveHTML($nodes[0]));
	$pageRaw = str_replace(array("\n", "\r"), "<br>", $pageRaw);

    $separators = array("Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "");
    $sepA = $separators[$dayOfWeek];
    $sepB = $separators[$dayOfWeek+1];
    preg_match('/.*' . $sepA . '(.*)' . $sepB . '.*/', $pageRaw, $menuDirty);
    
    $menuClean = $menuDirty[1];
    
    $menuClean = preg_replace('/\d+\.\d+\./', "", $menuClean);

    // Remove order ("1.", "2.", ...)
    $menuClean = preg_replace('/\d\. /', "", $menuClean);
    // Clean prices
    $menuClean = preg_replace('/\d+,- Kč<br>/', "", $menuClean);
    $menuClean = preg_replace('/\d+g/', "", $menuClean);
    // Collapse newlines
    $menuClean = preg_replace('/<br>(<br>|\s)+/', "<br>", $menuClean);
    // Drop leading whitespace
    $menuClean = preg_replace('/^ *<br>/', "", $menuClean);

    return $menuClean;
}

function cantina() {
    global $dayOfWeek;
    
    $baseURL = "http://www.restauracecantina.cz";

    // Get the daily menu link from the website menu Cantina changes the link (almost?) every week
    $pageRaw = get($baseURL . "/lang/en/");
	$dom = new DomDocument();
    // The web is missing encoding header, so appends it manually.
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' .  $pageRaw);
	$finder = new DomXPath($dom);
	$nodes = $finder->query("//span[contains(normalize-space(),'Daily offer')]/..");
    
    $menuURL = $nodes[0]->getAttribute('href');

    // Get the daily/weekly menu
    $pageRaw = get($baseURL . $menuURL);

    $dom = new DomDocument();
    // The web is missing encoding header, so appends it manually.
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' .  $pageRaw);
	$finder = new DomXPath($dom);
    $nodes = $finder->query("//div[@id='content']");
    
    // (the menu contains only UPPERCASE, which looks ugly)
    // Title-Case the diet titles
    $titles = $finder->query("//*[@class='dietTitle']/strong", $nodes[0]);
    foreach ($titles as $t) {
        $t->textContent = trim(mb_convert_case($t->textContent, MB_CASE_TITLE));
    }
    // And lowercase the ingredients 
    $ingredients = $finder->query("//*[@class='dietComposition']", $nodes[0]);
    foreach ($ingredients as $i) {
        $i->textContent = '(' . trim(mb_convert_case($i->textContent, MB_CASE_LOWER)) . ')';
    }
    
	$pageRaw = strip_tags($dom->saveHTML($nodes[0]));
    $pageRaw = str_replace(array("\n", "\r"), "<br>", $pageRaw);
    
    $separators = array("Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "");
    $sepA = $separators[$dayOfWeek];
    $sepB = $separators[$dayOfWeek+1];
    preg_match('/.*' . $sepA . '(.*)' . $sepB . '.*/', $pageRaw, $menuDirty);
    
    $menuClean = $menuDirty[1];
    
    // Remove the date
    $menuClean = preg_replace('/\d+\.\d+\./', "", $menuClean);
    // Remove order ("1.", "2.", ...)
    $menuClean = preg_replace('/\d\. /', "", $menuClean);
    // Clean prices
    $menuClean = preg_replace('/\d+,- Kč<br>/', "", $menuClean);
    // Remove weight
    $menuClean = preg_replace('/\d+[gG]/', "", $menuClean);
    // Collapse newlines
    $menuClean = preg_replace('/<br>(<br>|\s)+/', "<br>", $menuClean);
    // Drop leading whitespace
    $menuClean = preg_replace('/^ *<br>/', "", $menuClean);

    return $menuClean;
}

function menza_prava() {
    global $dayOfWeek;
    // This may get broken soon. I don't know how to choose the lawyer's menza explicitly 
    $pageRaw = get("https://kamweb.ruk.cuni.cz/webkredit/ZalozkaObjednavani.aspx");
	$dom = new DomDocument();
    // The web is missing encoding header, so appends it manually.
    @$dom->loadHTML('<?xml encoding="utf-8" ? >' .  $pageRaw);
    $alternatives = array(5, 7, 9, 11, 13, 15, 17); 
    $menu = array();
    foreach($alternatives as $alternative) {
        $menuEl = $dom->getElementById('Jidelnicek1_AlternativaTxt' . $alternative);
        if (isset($menuEl)) {
            array_push($menu, $menuEl->textContent);
        }
    } 
    return implode("\n", $menu);
}

include 'counter.php';

$places = array(
    array(
        'func' => 'ananta',
        'name' => 'Ananta',
        'href' => 'http://www.anantasesa.cz/tydenni-menu',
    ),
    array(
        'func' => 'natureza',
        'name' => 'Natureza',
        'href' => 'https://naturezaveget.cz/cs/dmenu',
    ),
    array(
        'func' => 'profdum',
        'name' => 'Profesní dům',
        'href' => 'https://www.profesnidum.cz/daily-menu.htm',
    ),
    array(
        'func' => 'ferdinanda',
        'name' => 'Ferdinanda',
        'href' => 'http://ferdinanda.cz/cs/mala-strana/menu/denni-menu',
    ),
    array(
        'func' => 'hamu',
        'name' => 'Hamu',
        'href' => 'https://www.hamu.cz/cs/vse-o-fakulte/fakultni-kavarna/',
    ),
    array(
        'func' => 'menza_prava',
        'name' => 'Právnická fakulta',
        'href' => 'https://kamweb.ruk.cuni.cz/webkredit/',
    ),
    array(
        'func' => 'carmelita',
        'name' => 'Carmelita',
        'href' => 'http://www.restauracecarmelita.cz/',
    ),
    array(
        'func' => 'cantina',
        'name' => 'Cantina',
        'href' => 'http://www.restauracecantina.cz/',
    ),
);
$response = array();
foreach($places as $placeArr) {
    try {
        $menu = $placeArr['func']();
        if (strlen($menu) <= 5) {
            throw new Exception('Nothing to eat');
        }
    } catch(Exception $e) {
        $menu = 'Not available';
    }
    $response[$placeArr['func']] = array(
        'name' => $placeArr['name'],
        'href' => $placeArr['href'],
        'menu' => $menu,
    );
} 
//print_r($response);
?>
