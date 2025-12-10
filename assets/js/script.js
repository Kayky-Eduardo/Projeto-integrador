// =========
// INDEX.PHP
// =========
document.addEventListener("DOMContentLoaded", () => {
    const slides = document.querySelectorAll(".slide");
    const prev = document.querySelector(".prev");
    const next = document.querySelector(".next");
    const dots = document.querySelectorAll(".dot");
    const area = document.querySelector(".carrossel");

    if (!slides.length) return;

    let index = 0;
    let interval;

    function mostrarSlide(i) {
        slides.forEach(slide => slide.classList.remove("ativo"));
        dots.forEach(dot => dot.classList.remove("ativo"));

        slides[i].classList.add("ativo");
        dots[i].classList.add("ativo");

        index = i;
    }

    function iniciarAuto() {
        interval = setInterval(() => {
            let novoIndex = (index + 1) % slides.length;
            mostrarSlide(novoIndex);
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
});

// ==================
// USUARIOS/LISTA.PHP
// ==================
document.addEventListener("DOMContentLoaded", () => {
    const campoBusca = document.getElementById("busca");
    const container = document.getElementById("tabelaUsuarios");

    if (!campoBusca || !container) return;

    campoBusca.addEventListener("keyup", () => {
        const termo = campoBusca.value.toLowerCase();
        const cards = container.querySelectorAll(".usuario-card");

        cards.forEach(card => {
            const texto = card.innerText.toLowerCase();
            card.style.display = texto.includes(termo) ? "" : "none";
        });
    });
});

// ====================
// USUARIO/CADASTRO.PHP
// ====================
document.addEventListener("DOMContentLoaded", () => {
    const cpf = document.querySelector('[name="cpf_usuario"]');
    const rg = document.querySelector('[name="rg_usuario"]');
    const telefone = document.querySelector('[name="telefone"]');
    const cep = document.querySelector('[name="cep"]');

    if (!cpf && !rg && !telefone && !cep) return;

    if (cpf) IMask(cpf, { mask: '000.000.000-00' });
    if (rg) IMask(rg, { mask: '00.000.000-0' });
    if (telefone) IMask(telefone, { mask: '(00) 00000-0000' });
    if (cep) IMask(cep, { mask: '00000-000' });
});

document.addEventListener("DOMContentLoaded", () => {

    const inputFoto = document.getElementById("input-foto");
    const preview = document.getElementById("preview-foto");

    if (!inputFoto || !preview) return;

    inputFoto.addEventListener("change", () => {
        const file = inputFoto.files[0];
        if (!file) return;

        const reader = new FileReader();

        reader.onload = (e) => {
            preview.src = e.target.result;
        };

        reader.readAsDataURL(file);
    });
});