<?php
if ($_FILES) {
    $tmp = $_FILES['file']['tmp_name'];
    echo "TMP = $tmp<br>";
    if (file_exists($tmp)) {
        echo "Fichier existe<br>";
        move_uploaded_file($tmp, __DIR__.'/test_upload.txt');
        echo "Upload OK";
    } else {
        echo "TMP file does not exist";
    }
} else {
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="file">
    <button type="submit">Upload</button>
</form>
<?php } ?>