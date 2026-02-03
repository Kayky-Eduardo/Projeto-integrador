document.addEventListener("DOMContentLoaded", () => {
    /* CARROSSEL */
    const slides = document.querySelectorAll(".slide");
    const prev = document.querySelector(".prev");
    const next = document.querySelector(".next");
    const dots = document.querySelectorAll(".dot");
    const area = document.querySelector(".carrossel");

    let index = 0;
    let interval;

    function mostrarSlide(i) {
        slides.forEach(slide => slide.classList.remove("ativo"));
        dots.forEach(dot => dot.classList.remove("ativo"));

        slides[i]?.classList.add("ativo");
        dots[i]?.classList.add("ativo");

        index = i;
    }

    function iniciarAuto() {
        if (!slides.length) return;

        interval = setInterval(() => {
            mostrarSlide((index + 1) % slides.length);
        }, 6000);
    }

    function pararAuto() {
        clearInterval(interval);
    }

    next?.addEventListener("click", () => {
        mostrarSlide((index + 1) % slides.length);
    });

    prev?.addEventListener("click", () => {
        mostrarSlide((index - 1 + slides.length) % slides.length);
    });

    dots.forEach((dot, i) => {
        dot.addEventListener("click", () => {
            mostrarSlide(i);
            pararAuto();
            iniciarAuto();
        });
    });

    area?.addEventListener("mouseenter", pararAuto);
    area?.addEventListener("mouseleave", iniciarAuto);

    iniciarAuto();

    /* BUSCA + FILTRO DE STATUS - LISTA DE USUÁRIOS */
    const campoBusca = document.getElementById("busca");
    const filtroStatus = document.getElementById("filtro-status");
    const cardsUsuarios = document.querySelectorAll(".usuario-card");

    if (campoBusca && cardsUsuarios.length) {

        campoBusca.addEventListener("input", aplicarFiltros);
        filtroStatus?.addEventListener("change", aplicarFiltros);

        function aplicarFiltros() {
            const termo = campoBusca.value.toLowerCase().trim();
            const statusSelecionado = filtroStatus?.value || "ativos";

            cardsUsuarios.forEach(card => {
                const nome = card.querySelector("h3")?.textContent.toLowerCase() || "";
                const cargo = card.querySelector(".cargo")?.textContent.toLowerCase() || "";
                const isAtivo = card.querySelector("span")?.classList.contains("ativo");

                let visivel = true;

                // filtro por texto
                if (!nome.includes(termo) && !cargo.includes(termo)) {
                    visivel = false;
                }

                // filtro por status
                if (statusSelecionado === "ativos" && !isAtivo) {
                    visivel = false;
                }

                if (statusSelecionado === "inativos" && isAtivo) {
                    visivel = false;
                }

                card.classList.toggle("hidden", !visivel);
            });
        }

        // filtro inicial: somente ativos
        aplicarFiltros();
    }

    /* MÁSCARAS */
    if (document.getElementById("cpf")) {
        IMask(document.getElementById("cpf"), {
            mask: "000.000.000-00"
        });
    }

    if (document.getElementById("rg")) {
        IMask(document.getElementById("rg"), {
            mask: "00.000.000-0"
        });
    }

    if (document.getElementById("telefone")) {
        IMask(document.getElementById("telefone"), {
            mask: "(00) 00000-0000"
        });
    }

    if (document.getElementById("cep")) {
        IMask(document.getElementById("cep"), {
            mask: "00000-000"
        });
    }

    /* PREVIEW DA FOTO */
    const inputFoto = document.getElementById("input-foto");
    const previewFoto = document.getElementById("preview-foto");
    const btnSalvarFoto = document.getElementById("btn-salvar-foto");

    if (inputFoto && previewFoto) {
        inputFoto.addEventListener("change", () => {
            const file = inputFoto.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = () => {
                previewFoto.src = reader.result;
            };
            reader.readAsDataURL(file);

            if (btnSalvarFoto) {
                btnSalvarFoto.classList.remove("hidden");
            }
        });
    }

    /* EDIÇÃO DE USUÁRIO */
    const btnEditar = document.getElementById("btn-editar");
    const painelEdicao = document.getElementById("painel-edicao");

    if (btnEditar && painelEdicao) {
        btnEditar.addEventListener("click", () => {
            painelEdicao.classList.toggle("ativo");
        });
    }

    /* FILTRO – BANCO DE HORAS (RELATÓRIO) */

    const filtroBancoHoras = document.getElementById("filtroUsuario");
    const tabelaBancoHoras = document.getElementById("tabelaBancoHoras");

    if (filtroBancoHoras && tabelaBancoHoras) {

        const linhasBancoHoras = tabelaBancoHoras.querySelectorAll("tbody tr");

        filtroBancoHoras.addEventListener("input", () => {
            const termo = filtroBancoHoras.value.toLowerCase().trim();

            linhasBancoHoras.forEach(linha => {
                const nomeUsuario = linha.children[0]
                    ?.textContent
                    .toLowerCase() || "";

                linha.style.display = nomeUsuario.includes(termo)
                    ? ""
                    : "none";
            });
        });
    }
});

// if (localStorage.getItem("teste") === "true") {
//     let inicio = localStorage.getItem("inicio");
//     let agora = new Date().getTime();
//     let decorridoSegundos = Math.floor((agora - inicio) / 1000);

//     PNotify.success({
//         title: 'Sucesso',
//         text: `Ação registrada com sucesso!${decorridoSegundos}`,
//         delay: 3000
//     });
//     localStorage.removeItem("aviso_sucesso");
// }