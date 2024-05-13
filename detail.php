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
    <title>Movie Detail</title>
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
            max-width: 800px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .movie-detail {
            background-color: #222;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        .movie-detail img {
            max-width: 100%;
            height: auto;
            margin-bottom: 20px;
        }
        .movie-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .movie-info {
            margin-bottom: 10px;
        }
        .container2 {
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
        .center {
            display: block;
            margin-left: auto;
            margin-right: auto;
            width: 50%;
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
    <?php
    
    $title = "";
    $poster = "";
    // Fetch movie details based on ID
    if (isset($_GET['id'])) {
        $movie_id = $_GET['id'];
        $sql = "SELECT * FROM movies WHERE id = $movie_id";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            echo '<div class="movie-detail">';
            echo '<img src="poster' . $row['poster'] . '" alt="' . $row['title'] . '" class="center">';
            echo '<div class="movie-title">' . $row['title'] . '</div>';
            echo '<div class="movie-info">Release Date: ' . $row['release_date'] . '</div>';
            echo '<div class="movie-info">Overview: ' . $row['overview'] . '</div>';
            echo '</div>';
            $title = $row['title'];
            $poster = $row['poster'];
        } else {
            echo "Movie not found.";
        }
    } else {
        echo "Invalid movie ID.";
    }
    ?>
</div>

<div class="container2">
    <h2>Similar Movies (using description only)</h2>
    <?php
    
    $data = array('title' => $title);  // Example data (replace with actual data)

    // Convert data to JSON
    $data_json = json_encode($data);
    
    $url = $api_url.'/similar';

    // Initialize cURL session
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
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
    $similars = json_decode($response);
    
    // $listid = "";
    // // Display similars
    // foreach ($similars as $movieid) {
    //     echo $movieid . "<br>";
    //     $listid 
    // }

    // Fetch movie data from the database
    $sql = "SELECT id, title, poster FROM movies WHERE id IN (" . implode(',', $similars) . ")";
    $result = mysqli_query($conn, $sql);

    // Display movie thumbnails
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<a href="detail.php?id=' . $row['id'] . '">';
            echo '<div class="movie">';
            echo '<img src="poster' . $row['poster'] . '" alt="' . $row['title'] . '">';
            echo '<div class="movie-title">' . $row['title'] . '</div>';
            echo '</div>';
            echo '</a>';
        }
    } else {
        echo "No movies found.";
    }
    ?>
</div>

<div class="container2">
    <h2>Similar Movies (using cast, director etc)</h2>
    <?php
    
    $data = array('title' => $title);  // Example data (replace with actual data)

    // Convert data to JSON
    $data_json = json_encode($data);
    
    $url = $api_url.'/similar2';

    // Initialize cURL session
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
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
    $similars = json_decode($response);
    
    // $listid = "";
    // // Display similars
    // foreach ($similars as $movieid) {
    //     echo $movieid . "<br>";
    //     $listid 
    // }

    // Fetch movie data from the database
    $sql = "SELECT id, title, poster FROM movies WHERE id IN (" . implode(',', $similars) . ")";
    $result = mysqli_query($conn, $sql);

    // Display movie thumbnails
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<a href="detail.php?id=' . $row['id'] . '">';
            echo '<div class="movie">';
            echo '<img src="poster' . $row['poster'] . '" alt="' . $row['title'] . '">';
            echo '<div class="movie-title">' . $row['title'] . '</div>';
            echo '</div>';
            echo '</a>';
        }
    } else {
        echo "No movies found.";
    }
    ?>
</div>

<div class="container2">
    <h2>Similar Movies (using poster image)</h2>
    <?php
    
    $data = array('poster' => $poster);  // Example data (replace with actual data)

    // Convert data to JSON
    $data_json = json_encode($data);
    
    $url = $api_url.'/cbir';

    // Initialize cURL session
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
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
    $posters = json_decode($response);
    
    // // Display similars
    // foreach ($posters as $poster) {
    //     echo $poster . "<br>";
    // }

    // Fetch movie data from the database
    $sql = "SELECT id, title, poster FROM movies WHERE poster IN ('" . implode("','", $posters) . "')";
    $sql = str_replace("\\", "/", $sql);
    // echo $sql;
    $result = mysqli_query($conn, $sql);

    // Display movie thumbnails
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<a href="detail.php?id=' . $row['id'] . '">';
            echo '<div class="movie">';
            echo '<img src="poster' . $row['poster'] . '" alt="' . $row['title'] . '">';
            echo '<div class="movie-title">' . $row['title'] . '</div>';
            echo '</div>';
            echo '</a>';
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