document.addEventListener("DOMContentLoaded", () => {

    /* =====================
       CARROSSEL
    ====================== */
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

    /* =====================
       MÁSCARAS
    ====================== */
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

    /* =====================
       PREVIEW DA FOTO
    ===================== */
    const inputFoto = document.getElementById("input-foto");
    const previewFoto = document.getElementById("preview-foto");
    const btnSalvarFoto = document.getElementById("btn-salvar-foto");

    if (inputFoto && previewFoto && btnSalvarFoto) {
        inputFoto.addEventListener("change", () => {
            const file = inputFoto.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = () => {
                previewFoto.src = reader.result;
            };
            reader.readAsDataURL(file);

            // mostra o botão "Salvar ajustes"
            btnSalvarFoto.classList.remove("hidden");
        });
    }

    /* =====================
       BOTÃO EDITAR (PAINEL)
    ====================== */
    const btnEditar = document.getElementById("btn-editar");
    const painelEdicao = document.getElementById("painel-edicao");

    if (btnEditar && painelEdicao) {
        btnEditar.addEventListener("click", () => {
            painelEdicao.classList.toggle("ativo");
        });
    }

});
