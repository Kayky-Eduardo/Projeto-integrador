<article id="popup-ponto">
    <h3>A finalização do ponto foi confirmada!</h3>
    <p id="contador"></p>
</article>

<script>
  let segundos = TEMPO_LOGOUT;
  const p = document.getElementById("contador");

  function atualizar() {
    const minutos = Math.floor(segundos / 60);
    const resto = segundos % 60;

    p.textContent = `Você será deslogado em ${minutos}:${resto.toString().padStart(2, "0")}`;
  }

  atualizar();

  const interval = setInterval(() => {
    segundos--;
    atualizar();

    if (segundos <= 0) {
      clearInterval(interval);
      window.location.href = "/projeto-integrador/public/logout.php";
    }
  }, 1000);
</script>
