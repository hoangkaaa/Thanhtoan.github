// Lấy phần tử canvas từ DOM và thiết lập ngữ cảnh 2D
let canvas = document.getElementById("canvas");
let context = canvas.getContext("2d");

// Lấy phần tử section mà hiệu ứng tuyết sẽ được áp dụng
let section = document.querySelector(".sunkissed_hero");

// Đặt kích thước canvas bằng với kích thước của section
canvas.width = section.offsetWidth;
canvas.height = section.offsetHeight;

// Tạo các mảng chứa các hạt tuyết thuộc ba lớp (gần, trung bình, xa)
let nearParticles = [],
    middleParticles = [],
    farParticles = [];

// Cài đặt số lượng hạt tuyết cho mỗi lớp
let particleSettings = {
    count: 250, // Số lượng hạt tuyết cho mỗi lớp. Có thể tăng/giảm tuỳ theo nhu cầu.
};

// Đảm bảo `requestAnimationFrame` tương thích trên các trình duyệt khác nhau
window.requestAnimationFrame =
    window.requestAnimationFrame ||
    window.webkitRequestAnimationFrame ||
    window.mozRequestAnimationFrame ||
    window.oRequestAnimationFrame ||
    window.msRequestAnimationFrame ||
    function (callback) {
        window.setTimeout(callback, 1000 / 60); // Dự phòng: chạy callback mỗi 16ms
    };

// Hàm tạo số ngẫu nhiên trong một khoảng nhất định
function randomNumberGenerator(min, max) {
    return Math.random() * (max - min) + min;
}

// Hàm tạo các hạt tuyết cho từng lớp (gần, trung bình, xa)
function createsnowfall(particles, flag) {
    while (particles.length < particleSettings.count) {
        let particle;
        // Tạo các hạt tùy theo lớp
        if (flag === "near") {
            particle = new Particle(4, 0.9, 0.3); // Kích thước lớn, alpha cao, vận tốc lớn
        } else if (flag === "middle") {
            particle = new Particle(3, 0.5, 0.2); // Kích thước trung bình, alpha trung bình
        } else {
            particle = new Particle(2, 0.3, 0.1); // Kích thước nhỏ, alpha thấp, vận tốc chậm
        }
        particle.color = `rgb(255,255,255)`; // Màu hạt tuyết là trắng
        particles.push(particle); // Thêm hạt vào mảng
    }
}

// Hàm khởi động hiệu ứng tuyết rơi
function startSnowFall() {
    // Xóa nội dung canvas trước khi vẽ lại
    context.clearRect(0, 0, canvas.width, canvas.height);

    // Tạo các hạt tuyết cho từng lớp
    createsnowfall(nearParticles, "near");
    createsnowfall(farParticles, "far");
    createsnowfall(middleParticles, "middle");

    // Kết hợp và sắp xếp ngẫu nhiên các hạt từ ba lớp
    let particles = [...nearParticles, ...middleParticles, ...farParticles];
    particles = particles.sort(() => 0.5 - Math.random());

    // Vẽ các hạt tuyết
    for (let i in particles) {
        particles[i].draw();

        // Nếu hạt vượt khỏi chiều cao canvas, đưa nó về vị trí ngẫu nhiên phía trên canvas
        if (particles[i].y > canvas.height) {
            particles[i].y = Math.random() * canvas.height - canvas.height;
        }
    }

    // Gọi lại `startSnowFall` để tạo vòng lặp hiệu ứng
    window.requestAnimationFrame(startSnowFall);
}

// Hàm tạo đối tượng Particle (hạt tuyết)
function Particle(areaValue, alphaValue, vyValue) {
    this.area = areaValue; // Kích thước hạt
    this.x = Math.random() * canvas.width; // Vị trí ngẫu nhiên trên trục x
    this.y = Math.random() * canvas.height - canvas.height; // Vị trí ban đầu phía trên canvas
    this.alpha = alphaValue; // Độ trong suốt của hạt
    this.vy = vyValue * 20; // Tốc độ rơi
}

// Thêm phương thức vẽ cho đối tượng Particle
Particle.prototype = {
    draw: function () {
        // Tăng vị trí y của hạt dựa trên vận tốc và hiệu ứng dao động
        this.y += (Math.cos(this.area) + this.vy) * 0.3;

        // Lưu trạng thái ngữ cảnh, vẽ hạt, rồi khôi phục trạng thái
        context.save();
        context.beginPath();
        context.arc(this.x, this.y, this.area, 0, Math.PI * 2); // Vẽ hình tròn cho hạt
        context.fillStyle = this.color; // Thiết lập màu
        context.globalAlpha = this.alpha; // Thiết lập độ trong suốt
        context.closePath();
        context.fill(); // Đổ màu
        context.restore();
    },
};

// Khi trang được tải, khởi động hiệu ứng
window.onload = () => {
    // Cập nhật kích thước canvas theo section
    canvas.width = section.offsetWidth;
    canvas.height = section.offsetHeight;

    // Xóa dữ liệu cũ trong các mảng hạt tuyết
    nearParticles = [];
    middleParticles = [];
    farParticles = [];

    // Bắt đầu hiệu ứng tuyết rơi
    window.requestAnimationFrame(startSnowFall);
};

// Lắng nghe sự kiện thay đổi kích thước màn hình và cập nhật kích thước canvas
window.addEventListener('resize', () => {
    canvas.width = section.offsetWidth;
    canvas.height = section.offsetHeight;
});
