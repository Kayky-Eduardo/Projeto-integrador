document.addEventListener("DOMContentLoaded", () => {
    /* SETAR ID_USUARIO NO LOCAL STORAGE */
    
    if (localStorage.getItem("id_usuario") == null) {
        const id_usuario = document.getElementById("nome-usuario-navbar").dataset.id;
        localStorage.setItem("id_usuario", id_usuario);
    }

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
        const inputDescricao = document.getElementById("set-descricao");
        const resposta = document.getElementById("resposta");
        const botaoSalvar = formJornada.querySelector("button");

        formJornada.addEventListener("submit", async (e) => {
            const checkboxesDias = document.querySelectorAll('input[name="dia_semana[]"]:checked');
            const diasSelecionados = Array.from(checkboxesDias).map(cb => parseInt(cb.value));
            
            e.preventDefault();
            resposta.textContent = "";
            resposta.className = "";

            const descricao = inputDescricao.value;
            const jornada = inputJornada.value;
            const horaExtra = inputHoraExtra.value;

            if (!jornada || !horaExtra || !descricao) {
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
                        descricao: descricao,
                        jornada: jornada,
                        hora_extra: horaExtra,
                        dias_semana: diasSelecionados
                    })
                });

                if (!response.ok) {
                    throw new Error("Erro HTTP");
                }

                const data = await response.json();

                if (data.sucesso) {
                    resposta.textContent = "";
                    chamarPnotifySuccess("Sucesso", "Jornada criada com sucesso!");

                } else {

                    resposta.textContent = data.mensagem;
                    resposta.className = "erro";
                }

            } catch (error) {
                chamarPnotifyAviso("Alerta", "Falha na comunicação com o servidor.");
                resposta.textContent = "";
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

    /* PONTO / PAUSA – REGISTRO DE PONTO */
    if (document.getElementById("formPausa")) {

        const el = document.getElementById("cronometro");
        const statusMsg = document.getElementById("statusTempo");
        const btnFinalizar = document.getElementById("btnFinalizarPausa");
        let intervalId = null;
        let avisoEmitido = false;

        if (el) {
            function atualizarInterfacePausa() {
                const inicio = new Date(el.dataset.inicio).getTime();
                const agora = Date.now();
                const decorridoSegundos = Math.floor((agora - inicio) / 1000);

                const minSegundos = parseInt(el.dataset.min) * 60;
                const maxSegundos = parseInt(el.dataset.max) * 60;
                const segundosRestantes = maxSegundos - decorridoSegundos;

                const m = Math.floor(decorridoSegundos / 60).toString().padStart(2, "0");
                const s = (decorridoSegundos % 60).toString().padStart(2, "0");
                el.textContent = `${m}:${s}`;

                if (decorridoSegundos < minSegundos) {
                    btnFinalizar && (btnFinalizar.disabled = true);
                    const faltam = minSegundos - decorridoSegundos;
                    statusMsg.textContent = `Aguarde: faltam ${Math.floor(faltam / 60)}m ${faltam % 60}s`;
                    statusMsg.style.color = "red";
                } else {
                    btnFinalizar && (btnFinalizar.disabled = false);
                    statusMsg.textContent = "Tempo mínimo atingido.";
                    statusMsg.style.color = "green";
                    
                    localStorage.setItem("Aviso", "Tempo mínimo de pausa atingido");
                    localStorage.setItem("aviso_emitido", false);
                }

                if (decorridoSegundos >= maxSegundos) {
                    cronometro.style.color = "red";
                    statusMsg.textContent = "Tempo máximo atingido.";
                    statusMsg.style.color = "red";
                    localStorage.setItem("aviso", "Tempo máximo de pausa atingido.")
                }

                if (segundosRestantes <= minSegundos && segundosRestantes > 0 && !avisoEmitido) {
                    let tempo = Math.round(segundosRestantes / 60);
                    if (tempo > 60) tempo = Math.round(tempo / 60);

                    chamarPnotifyAviso(
                        "Aviso de Tempo",
                        `Faltam ${tempo} minutos para o limite da sua pausa!`,
                        100000
                    );
                    
                    avisoEmitido = true;
                }
            }

            intervalId = setInterval(atualizarInterfacePausa, 1000);
            atualizarInterfacePausa();
        }

        if (btnFinalizar) {
            btnFinalizar.addEventListener("click", () => {
                chamarPnotifySuccess("Ação registrada!", "Sua ação foi registrada com sucesso!")
            });
        }
    }

    // EDITAR FOLHA
    //CÓDIGO DAVI ↓↓↓↓↓
    // deletar
    document.querySelectorAll('.btn-deletar').forEach(botao => {
        botao.addEventListener('click', function() {
            const idEvento = this.getAttribute('data-id');

            const confirmarExclusao = () => {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        confirmado: true, 
                        acao: 'deletar', 
                        id_evento: idEvento 
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'sucesso') {
                        chamarPnotifySuccess("Sucesso!", data.msg);
                    } else {
                        chamarPnotifyAlert("Erro", data.msg);
                    }
                })
                .catch(err => console.error("Erro na requisição:", err));
            };

            const cancelarExclusao = () => {
                chamarPnotifyAlert("Erro", "Exclusão Cancelada!");
            };
            chamarPnotifyConfirm("Confirmar", "Tem certeza que deseja deletar este evento?", confirmarExclusao(), cancelarExclusao());

        });
    });
    
    // editar
    document.querySelectorAll('.input-editar').forEach(input => {
        input.addEventListener('change', function(event){
            const idEditar = this.getAttribute('id');
            const valorNovo = event.target.value;
            fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        acao: 'editar',
                        valor: valorNovo,
                        id_editar : idEditar
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'sucesso') chamarPnotifySuccess("Sucesso!", data.msg); // Recarrega para ver a mudança
                })
                .catch(err => console.error("Erro na requisição:", err));
        })
        
    });
    //CÓDIGO DAVI ↑↑↑↑↑

    // REVISAR FOLHA
    // alterar para revisado
    btnRevisar = document.querySelector('.btn-salvar');
    btnRevisar.addEventListener('click', function(){
        let resposta = confirm('Tem certeza que deseja marcar como revisado? Sua folha não poderá ser modificada depois');
        const idUsuario = this.getAttribute('id');
        if (resposta){
            fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        acao: 'revisar',
                        id_usuario: idUsuario
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'sucesso') chamarPnotifySuccess("Sucesso!", data.msg); // Recarrega para ver a mudança
                })
                .catch(err => console.error("Erro na requisição:", err));
        }
    })
    //CÓDIGO DAVI ↑↑↑↑↑
    window.onload = function () {
        // Se não marcou como "acabou de recarregar"
        if (!sessionStorage.getItem("justReloaded")) {
            // Marca que acabou de recarregar
            sessionStorage.setItem("justReloaded", "true");
            // Recarrega a página
            location.reload();
        } else {
            // Limpa a marca para a próxima vez que entrar na página
            sessionStorage.removeItem("justReloaded");
        }
    };


    /* PNOTIFY */
    function chamarPnotifyAviso(titulo, mensagem, milissegundos) {
        const som_aviso = new Audio('/projeto-integrador/assets/som_notificacoes/notificacao_comum.mp3');

        som_aviso.play();

        let tempo = milissegundos ?? 5000;
        PNotify.info({
            title: titulo,
            text: `${mensagem}`,
            delay: tempo,
        });
    }

    function chamarPnotifyAlert(titulo, mensagem, milissegundos) {
        const som_aviso = new Audio('/projeto-integrador/assets/som_notificacoes/notificacao_erro.wav');

        som_aviso.play();

        let tempo = milissegundos ?? 5000;
        PNotify.alert({
            title: titulo,
            text: `${mensagem}`,
            delay: tempo,
        });
    }

    function chamarPnotifySuccess(titulo, mensagem, milissegundos) {
        const som_aviso = new Audio('/projeto-integrador/assets/som_notificacoes/notificacao_comum.mp3');

        som_aviso.play();

        let tempo = milissegundos ?? 5000;
        PNotify.success({
            title: titulo,
            text: `${mensagem}`,
            delay: tempo,    
        });
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
                console.log(resposta.dados.mensagem);
                chamarPnotifyAviso("Aviso", resposta.dados.mensagem, 5000);
            } 
        });
    } else if (localStorage.getItem("aviso") && localStorage.getItem("aviso_emitido") === false) {
        let mensagem = localStorage.getItem("aviso");
        chamarPnotifyAviso("Aviso", mensagem, 5000);
        localStorage.setItem("aviso_emitido", true);
    }

    function chamarPnotifyConfirm(titulo, mensagem, funcaoConfirmar, funcaoCancelar) {
        const som_aviso = new Audio('/projeto-integrador/assets/som_notificacoes/notificacao_comum.mp3');
        som_aviso.play();

        PNotify.confirm({
            title: titulo,
            text: mensagem,
            icon: 'fas fa-question-circle',
            hide: false,
            closer: false,
            sticker: false,
            modules: {
                Confirm: {
                    confirm: true,
                    buttons: [{
                            text: 'Confirmar',
                            primary: true,
                            click: (notice) => {
                                notice.close();
                                if (typeof funcaoConfirmar === 'function') funcaoConfirmar();
                            }
                        },
                        {
                            text: 'Cancelar',
                            click: (notice) => {
                                notice.close();
                                if (typeof funcaoCancelar === 'function') funcaoCancelar();
                            }
                        }
                    ]
                }
            }
        });
    }
})