<?php require 'config.php';?>
<?php
    // Database connection
    $conn = mysqli_connect($db_host, $db_username, $db_password, $db_name);

    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix-like Movie Website</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #111;
            color: #fff;
        }
        header {
            background-color: #000;
            padding: 20px;
            text-align: center;
        }
        header h1 {
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .movie {
            display: inline-block;
            width: 200px;
            margin: 10px;
            background-color: #222;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        .movie img {
            width: 100%;
            height: auto;
        }
        .movie-title {
            padding: 10px;
            text-align: center;
            font-size: 16px;
        }
    </style>
</head>
<body>

<header>
    <a href="index.php"><h1>Netflix-like Movie Website</h1></a>
    <form action="search.php" method="get">
        <input type="text" name="query" placeholder="Search movies...">
        <button type="submit">Search</button>
    </form>
</header>

<div class="container">
    <h2>Top Movies</h2>
    <?php

    $url = $api_url.'/recommend';

    // Initialize cURL session
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL session
    $response = curl_exec($ch);

    // Check for errors
    if(curl_errno($ch)){
        echo 'cURL error: ' . curl_error($ch);
    }

    // Close cURL session
    curl_close($ch);
    
    // Parse JSON response
    $recommendations = json_decode($response);
    
    // $listid = "";
    // // Display recommendations
    // foreach ($recommendations as $movieid) {
    //     echo $movieid . "<br>";
    //     $listid 
    // }

    // Fetch movie data from the database
    $sql = "SELECT id, title, poster FROM movies WHERE id IN (" . implode(',', $recommendations) . ")";
    $result = mysqli_query($conn, $sql);

    // Display movie thumbnails
    $rec = [];
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rec[] = $row;
        }
        foreach ($recommendations as $movieid) {
            foreach ($rec as $row) {
                if($movieid==$row['id']){
                    echo '<a href="detail.php?id=' . $row['id'] . '">';
                    echo '<div class="movie">';
                    echo '<img src="poster' . $row['poster'] . '" alt="' . $row['title'] . '">';
                    echo '<div class="movie-title">' . $row['title'] . '</div>';
                    echo '</div>';
                    echo '</a>';
                    break;
                }
            }
        }
    } else {
        echo "No movies found.";
    }
    ?>
</div>

</body>
</html>

<?php
    // Close database connection
    mysqli_close($conn);
?>