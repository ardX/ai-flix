<?php
require 'config.php';
// Database connection
$conn = mysqli_connect($db_host, $db_username, $db_password, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$uploads_dir = __DIR__ . '/uploads';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}
foreach ($_FILES as $field => $file) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $file_type = $finfo->file($file["tmp_name"]);
        
        $allowed_image_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if(!in_array($file_type, $allowed_image_types) ){
            return "Your choosen file is not a valid image type";
        }
        $now = sha1(microtime(true));
        $tmp_name = $file["tmp_name"];
        $pathinfo = pathinfo($file['name']);
        while (is_file("{$uploads_dir}/{$field}_{$pathinfo['basename']}_{$now}.{$pathinfo['extension']}")) {
            $now .= '(1)';
        }
        move_uploaded_file($tmp_name, "{$uploads_dir}/{$field}_{$pathinfo['basename']}_{$now}.{$pathinfo['extension']}");
        $uploaded_file = "{$uploads_dir}/{$field}_{$pathinfo['basename']}_{$now}.{$pathinfo['extension']}";
        // build data for a multipart/form-data request
        list('boundary' => $boundary, 'data' => $data) = wrap_multipart_data(generate_multipart_data_parts([],['photo' => $uploaded_file,]));

        // Send POST REQUEST
        $context_options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type: multipart/form-data; boundary={$boundary}\r\n"
                    . "Content-Length: " . strlen($data) . "\r\n",
                'content' => $data,
                'timeout' => 10,
            )
        );

        $context = stream_context_create($context_options);
        $result = fopen($api_url.'/upload', 'r', false, $context);
        $response = stream_get_contents($result);
        $posters = json_decode($response);

        $sql = "SELECT id, title, poster FROM movies WHERE poster IN ('" . implode("','", $posters) . "')";
        $sql = str_replace("\\", "/", $sql);
        $result = mysqli_query($conn, $sql);
        $rec = [];
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rec[] = $row;
            }
            echo json_encode($rec);
        } else {
            echo "No movies found.";
        }
    } else {
        echo "Error" . PHP_EOL;
    }
}

mysqli_close($conn);

// Source : https://gist.github.com/yookoala/58d5db5da16c6f404ef169eaf5a50249
/**
 * A genertor that yields multipart form-data fragments (without the ending EOL).
 * Would encode all files with base64 to make the request binary-safe.
 *
 * @param iterable $vars
 *    Key-value iterable (e.g. assoc array) of string or integer.
 *    Keys represents the field name.
 * @param iterable $files
 *    Key-value iterable (e.g. assoc array) of file path string.
 *    Keys represents the field name of file upload.
 *
 * @return \Generator
 *    Generator of multipart form-data fragments (without the ending EOL) in assoc format,
 *    always contains 2 key-values:
 *    * header: An array of header for a key-value pair.
 *    * content: A value string (can contain binary content) of the key-value pair.
 */
function generate_multipart_data_parts(iterable $vars, iterable $files=[]): Generator {
    // handle normal variables
    foreach ($vars as $name => $value) {
        $name = urlencode($name);
        $value = urlencode($value);
        yield [
            'header' => ["Content-Disposition: form-data; name=\"{$name}\""],
            'content' => $value,
        ];
    }

    // handle file contents
    foreach ($files as $file_fieldname => $file_path) {
        $file_fieldname = urlencode($file_fieldname);
        $file_data = file_get_contents($file_path);
        yield [
            'header' => [
                "Content-Disposition: form-data; name=\"{$file_fieldname}\"; filename=\"".basename($file_path)."\"",
                "Content-Type: application/octet-stream", // for binary safety
            ],
            'content' => $file_data
        ];
    }
}

/**
 * Converts output of generate_multipart_data_parts() into form data.
 *
 * @param iterable $parts
 *    An iterator of form fragment arrays. See return data of
 *    generate_multipart_data_parts().
 * @param string|null $boundary
 *    An optional pre-generated boundary string to use for wrapping data.
 *    Please reference section 7.2 "The Multipart Content-Type" in RFC1341.
 *
 * @return array
 *    An assoc array with 2 items:
 *    * boundary: the multipart boundary string
 *    * data: the data string (can contain binary data)
 */
function wrap_multipart_data(iterable $parts, ?string $boundary = null): array {
    if (empty($boundary)) {
        $boundary = 'boundary' . time();
    }
    $data = '';
    foreach ($parts as $part) {
        list('header' => $header, 'content' => $content) = $part;
        // Check content for boundary.
        // Note: Won't check header and expect the program makes sense there.
        if (strstr($content, "\r\n$boundary") !== false) {
            throw new \Exception('Error: data contains the multipart boundary');
        }
        $data .= "--{$boundary}\r\n";
        $data .= implode("\r\n", $header) . "\r\n\r\n" . $content . "\r\n";
    }
    // signal end of request (note the trailing "--")
    $data .= "--{$boundary}--\r\n";
    return ['boundary' => $boundary, 'data' => $data];
}
?>