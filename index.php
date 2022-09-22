<html>
<head>
    <title>Mealtime</title>
    <link rel="shortcut icon" href="https://ufal.mff.cuni.cz/sites/all/themes/drufal/ico/favicon.ico" type="image/vnd.microsoft.icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400" media="all">
    <style>
        * { font-family: Roboto; }
        a { text-decoration: none; }
    </style>
</head>

<body style='background-color: #f4f4f4'>
    <h1>It's mealtime!</h1>
    <?php
    include 'src/raw.php';
    
    if ($isWeekend) {
        echo "<p>It's weekend now, so I'm showing last available lunch menus.</p>";
    }

    foreach($response as $place) {
        echo '<h3 style="background-color: yellow"><a href="' . $place['href'] . '">' . $place['name'] . '</a></h3>';
        $menu = $place['menu'];
        $menu = str_replace("\n", '<br>', $menu);
        echo '<p>' . $menu . '</p>';
    }
    ?>

    <br>
    <div>
        Mealtime is Anša's fork of the CfM project, made by Vilda with the help of others from MS.
    </div>
    
    <div style='font-weight: bold;'>
        <a href="https://github.com/Ansa211/mealtime">GitHub</a><!--, <a href="statistics.php">Statistics</a>-->
    </div>
</body>
</html>
