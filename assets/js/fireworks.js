// Fireworks effect for celebration
(function() {
  const canvas = document.getElementById('canvas');
  const ctx = canvas.getContext('2d');
  
  // Get cans-container boundaries for limiting fireworks
  let cansContainer = document.querySelector('.cans-container');
  let containerBounds = {
    left: 0,
    top: 0,
    right: window.innerWidth,
    bottom: window.innerHeight,
    width: window.innerWidth,
    height: window.innerHeight
  };
  
  function updateContainerBounds() {
    if (cansContainer) {
      const rect = cansContainer.getBoundingClientRect();
      containerBounds = {
        left: rect.left,
        top: rect.top,
        right: rect.right,
        bottom: rect.bottom,
        width: rect.width,
        height: rect.height
      };
    }
  }
  updateContainerBounds();
  
  // Ensure canvas dimensions
  function resizeCanvas() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    updateContainerBounds();
  }
  resizeCanvas();
  
  // Array to store fireworks
  let fireworks = [];
  let particles = [];
  
  // Colors for fireworks - patriotic colors
  const colors = ['#F4F42E', '#D54133'];
  
  // Firework class
  class Firework {
    constructor() {
      // Start from bottom of container instead of bottom of screen
      this.x = containerBounds.left + Math.random() * containerBounds.width;
      this.y = containerBounds.bottom;
      // Target is somewhere in the top third of container
      this.targetY = containerBounds.top + Math.random() * (containerBounds.height / 3);
      this.speed = 2 + Math.random() * 3;
      this.angle = Math.PI/2; // Straight up
      this.radius = 2;
      this.color = colors[Math.floor(Math.random() * colors.length)];
      this.opacity = 1;
    }
    
    update() {
      this.y -= this.speed;
      
      // If the firework reaches its target or top of container, explode
      if (this.y <= this.targetY || this.y <= containerBounds.top) {
        // Create explosion particles
        for (let i = 0; i < 50; i++) {
          particles.push(new Particle(this.x, this.y, this.color));
        }
        return true; // Signal for removal
      }
      return false;
    }
    
    draw() {
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = this.color;
      ctx.fill();
      
      // Add a trail
      ctx.beginPath();
      ctx.moveTo(this.x, this.y);
      ctx.lineTo(this.x, this.y + 10);
      ctx.strokeStyle = this.color;
      ctx.stroke();
    }
  }
  
  // Explosion particles
  class Particle {
    constructor(x, y, color) {
      this.x = x;
      this.y = y;
      this.color = color;
      this.radius = Math.random() * 2;
      this.speed = Math.random() * 5;
      this.angle = Math.random() * Math.PI * 2; // Random direction
      this.friction = 0.95;
      this.gravity = 0.1;
      this.opacity = 1;
      this.velocityX = Math.cos(this.angle) * this.speed;
      this.velocityY = Math.sin(this.angle) * this.speed;
    }
    
    update() {
      this.velocityX *= this.friction;
      this.velocityY *= this.friction;
      this.velocityY += this.gravity;
      
      this.x += this.velocityX;
      this.y += this.velocityY;
      
      // Remove particles if they go outside container
      if (this.x < containerBounds.left || this.x > containerBounds.right || 
          this.y < containerBounds.top || this.y > containerBounds.bottom) {
        return true;
      }
      
      this.opacity -= 0.01;
      
      return this.opacity <= 0; // Signal for removal when opacity is 0
    }
    
    draw() {
      ctx.globalAlpha = this.opacity;
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = this.color;
      ctx.fill();
      ctx.globalAlpha = 1;
    }
  }
  
  // Animation loop 
  function animate() {
    // Clear canvas completely each frame to make it transparent
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Update container bounds in case of scrolling
    updateContainerBounds();
    
    // Randomly add new fireworks (lower probability)
    if (Math.random() < 0.02 && containerBounds.height > 0) {
      fireworks.push(new Firework());
    }
    
    // Update and draw fireworks
    for (let i = fireworks.length - 1; i >= 0; i--) {
      if (fireworks[i].update()) {
        fireworks.splice(i, 1);
      } else {
        fireworks[i].draw();
      }
    }
    
    // Update and draw particles
    for (let i = particles.length - 1; i >= 0; i--) {
      if (particles[i].update()) {
        particles.splice(i, 1);
      } else {
        particles[i].draw();
      }
    }
    
    requestAnimationFrame(animate);
  }
  
  // Handle resizing
  window.addEventListener('resize', resizeCanvas);
  
  // Handle scroll events to update container position
  window.addEventListener('scroll', updateContainerBounds);
  
  // Start animation
  animate();
})(); 