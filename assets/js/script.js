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
                window.location.reload();
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
            window.location.reload();
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
        if (localStorage.getItem("aviso_sucesso") === "true") {
            PNotify.success({
                title: "Sucesso",
                text: "Ação registrada com sucesso!",
                delay: 3000
            });

            localStorage.removeItem("aviso_sucesso");
        }

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
                }

                if (decorridoSegundos >= maxSegundos) {
                    clearInterval(intervalId);
                    document.getElementById("formPausa").submit();
                }

                if (segundosRestantes <= minSegundos && segundosRestantes > 0 && !avisoEmitido) {
                    let tempo = Math.round(segundosRestantes / 60);
                    if (tempo > 60) tempo = Math.round(tempo / 60);

                    PNotify.notice({
                        title: "Aviso de Tempo",
                        text: `Faltam ${tempo} minutos para o limite da sua pausa!`,
                        delay: 10000
                    });

                    avisoEmitido = true;
                }
            }

            intervalId = setInterval(atualizarInterfacePausa, 1000);
            atualizarInterfacePausa();
        }

        if (btnFinalizar) {
            btnFinalizar.addEventListener("click", () => {
                localStorage.setItem("aviso_sucesso", "true");
            });
        }
    }

    /* SOLICITAÇÃO DE AJUSTE – VALIDAÇÃO FORMULÁRIO */
    const formAjuste = document.querySelector("form");

    if (formAjuste && document.getElementById("tipo_ajuste")) {

        formAjuste.addEventListener("submit", function (e) {

            const erroBox = document.getElementById("erro_validacao");
            erroBox.style.display = "none";
            erroBox.innerHTML = "";

            const campoPontoInput = document.getElementById("valor_novo_ponto");
            const campoPausaInput = document.getElementById("pausa_nova");

            campoPontoInput?.classList.remove("input-erro");
            campoPausaInput?.classList.remove("input-erro");

            const tipo = document.getElementById('tipo_ajuste').value;

            if (tipo === 'ponto') {
                const campo = document.getElementById('campo_ponto').value;
                const novoValor = campoPontoInput.value;

                const inicioAtual = campoPontoInput.dataset.inicio || "";
                const fimAtual = campoPontoInput.dataset.fim || "";

                if (!novoValor) return;

                if (campo === 'inicio_ponto' && fimAtual && novoValor > fimAtual) {
                    e.preventDefault();
                    mostrarErro("A entrada não pode ser depois da saída.", "valor_novo_ponto");
                    return;
                }

                if (campo === 'fim_ponto' && inicioAtual && novoValor < inicioAtual) {
                    e.preventDefault();
                    mostrarErro("A saída não pode ser antes da entrada.", "valor_novo_ponto");
                    return;
                }
            }

            if (tipo === 'pausa') {
                const campoPausa = document.getElementById('campo_pausa').value;
                const novaHora = campoPausaInput.value;

                if (!novaHora) {
                    e.preventDefault();
                    mostrarErro("O novo horário deve ser atribuído");
                    return;
                }

                const inicioPonto = campoPontoInput.dataset.inicio || "";
                const fimPonto = campoPontoInput.dataset.fim || "";

                const selectPausa = document.getElementById("id_pausa");
                const textoSelecionado = selectPausa.options[selectPausa.selectedIndex].text;

                const match = textoSelecionado.match(/Início:\s(\d{2}:\d{2}),\sFim:\s(\d{2}:\d{2})/);
                if (!match) return;

                const inicioPausaAtual = match[1];
                const fimPausaAtual = match[2];

                if (campoPausa === 'inicio_pausa') {
                    if (novaHora > fimPausaAtual) {
                        e.preventDefault();
                        mostrarErro("O início da pausa não pode ser depois do fim.", "pausa_nova");
                        return;
                    }

                    if (inicioPonto && novaHora < inicioPonto) {
                        e.preventDefault();
                        mostrarErro("A pausa não pode começar antes da entrada.", "pausa_nova");
                        return;
                    }
                }

                if (campoPausa === 'fim_pausa') {
                    if (novaHora < inicioPausaAtual) {
                        e.preventDefault();
                        mostrarErro("O fim da pausa não pode ser antes do início.", "pausa_nova");
                        return;
                    }

                    if (fimPonto && novaHora > fimPonto) {
                        e.preventDefault();
                        mostrarErro("A pausa não pode terminar depois da saída.", "pausa_nova");
                        return;
                    }
                }
            }

            function mostrarErro(mensagem, campoErroId) {
                erroBox.innerHTML = mensagem;
                erroBox.style.display = "block";

                if (campoErroId) {
                    document.getElementById(campoErroId)?.classList.add("input-erro");
                }
            }
        });
    }

    window.mostrarCamposAjuste = function () {
        const tipo = document.getElementById('tipo_ajuste').value;

        const ajustePonto = document.getElementById('ajuste_ponto');
        const ajustePausa = document.getElementById('ajuste_pausa');

        ajustePonto.style.display = 'none';
        ajustePausa.style.display = 'none';

        document.getElementById('campo_ponto').required = false;
        document.getElementById('valor_novo_ponto').required = false;
        document.getElementById('id_pausa').required = false;
        document.getElementById('campo_pausa').required = false;
        document.getElementById('pausa_nova').required = false;

        if (tipo === 'ponto') {
            ajustePonto.style.display = 'block';
            document.getElementById('campo_ponto').required = true;
            document.getElementById('valor_novo_ponto').required = true;
        }

        if (tipo === 'pausa') {
            ajustePausa.style.display = 'block';
            document.getElementById('id_pausa').required = true;
        }
    };

    /* VISUALIZAR FOLHA – FOLHA DE PAGAMENTO */
    const btnGerarPDF = document.getElementById("btnGerarPDF");
    const inputMes = document.getElementById("mes");

    if (btnGerarPDF && inputMes) {

        btnGerarPDF.addEventListener("click", () => {

            const mes = inputMes.value;
            const atual = new Date().toISOString().slice(0, 7);

            if (!mes || !/^\d{4}-\d{2}$/.test(mes)) {
                alert("Selecione um mês válido no formato YYYY-MM");
                return;
            }

            if (mes > atual) {
                alert("Você não pode escolher um mês futuro.");
                return;
            }

            window.open(`../../api/api_gerar_pdf.php?mes=${mes}`, "_blank");
        });
    }

    /* GERAR FOLHAS – FOLHA DE PAGAMENTO */
    const mesGerar = document.getElementById('mesGerar');
    const mesEvento = document.getElementById('mesEvento');
    const btnEvento = document.getElementById('add_evento');
    const btnGerar = document.getElementById('gerar_folhas');
    const btnRevisao = document.querySelector('.btn-revisar');
    const inputAcao = document.getElementById('inputAcao');
    const form = document.getElementById('Form');

    function ouvirEventos(mes, btn, btn2 = null) {
        if (!mes || !btn) return;

        const dataFixa = mes.value;

        mes.addEventListener('input', (event) => {
            const data = event.target.value;

            if (dataFixa === data) {
                btn.removeAttribute('disabled');
                if (btn2) btn2.removeAttribute('disabled');
            } else {
                btn.setAttribute('disabled', 'true');
                if (btn2) btn2.setAttribute('disabled', 'true');
            }
        });
    }

    ouvirEventos(mesGerar, btnGerar, btnRevisao);
    ouvirEventos(mesEvento, btnEvento);

    if (btnGerar) {
        btnGerar.addEventListener('click', () => {
            inputAcao.value = 'gerar';
            form.submit();
        });
    }

    if (btnRevisao) {
        btnRevisao.addEventListener('click', () => {
            const confirmacao = confirm(`Deseja marcar todas as folhas como revisado?
                    As folhas não poderão ser modificadas depois`
            );

            if (confirmacao) {
                inputAcao.value = 'revisar';
                form.submit();
            }
        });
    }

    /* MARCAR FOLHA COMO REVISADA */
    const btnRevisarFolha = document.querySelector(".btn-padrao");

    if (btnRevisarFolha) {
        btnRevisarFolha.addEventListener("click", () => {

            const idUsuario = btnRevisarFolha.dataset.id;

            fetch(window.location.href, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    acao: "revisar",
                    id_usuario: idUsuario
                })
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.msg);

                    if (data.status === "sucesso") {
                        window.location.href = "gerar_folhas_todos.php?mes=" + new URLSearchParams(window.location.search).get("mes");
                    }
                })
                .catch(err => {
                    console.error("Erro:", err);
                });
        });
    }

    /* EDITAR E DELETAR EVENTOS (HOLERITE) */

    // DELETAR
    document.querySelectorAll(".btn-deletar").forEach(btn => {
        btn.addEventListener("click", async () => {

            const id = btn.dataset.id;

            const confirmar = confirm("Tem certeza que deseja deletar este evento?");
            if (!confirmar) return;

            try {
                const response = await fetch(window.location.href, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        acao: "deletar",
                        id_evento: id,
                        confirmado: true
                    })
                });

                const data = await response.json();
                alert(data.msg);

                if (data.status === "sucesso") {
                    location.reload();
                }

            } catch (erro) {
                console.error("Erro:", erro);
                alert("Erro ao deletar evento.");
            }
        });
    });


    // EDITAR FOLHA (quando sair do input)
    document.querySelectorAll(".input-editar").forEach(input => {

        input.addEventListener("change", async () => {

            const id = input.id;
            const valor = input.value;

            try {
                const response = await fetch(window.location.href, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        acao: "editar",
                        id_editar: id,
                        valor: valor
                    })
                });

                const data = await response.json();

                if (data.status === "sucesso") {
                    console.log("Atualizado!");
                } else {
                    alert(data.msg);
                }

            } catch (erro) {
                console.error("Erro:", erro);
                alert("Erro ao editar.");
            }
        });

    });

    /* GERAR PDF – HOLERITE */
    const btnGerarPdf = document.getElementById("btnGerarPdf");

    if (btnGerarPdf) {

        btnGerarPdf.addEventListener("click", async () => {

            try {
                const element = document.getElementById("holerite");

                if (!element) {
                    alert("Elemento do holerite não encontrado.");
                    return;
                }

                const { jsPDF } = window.jspdf;

                const canvas = await html2canvas(element, { scale: 2 });
                const imgData = canvas.toDataURL("image/png");

                const pdf = new jsPDF("p", "mm", "a4");

                const pageWidth = pdf.internal.pageSize.getWidth();
                const imgHeight = (canvas.height * pageWidth) / canvas.width;

                pdf.addImage(imgData, "PNG", 0, 0, pageWidth, imgHeight);

                pdf.save("holerite.pdf");

            } catch (erro) {
                console.error("Erro ao gerar PDF:", erro);
                alert("Erro ao gerar PDF.");
            }
        });
    }

    // CONFIGURAÇÕES – EMPRESA
    document.addEventListener("DOMContentLoaded", () => {
        const btnSalvar = document.querySelector('.btn-salvar');
        const btnExcluir = document.querySelector('.btn-excluir');

        if (btnSalvar) {
            btnSalvar.addEventListener('click', function () {
                if (!confirm('Salvar dados?')) return;
                const idUsuario = this.dataset.id;
                const formData = new FormData(document.getElementById('meuForm'));
                let dicionario = {};
                formData.forEach((v, k) => dicionario[k] = v);

                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'salvar',
                        id_usuario: idUsuario,
                        dicionario
                    })
                })
                    .then(r => r.json())
                    .then(d => {
                        alert(d.msg);
                        if (d.status === 'sucesso') location.reload();
                    });
            });
        }

        if (btnExcluir) {
            btnExcluir.addEventListener('click', function () {
                if (!confirm('Excluir dados?')) return;

                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ acao: 'excluir' })
                })
                    .then(r => r.json())
                    .then(d => {
                        alert(d.msg);
                        if (d.status === 'sucesso') location.reload();
                    });
            });
        }
    });

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
                        jornada,
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

    const filtroJornada = document.getElementById("filtro-jornada");
    const linhasJornada = document.querySelectorAll("#tabela-jornada tbody tr");

    if (filtroJornada && linhasJornada.length) {
        filtroJornada.addEventListener("change", aplicarFiltroJornada);

        function aplicarFiltroJornada() {
            const valor = filtroJornada.value;
            let visiveis = 0;

            linhasJornada.forEach(linha => {
                if (linha.id === "linha-vazia-jornada") return;
                const status = linha.dataset.status;
                let mostrar = true;
                if (valor === "ativos" && status !== "ativo") mostrar = false;
                if (valor === "inativos" && status !== "inativo") mostrar = false;
                linha.style.display = mostrar ? "" : "none";
                if (mostrar) visiveis++;
            });

            const linhaVazia = document.getElementById("linha-vazia-jornada");

            if (visiveis === 0) {
                let texto = "Nenhuma jornada encontrada.";
                if (valor === "ativos") texto = "Nenhuma jornada ativa encontrada.";
                if (valor === "inativos") texto = "Nenhuma jornada inativa encontrada.";
                linhaVazia.style.display = "";
                linhaVazia.querySelector("td").innerText = texto;
            } else {
                linhaVazia.style.display = "none";
            }
        }

        aplicarFiltroJornada();
    }

    // CONFIGURAÇÕES - PAUSAS
    const filtroPausa = document.getElementById("filtro-pausa");
    const linhasPausa = document.querySelectorAll("#tabela-pausa tbody tr");

    if (filtroPausa && linhasPausa.length) {

        filtroPausa.addEventListener("change", aplicarFiltroPausa);

        function aplicarFiltroPausa() {
            const valor = filtroPausa.value;
            let visiveis = 0;

            linhasPausa.forEach(linha => {
                if (linha.id === "linha-vazia-pausa") return;
                const status = linha.dataset.status;
                let mostrar = true;
                if (valor === "ativos" && status !== "ativo") mostrar = false;
                if (valor === "inativos" && status !== "inativo") mostrar = false;
                linha.style.display = mostrar ? "" : "none";
                if (mostrar) visiveis++;
            });

            const linhaVazia = document.getElementById("linha-vazia-pausa");

            if (visiveis === 0) {
                let texto = "Nenhuma pausa encontrada.";
                if (valor === "ativos") texto = "Nenhuma pausa ativa encontrada.";
                if (valor === "inativos") texto = "Nenhuma pausa inativa encontrada.";
                linhaVazia.style.display = "";
                linhaVazia.querySelector("td").innerText = texto;
            } else {
                linhaVazia.style.display = "none";
            }
        }

        aplicarFiltroPausa();
    }

    // CONFIGURAÇÃO - SETORES
    let setorAtual = null;

    document.querySelectorAll(".btn-editar-setor").forEach(btn => {
        btn.addEventListener("click", async () => {
            setorAtual = btn.dataset.id;
            const nomeSetor = btn.dataset.nome;

            document.getElementById("titulo-setor").innerText =
                `Gerenciar Usuários de ${nomeSetor}`;

            const painel = document.getElementById("editar-usuarios-setor");
            painel.classList.remove("hidden");
            await carregarListas(setorAtual);
        });
    });

    function criarCheckboxUsuario(usuario, checked = false) {
        const label = document.createElement("label");
        label.classList.add("label", "box-config");

        label.innerHTML = `
            <input class="input" type="checkbox" value="${usuario.id_usuario}" ${checked ? "checked" : ""}>
            <span>${usuario.nome_usuario}</span>
        `;

        return label;
    }

    async function carregarListas(idSetor) {
        const res1 = await fetch(`../../api/api_setores.php?acao=get_pessoas_setor`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_setor: idSetor })
        });

        const dadosSetor = await res1.json();

        const res2 = await fetch(`../../api/api_setores.php?acao=get_sem_setor`, {
            method: 'POST'
        });

        const dadosSemSetor = await res2.json();
        const containerSetor = document.getElementById("usuarios-no-setor");
        const containerLivre = document.getElementById("usuarios-sem-setor");
        containerSetor.innerHTML = "";
        containerLivre.innerHTML = "";

        dadosSetor.dados.forEach(u => {
            containerSetor.appendChild(criarCheckboxUsuario(u, true));
        });

        dadosSemSetor.dados.forEach(u => {
            containerLivre.appendChild(criarCheckboxUsuario(u, false));
        });
    }

    document.getElementById("salvar-edicao-setor").addEventListener("click", async () => {
        const usuariosSelecionados = Array.from(
            document.querySelectorAll('#editar-usuarios-setor input[type="checkbox"]:checked')
        ).map(cb => parseInt(cb.value));

        try {
            const response = await fetch(`../../api/api_setores.php?acao=set_setor`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_setor: parseInt(setorAtual),
                    usuarios_selecionado: usuariosSelecionados,
                    nome_setor: "temp",
                    id_tempo: 1
                })
            });

            const resultado = await response.json();

            if (resultado.sucesso) {
                alert("Atualizado com sucesso!");
                location.reload();
            } else {
                alert("Erro: " + resultado.mensagem);
            }
        } catch (error) {
            console.error(error);
            alert("Erro ao salvar");
        }
    });

    const filtroSetor = document.getElementById("filtro-setor");

    if (filtroSetor) {
        function aplicarFiltroSetor() {
            const valor = filtroSetor.value;
            let visiveis = 0;

            document.querySelectorAll("#tabela-setores tbody tr").forEach(tr => {
                if (tr.id === "linha-vazia") return;

                const status = tr.dataset.status;

                if (valor === "todos") {
                    tr.style.display = "";
                    visiveis++;
                } else if (valor === "ativos" && status === "ativo") {
                    tr.style.display = "";
                    visiveis++;
                } else if (valor === "inativos" && status === "inativo") {
                    tr.style.display = "";
                    visiveis++;
                } else {
                    tr.style.display = "none";
                }
            });

            const linhaVazia = document.getElementById("linha-vazia");

            if (visiveis === 0) {
                let texto = "Nenhum setor encontrado.";

                if (valor === "ativos") texto = "Nenhum setor ativo encontrado.";
                if (valor === "inativos") texto = "Nenhum setor inativo encontrado.";

                linhaVazia.style.display = "";
                linhaVazia.querySelector("td").innerText = texto;
            } else {
                linhaVazia.style.display = "none";
            }
        }

        filtroSetor.addEventListener("change", aplicarFiltroSetor);
        aplicarFiltroSetor();
    }
});