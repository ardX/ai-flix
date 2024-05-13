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
    <title>Movie Search</title>
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
        #load{
            width:100%;
            height:100%;
            position:fixed;
            z-index:9999;
            background:url("https://upload.wikimedia.org/wikipedia/commons/1/10/Loading-special.gif") no-repeat center center rgba(0,0,0,0.25)
        }
    </style>
</head>
<body>

<div id="load">
</div>

<header>
    <a href="index.php"><h1>Netflix-like Movie Website</h1></a>
    <form action="search.php" method="get">
        <input type="text" name="query" placeholder="Search movies...">
        <button type="submit">Search</button>
    </form>
    <input type="file" id="fileInput" accept="image/*" style="display: none;">
    <button onclick="selectImage()">Search by Image</button>
    <script>
        document.getElementById('load').style.visibility="hidden";
        function selectImage() {
            var fileInput = document.getElementById('fileInput');
            
            // Trigger click event on file input
            fileInput.click();
            
            // Handle file selection
            fileInput.onchange = function() {
                document.getElementById('load').style.visibility="visible";
                var file = fileInput.files[0];
                var formData = new FormData();
                
                // Append selected image file to FormData object
                formData.append('photo', file);
                
                // Send POST request to server
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'upload.php', true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        var elements = document.getElementsByClassName("movie");
                        while (elements[0]) {
                            elements[0].parentNode.removeChild(elements[0]);
                        }
                        var responseData = JSON.parse(xhr.responseText);
                        // console.log(responseData);
                        for (var i = 0; i < responseData.length; i++) {
                            // console.log(responseData[i]['poster']);
                            const div = document.createElement('div');
                            div.className = 'movie';
                            div.innerHTML = `
                            <a href="detail.php?id=` + responseData[i]['id'] + `">
                            <img src="poster` + responseData[i]['poster'] + `" alt="` + responseData[i]['title'] + `">
                            <div class="movie-title">` + responseData[i]['title'] + `</div>
                            </a>
                            `;
                            document.getElementsByClassName('container')[0].appendChild(div);
                        }
                        document.getElementById('load').style.visibility="hidden";
                        // alert('Image uploaded successfully!');
                    } else {
                        // alert('Error uploading image');
                    }
                };
                xhr.send(formData);
            };
        }
    </script>
</header>

<div class="container">
    <h2>Search Results</h2>
    <?php

    // Check if the form is submitted
    if (isset($_GET['query'])) {
        // Use prepared statement to prevent SQL injection
        $search_query = '%' . $_GET['query'] . '%';
        $sql = "SELECT * FROM movies WHERE title LIKE ? or overview LIKE ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $search_query, $search_query);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    
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
            echo "No results found.";
        }
    
        mysqli_stmt_close($stmt);
    }
    ?>
</div>

</body>
</html>

<?php
    // Close database connection
    mysqli_close($conn);
?>