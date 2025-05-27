/*=============== SHOW MENU ===============*/
const navMenu = document.getElementById("nav-menu"),
  navToggle = document.getElementById("nav-toggle"),
  navClose = document.getElementById("nav-close");

/*===== Menu Show =====*/
/* Validate if constant exists */
if (navToggle) {
  navToggle.addEventListener("click", () => {
    navMenu.classList.add("show-menu");
  });
}

/*===== Hide Show =====*/
/* Validate if constant exists */
if (navClose) {
  navClose.addEventListener("click", () => {
    navMenu.classList.remove("show-menu");
  });
}


/*=============== SWIPER CATEGORIES ===============*/
let swiperCategories = new Swiper(".categories__container", {
  spaceBetween: 24,
  loop: true,
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },

  breakpoints: {
    350: {
      slidesPerView: 2,
      spaceBetween: 24,
    },
    768: {
      slidesPerView: 3,
      spaceBetween: 24,
    },
    992: {
      slidesPerView: 4,
      spaceBetween: 24,
    },
    1200: {
      slidesPerView: 5,
      spaceBetween: 24,
    },
    1400: {
      slidesPerView: 6,
      spaceBetween: 24,
    },
  },
});

/*=============== SWIPER PRODUCTS ===============*/
let swiperProducts = new Swiper(".new__container", {
  spaceBetween: 24,
  loop: true,
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },

  breakpoints: {
    768: {
      slidesPerView: 2,
      spaceBetween: 24,
    },
    992: {
      slidesPerView: 4,
      spaceBetween: 24,
    },
    1400: {
      slidesPerView: 4,
      spaceBetween: 24,
    },
  },
});

/*=============== PRODUCTS TABS ===============*/
const tabs = document.querySelectorAll("[data-target]");
const tabsContents = document.querySelectorAll("[content]");
const menuToggle = document.querySelector(".menu-toggle"); // Nút toggle menu
const accountTabs = document.querySelector(".account__tabs"); // Menu tabs

// Xử lý khi click vào tab
tabs.forEach((tab) => {
  tab.addEventListener("click", () => {
    const target = document.querySelector(tab.dataset.target);

    // Xóa class 'active-tab' khỏi tất cả nội dung
    tabsContents.forEach((tabsContent) => {
      tabsContent.classList.remove("active-tab");
    });

    // Thêm class 'active-tab' vào nội dung được chọn
    target.classList.add("active-tab");

    // Xóa class 'active-tab' khỏi tất cả các tab
    tabs.forEach((tab) => {
      tab.classList.remove("active-tab");
    });

    // Thêm class 'active-tab' vào tab được chọn
    tab.classList.add("active-tab");

    // Nếu menu đang được ẩn/hiện trên màn hình nhỏ, đóng menu sau khi chọn tab
    if (window.innerWidth <= 738) {
      accountTabs.classList.remove("active"); // Đóng menu
    }
  });
});

// Xử lý toggle menu khi nhấn nút menu trên màn hình nhỏ
menuToggle.addEventListener("click", () => {
  accountTabs.classList.toggle("active"); // Thêm/xóa class 'active' để mở/đóng menu
});
