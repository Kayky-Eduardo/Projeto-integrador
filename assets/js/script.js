document.addEventListener("DOMContentLoaded", () => {
    /* CARROSSEL - INDEX*/
    const slides = document.querySelectorAll(".slide");
    const prev = document.querySelector(".prev");
    const next = document.querySelector(".next");
    const dots = document.querySelectorAll(".dot");
    const area = document.querySelector(".carrossel");
    let index = 0;
    let interval;

    function mostrarSlide(i) {
        slides.forEach(slide => slide.classList.remove("slide-ativo"));
        dots.forEach(dot => dot.classList.remove("ativo"));
        slides[i]?.classList.add("slide-ativo");
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

    /* FILTRO DE BUSCA E FILTRO DE STATUS - LISTA DE USUÁRIOS */
    const campoBusca = document.getElementById("busca");
    const filtroStatus = document.getElementById("filtro-status");
    const cardsUsuarios = document.querySelectorAll(".card-usuario");

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

                if (!nome.includes(termo) && !cargo.includes(termo)) {
                    visivel = false;
                }

                if (statusSelecionado === "ativos" && !isAtivo) {
                    visivel = false;
                }

                if (statusSelecionado === "inativos" && isAtivo) {
                    visivel = false;
                }

                card.classList.toggle("hidden", !visivel);
            });
        }
        aplicarFiltros();
    }

    /* MÁSCARAS DE PREENCHIMENTO AUTOMÁTICO */
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
            painelEdicao.classList.toggle("hidden");
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
                const nomeUsuario = linha.children[0]?.textContent.toLowerCase() || "";
                linha.style.display = nomeUsuario.includes(termo) ? "" : "none";
            });
        });
    }

    /*  CONFIGURAÇÕES – JORNADA  */
    const formJornada = document.getElementById("form-jornada");

    if (formJornada) {
        const inputJornada = document.getElementById("set-jornada");
        const inputHoraExtra = document.getElementById("set-hora-max");
        const resposta = document.getElementById("resposta");
        const botaoSalvar = formJornada.querySelector("button");

        formJornada.addEventListener("submit", async (e) => {
            e.preventDefault();
            resposta.textContent = "";
            resposta.className = "";
            const jornada = inputJornada.value;
            const horaExtra = inputHoraExtra.value;

            if (!jornada || !horaExtra) {
                resposta.textContent = "Preencha todos os campos.";
                resposta.className = "erro";
                return;
            }

            if (jornada === "00:00") {
                resposta.textContent = "A jornada diária não pode ser zero.";
                resposta.className = "erro";
                return;
            }

            try {
                botaoSalvar.disabled = true;
                resposta.textContent = "Salvando configuração...";
                resposta.className = "info";

                const response = await fetch("../../api/api_jornada.php?acao=jornada", {
                    method: "POST",

                    headers: {
                        "Content-Type": "application/json"
                    },

                    body: JSON.stringify({
                        jornada: jornada,
                        hora_extra: horaExtra
                    })
                });

                if (!response.ok) {
                    throw new Error("Erro HTTP");
                }

                const data = await response.json();
                resposta.textContent = data.mensagem;
                resposta.className = data.sucesso ? "sucesso" : "erro";
            } catch (error) {
                resposta.textContent = "Falha na comunicação com o servidor.";
                resposta.className = "erro";
                console.error(error);
            } finally {
                botaoSalvar.disabled = false;
            }
        });
    }

    /* ONLINE – USUÁRIOS LOGADOS */
    const tabelaOnline = document.getElementById("tabela-online");
    const filtroOnline = document.getElementById("filtro-online");

    if (tabelaOnline && filtroOnline) {
        const API_URL = "../../api/api_relatorio_ponto.php";
        carregarLogados();
        setInterval(carregarLogados, 10000);
        filtroOnline.addEventListener("input", aplicarFiltroOnline);

        async function carregarLogados() {
            try {
                const response = await fetch(`${API_URL}?acao=get_logados`);
                const dados = await response.json();
                tabelaOnline.innerHTML = "";

                if (!Array.isArray(dados) || !dados.length) {
                    tabelaOnline.innerHTML = `
                        <tr>
                            <td colspan="5">Nenhum usuário logado</td>
                        </tr>
                    `;
                    return;
                }

                const fragment = document.createDocumentFragment();

                dados.forEach(usuario => {
                    fragment.appendChild(criarLinhaOnline(usuario));
                });

                tabelaOnline.appendChild(fragment);
                aplicarFiltroOnline();
            } catch (error) {
                console.error("Erro ao buscar usuários logados:", error);

                tabelaOnline.innerHTML = `
                    <tr>
                        <td colspan="5">Erro ao carregar dados</td>
                    </tr>
                `;
            }
        }

        function criarLinhaOnline(usuario) {
            const tr = document.createElement("tr");
            const tempo = formatarTempo(usuario.tempo_logado);

            tr.innerHTML = `
                <td>
                    <button class="btn btn-padrao" data-id="${usuario.id_login}">
                        Deslogar
                    </button>
                </td>

                <td class="col-nome">${usuario.nome_usuario ?? "-"}</td>
                <td>${usuario.email_usuario ?? "-"}</td>
                <td>${usuario.data_inicio ?? "-"}</td>
                <td>${tempo}</td>
            `;

            tr.querySelector("button").addEventListener("click", () => {
                deslogarUsuario(usuario.id_login);
            });

            return tr;
        }

        function aplicarFiltroOnline() {
            const termo = filtroOnline.value.toLowerCase().trim();
            const linhas = tabelaOnline.querySelectorAll("tr");

            linhas.forEach(linha => {
                const nome = linha.querySelector(".col-nome")?.textContent.toLowerCase() || "";
                linha.style.display = nome.includes(termo) ? "" : "none";
            });
        }

        async function deslogarUsuario(idLogin) {
            await fetch(`${API_URL}?acao=deslogar`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id_login: idLogin })
            });

            carregarLogados();
        }

        function formatarTempo(minutosTotais) {
            if (!minutosTotais) return "-";
            const horas = Math.floor(minutosTotais / 60);
            const minutos = minutosTotais % 60;
            return horas > 0 ? `${horas}h ${minutos}min` : `${minutos}min`;
        }
    }

    if (localStorage.getItem("id_usuario")) {
        let id_usuario = localStorage.getItem("id_usuario");
        
        fetch(`/projeto-integrador/api/api_consulta_aviso.php?id_usuario=${id_usuario}`, {
            method: "GET",
            headers: {"Content-Type": "application/json"},
        })
        .then((res)=> res.json())
        .then((resposta) => {
            if (resposta.sucesso) {
                PNotify.info({
                    title: 'Aviso',
                    text: `${resposta.dados.mensagem}`,
                    delay: 3000,    
                });
            } 
        });
    }

    if (localStorage.getItem("aviso_sucesso") === "true") {        
        PNotify.success({
            title: 'Sucesso',
            text: `Ação registrada com sucesso!}`,
            delay: 3000
        });
        localStorage.removeItem("aviso_sucesso");
    }
});
