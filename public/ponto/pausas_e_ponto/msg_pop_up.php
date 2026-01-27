<?php

?>
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
        <section id="resposta-pop-up">
        </section>
    </main>
    <script>
        let segundos = new URLSearchParams(window.location.search).get('s');
        let milissegundos = Math.round(segundos * 1000);
        let minutos = Math.round(segundos / 60);

        let section = document.getElementById("resposta-pop-up");
        let h3 = document.createElement("h3");
        let p = document.createElement("p");
        let tempo_novo = minutos;   
        
        h3.textContent = "A finalização do ponto foi confirmada!";
        p.textContent = `Você será deslogado em ${minutos} minutos`;

        section.appendChild(h3);
        section.appendChild(p);

        setInterval(() => {
            tempo_novo -= 1;
            p.textContent = `Você será deslogado em ${tempo_novo} minutos`;

        }, 60000);

        setTimeout(() => {
            window.location.href = "/projeto-integrador/public/logout.php";
        }, milissegundos);
    </script>
</body>

</html>