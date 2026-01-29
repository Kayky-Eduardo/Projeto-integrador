<div id="aviso-jornada" class="aviso oculto"></div>

<style>
.aviso {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #0f172a;
    color: white;
    padding: 15px 20px;
    border-radius: 12px;
    font-family: Arial;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    z-index: 9999;
}
.oculto { display: none; }
</style>
<script>
let contadorInterval = null;

async function checarAvisoJornada() {
    try {
        const r = await fetch('/projeto-integrador/api/api_consulta_avisos.php');

        if (!r.ok) {
            console.warn("Erro na API de avisos:", r.status);
            return;
        }

        const dados = await r.json();

        if (dados.erro === 'nao_autenticado') {
            location.href = "/projeto-integrador/public/logout.php";
            return;
        }

        if (dados.mostrar) {
            iniciarAviso(dados.segundos);
        }

    } catch (e) {
        console.error("Falha ao buscar aviso de jornada:", e);
    }
}

function iniciarAviso(segundos) {
    const box = document.getElementById('aviso-jornada');
    box.classList.remove('oculto');

    if (contadorInterval) return;

    contadorInterval = setInterval(() => {
        if (segundos <= 0) {
            clearInterval(contadorInterval);
            location.href = "/projeto-integrador/public/logout.php";
            return;
        }

        const m = Math.floor(segundos / 60);
        const s = segundos % 60;

        box.innerHTML = `Sua sessão termina em <b>${m}m ${s}s</b>`;
        segundos--;
    }, 1000);
}

setInterval(checarAvisoJornada, 20000);
checarAvisoJornada();
</script>