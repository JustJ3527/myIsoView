<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
    <link rel="stylesheet" type="text/css" href="css/style.css">

    <script src="https://kit.fontawesome.com/2dd49550a9.js" crossorigin="anonymous"></script>
</head>
<body>

    <div id="panorama"></div>


    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <script src="js/custom.js"></script>
    <script>
        pannellum.viewer('panorama', {
            "type": "equirectangular",
            "autoload" : true,
            "panorama": "./panorama.jpg" 
            
        });
    </script>
</body>
</html>