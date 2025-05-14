const hamburger = document.getElementById("hamburger");
const mobileMenu = document.getElementById("mobile-menu");
const closeBtn = document.getElementById("close-mobile-menu");
const overlay = document.getElementById("overlay");

function openMenu() {
    mobileMenu.style.right = "0%";
    overlay.style.display = "block";
}

function closeMenu() {
    mobileMenu.style.right = "-100%";
    overlay.style.display = "none";
}

hamburger.addEventListener("click", openMenu);
closeBtn.addEventListener("click", closeMenu);
overlay.addEventListener("click", closeMenu);

// Toggle submenus on mobile
const mobileDropdowns = mobileMenu.querySelectorAll(".dropdown > a");

mobileDropdowns.forEach(link => {
    link.addEventListener("click", e => {
        e.preventDefault(); // prevent default anchor behavior
        const parentLi = link.parentElement;
        parentLi.classList.toggle("open");
    });
});
