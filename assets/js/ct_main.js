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

// HIỆU ỨNG CÁC LON DI CHUYỂN KHI NGƯỜI DÙNG SCROLL MÀN HÌNH
window.addEventListener('scroll', function() {
  const heroSection = document.querySelector('.sunkissed_hero');
  const cans = document.querySelectorAll('.can');
  const heroRect = heroSection.getBoundingClientRect();
  const scrollPosition = window.scrollY;

  // Kiểm tra nếu khu vực hero vẫn còn trong viewport
  if (heroRect.top <= window.innerHeight && heroRect.bottom >= 0) {
      const relativeScroll = scrollPosition - heroSection.offsetTop;

      // Tùy chỉnh sự chuyển động của từng lon
      cans.forEach((can) => {
          let translateX = 0;
          let translateY = 0;
          let rotate = 0;

          // Tùy chỉnh sự chuyển động của từng lon
          if (can.classList.contains('loncaphe')) {
              translateX = relativeScroll * 0.05;
              translateY = relativeScroll * 0.1;
              rotate = -10;
          } else if (can.classList.contains('loncam')) {
              translateX = relativeScroll * -0.05;
              translateY = relativeScroll * 0.05;
              rotate = 10;
          } else if (can.classList.contains('lonchanh')) {
              translateX = relativeScroll * 0.1;
              translateY = relativeScroll * 0.05;
              rotate = 20;
          } else if (can.classList.contains('lonvq')) {
              translateX = relativeScroll * -0.15;
              translateY = relativeScroll * 0.02;
              rotate = 20; 
          } else if (can.classList.contains('londuahau')) {
              translateX = relativeScroll * 0.15;
              translateY = relativeScroll * 0.02;
              rotate = -10;
          }

          // Di chuyển và xoay lon
          can.style.transform = `translate(${translateX}px, ${translateY}px) rotate(${rotate}deg)`;
      });
  } else {
      // Giữ nguyên vị trí mới khi ra khỏi khu vực hero
      cans.forEach(can => {
          const computedStyle = window.getComputedStyle(can);
          const matrix = new WebKitCSSMatrix(computedStyle.transform);
          can.dataset.translateX = matrix.m41;
          can.dataset.translateY = matrix.m42;
          can.dataset.rotate = matrix.a; // Lưu giá trị xoay hiện tại
      });
  }

  // Thêm đoạn mã mới cho section .brandintro
  const brandIntro = document.querySelector('.brandintro');
  const leftHand = document.querySelector('.left-hand');
  const rightHand = document.querySelector('.right-hand');
  const text = document.querySelector('.brandintro .text');
  const imageNoColor = document.querySelector('.image-no-color');
  const brandIntroPosition = brandIntro.getBoundingClientRect().top;
  const screenPosition = window.innerHeight / 1.5;

  if (brandIntroPosition < screenPosition) {
      if (!leftHand.classList.contains('moved') && !rightHand.classList.contains('moved')) {
          leftHand.style.transform = 'translateX(-37px) rotate(10deg)'; // Thêm hiệu ứng phóng to
          rightHand.style.transform = 'translateX(35px) rotate(-10deg)'; // Thêm hiệu ứng phóng to
          leftHand.classList.add('moved');
          rightHand.classList.add('moved');
      }
      // Di chuyển đoạn text lên trên
      text.style.transform = 'translateY(-100px)';
      // Di chuyển hình ảnh lên trên
      imageNoColor.style.transform = 'translateY(-40px)';
  } else {
      // Di chuyển tay trở lại vị trí ban đầu khi cuộn lên
      if (leftHand.classList.contains('moved') && rightHand.classList.contains('moved')) {
          leftHand.style.transform = 'translateX(-150px) rotate(0deg)';
          rightHand.style.transform = 'translateX(120px) rotate(0deg)';
          leftHand.classList.remove('moved');
          rightHand.classList.remove('moved');
      }
      // Đưa đoạn text trở lại vị trí ban đầu
      text.style.transform = 'translateY(-120px)';
      // Đưa hình ảnh trở lại vị trí ban đầu
      imageNoColor.style.transform = 'translateY(110px)';
  }

  // Thêm đoạn mã mới cho section .spnb
  const spnbSection = document.querySelector('.spnb');
  const canNames = document.querySelectorAll('.can_name');
  const spnbRect = spnbSection.getBoundingClientRect();

  if (spnbRect.top <= window.innerHeight / 6.5 && spnbRect.bottom >= 0) {
      // Hiện tên sản phẩm khi cuộn đến một nửa section .spnb
      canNames.forEach(canName => {
          canName.style.opacity = '1';
          canName.style.transform = 'translateY(0)';
      });
  }
});

