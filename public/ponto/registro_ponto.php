<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>

<body>
    <main>
        <?php
            for ($i = 0; $i < 10; $i++){
                echo"<h1>$i</h1>";
                usleep(1);
            }
        ?>
    </main>

    <!--Javascript-->
    <script src="js/script.js"></script>
</body>

</html>