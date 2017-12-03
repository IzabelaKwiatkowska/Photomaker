<?php
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');

?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="theme-color" content="#AAAAAA">
        <title>PhotoMaker</title>
        <link rel="shortcut icon" href="photos/favicon.ico" />
        <link href="photomaker.css" rel="stylesheet" type="text/css">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <div class="logo">
            PhotoMaker
        </div>

        <div class="text">
            Narzędzie, które w szybki i łatwy sposób przekonwertuje<br> 
            Twoje obrazy do wybranego rozmiaru <br> (obsługiwane formaty .jpg .png .bmp .gif)
        </div>

        <form method="post" enctype="multipart/form-data" action="/formularz_2.php">
            <div class="dimensions">
                <input type="number"  name="height" placeholder="wysokość"  tabindex="1" />
                <input type="number"  name="width" placeholder="szerokość" tabindex="4" />
            </div>
            <div class="errors-holder">
            </div>

            <div class="files-holder">
                <div class="files-inputs">
                    
                    <?php
                    for ($i=1; $i<6; $i++){
                        ?>
                    <input type="file" name="file<?php echo $i; ?>" id="file<?php echo $i; ?>" class="inputfile" 
                           data-multiple-caption="{count} files selected" multiple />
                    <label for="file<?php echo $i; ?>">
                        Zdjęcie nr. <?php echo $i; ?>
                    </label>
                    
                    <?php
                    }
                    ?>
                    
                    
                </div>
                <div class="files-button">
                    <input id="submit" name="submit" type="submit" value="Konwertuj">
                </div>
            </div>
        </form>
        <script src="jquery-3.1.1.min.js"></script>
        <script src="flowtype.js"></script>
    <script>$('body').flowtype({
        minFont: 20
    });</script>     
    <script src="input.js"></script>
    <script src="javascript.js"></script>
</body>
</html>