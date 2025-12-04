document.addEventListener("DOMContentLoaded", () => {

    const slides = document.querySelectorAll(".slide");
    const prev = document.querySelector(".prev");
    const next = document.querySelector(".next");
    const area = document.querySelector("section"); // área do carrossel

    let index = 0;
    let interval;

    function mostrarSlide(i) {
        slides.forEach(slide => slide.classList.remove("ativo"));
        slides[i].classList.add("ativo");
    }

    function iniciarAuto() {
        interval = setInterval(() => {
            index = (index + 1) % slides.length;
            mostrarSlide(index);
        }, 6000);
    }

    function pararAuto() {
        clearInterval(interval);
    }

    next.addEventListener("click", () => {
        index = (index + 1) % slides.length;
        mostrarSlide(index);
    });

    prev.addEventListener("click", () => {
        index = (index - 1 + slides.length) % slides.length;
        mostrarSlide(index);
    });

    // Pausa ao passar o mouse
    area.addEventListener("mouseenter", pararAuto);
    area.addEventListener("mouseleave", iniciarAuto);

    // Inicia o carrossel automaticamente
    iniciarAuto();
});