/*=============== FAQ ===============*/
const tabs = document.querySelectorAll("[data-target]"),
      tabsContents = document.querySelectorAll("[content]");

tabs.forEach((tab) => {
  tab.addEventListener("click", () => {
    const target = document.querySelector(tab.dataset.target);

    tabsContents.forEach((tabsContent) => {
      tabsContent.classList.remove("active-tab");
    });

    target.classList.add("active-tab");

    tabs.forEach((tab) => {
      tab.classList.remove("active-tab");
    });

    tab.classList.add("active-tab");
  });
});

document.querySelectorAll('.faq-item').forEach(item => {
    item.addEventListener('click', () => {
        item.classList.toggle('active');
    });
});
/*=============== n/FAQ ===============*/

// BLOG
document.querySelectorAll('.blog-card').forEach(card => {
    card.addEventListener('mouseover', () => {
        card.style.boxShadow = '0 6px 12px rgba(0, 0, 0, 0.2)';
    });
    card.addEventListener('mouseout', () => {
        card.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
    });
});


// Lấy nút lên đầu trang
const scrollToTopButton = document.getElementById('scroll-to-top');

// Hiển thị hoặc ẩn nút khi cuộn
window.addEventListener('scroll', () => {
  if (window.scrollY > 200) {
    scrollToTopButton.classList.add('show'); // Hiển thị nút
  } else {
    scrollToTopButton.classList.remove('show'); // Ẩn nút
  }
});

// Cuộn lên đầu trang khi nhấn nút
scrollToTopButton.addEventListener('click', () => {
  window.scrollTo({
    top: 0,
    behavior: 'smooth' // Cuộn mượt mà
  });
});
  
// HIỆU ỨNG CON TRỎ CHUỘT

// Khởi tạo biến và màu sắc
const coords = { x: 0, y: 0 };
const circles = document.querySelectorAll(".circle");

const colors = [
  "#ffb56b",
  "#fdaf69",
  "#f89d63",
  "#f59761",
  "#ef865e",
  "#ec805d",
  "#ef865e",
  "#ec805d",
  "#ef865e",
  "#ec805d",
  "#ef865e",
  "#ec805d",
  "#ef865e",
  "#ec805d",
  "#ef865e",
  "#ec805d",
];
// Thiết lập màu sắc cho các vòng tròn
circles.forEach(function (circle, index) {
  circle.x = 0;
  circle.y = 0;
  circle.style.backgroundColor = colors[index % colors.length];
});
// Lắng nghe sự kiện di chuyển chuột
window.addEventListener("mousemove", function(e){
  coords.x = e.clientX;
  coords.y = e.clientY;
  
});
  
function animateCircles() {
  
  let x = coords.x;
  let y = coords.y;
  
  circles.forEach(function (circle, index) {
    circle.style.left = x - 12 + "px";
    circle.style.top = y - 12 + "px";
    
    circle.style.scale = (circles.length - index) / circles.length;
    
    circle.x = x;
    circle.y = y;

    const nextCircle = circles[index + 1] || circles[0];
    x += (nextCircle.x - x) * 0.3;
    y += (nextCircle.y - y) * 0.3;
  });
 
  requestAnimationFrame(animateCircles);
}

animateCircles();

// N/HIỆU ỨNG CON TRỞ CHUỘT
