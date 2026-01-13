document.addEventListener("DOMContentLoaded", () => {

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

    next.addEventListener("click", () => {
        mostrarSlide((index + 1) % slides.length);
    });

    prev.addEventListener("click", () => {
        mostrarSlide((index - 1 + slides.length) % slides.length);
    });

    dots.forEach((dot, i) => {
        dot.addEventListener("click", () => {
            mostrarSlide(i);
            pararAuto();
            iniciarAuto();
        });
    });

    area.addEventListener("mouseenter", pararAuto);
    area.addEventListener("mouseleave", iniciarAuto);

    iniciarAuto();
});
