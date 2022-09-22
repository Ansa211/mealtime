<?php

/* Normalizes dayOfWeek to 0 - Mon, 6 - Sun
 * Add +7 because PHP mod is broken on negative numbers
 */
$date=date('d-m-Y');
$dayOfWeek = (date('w')-1 + 7) % 7;
$isWeekend = ($dayOfWeek > 4);
$dayOfWeek = min($dayOfWeek, 4);

// fetch by URL with caching
function getURL($url, bool $use_include_path = FALSE, $context = NULL) {
    $cachePrefix = 'cache/'; // cache/ is expected to be hidden with .htaccess
    $fname = $cachePrefix . preg_replace("/[^a-zA-Z0-9]/", "_", $url);
    $maxAge = 1 * 60; // 1 minute

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

// include all files in places/
foreach (glob("src/places/*.php") as $filename) {
    include $filename;
}

// // bump up the daily counter
// include 'counter.php';

$places = array(
    array(
        'func' => 'malinova',
        'name' => 'Restaurace v Malinové (11:00-14:00)',
        'href' => 'http://restauracevmalinove/denni-menu/',
    ),
    array(
        'func' => 'usvehly',
        'name' => 'U Švehly (11:00-15:00)',
        'href' => 'http://www.u-svehly.cz/denni-menu-'. $date,
    ),
    array(
        'func' => 'allegria',
        'name' => 'Pizzeria Allegria (11:00-15:00)',
        'href' => 'https://pizzeriaallegria.cz/',
    ),
    array(
        'func' => 'kotelna',
        'name' => 'Kotelna',
        'href' => 'https://restauracekotelna.cz/',
    ),
    array(
        'func' => 'topolova',
        'name' => 'Rezidence Topolová',
        'href' => 'https://www.restauracetopolova.cz/denni-menu/',
    ),
);
$response = array();
foreach($places as $placeArr) {
    try {
        $menu = $placeArr['func']();
        if (strlen($menu) <= 5) {
            throw new Exception('Nothing to eat');
        }
    } catch(Throwable $t) {
        $menu = 'Not available';
    }
    $response[$placeArr['func']] = array(
        'name' => $placeArr['name'],
        'href' => $placeArr['href'],
        'menu' => $menu,
    );
} 
?>

