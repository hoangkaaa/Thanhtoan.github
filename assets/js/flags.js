// Flying flags effect for celebration
(function() {
  // Wait for the fireworks script to initialize first
  setTimeout(() => {
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');
    const flags = [];
    const flagImage = new Image();
    flagImage.src = './assets/img/vnflag.png'; // Path to the flag image
    
    // Get cans-container boundaries for limiting flag movement
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
    
    // Add a glowing effect
    function generateLight(x, y, size, color) {
      const gradient = ctx.createRadialGradient(x, y, 0, x, y, size);
      gradient.addColorStop(0, color);
      gradient.addColorStop(1, 'rgba(255, 0, 0, 0)');
      
      ctx.save();
      ctx.globalCompositeOperation = 'lighter';
      ctx.fillStyle = gradient;
      ctx.beginPath();
      ctx.arc(x, y, size, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
    }
    
    // Initialize flags once the image is loaded
    flagImage.onload = () => {
      createFlags(15); // Create 15 flying flags
      // Start animation
      animate();
    };
    
    // Create flags
    function createFlags(count) {
      updateContainerBounds();
      
      for (let i = 0; i < count; i++) {
        // Create flags only within cans-container boundaries
        flags.push({
          x: containerBounds.left + Math.random() * containerBounds.width,
          y: containerBounds.top + Math.random() * containerBounds.height,
          width: 30 + Math.random() * 20, // Random width between 30 and 50px
          height: 20 + Math.random() * 10, // Proportional height
          speedX: Math.random() * 1 - 0.5, // Slower horizontal speed
          speedY: Math.random() * 1 - 0.5, // Slower vertical speed
          rotation: Math.random() * 0.1 - 0.05, // Small random rotation
          rotationAngle: Math.random() * Math.PI * 2, // Random initial rotation
          opacity: 0.7 + Math.random() * 0.3, // More opaque (0.7-1.0)
          waveAmplitude: 0.5 + Math.random() * 1.5, // Random wave effect
          waveFrequency: 0.02 + Math.random() * 0.03,
          waveOffset: Math.random() * Math.PI * 2,
          glowSize: 15 + Math.random() * 10, // Glow effect size
          glowColor: 'rgba(255, 255, 255, 0)' // Red-orange glow color
        });
      }
    }
    
    function drawFlag(flag) {
      ctx.save();
      ctx.globalAlpha = flag.opacity;
      
      // Move to the flag position
      ctx.translate(flag.x, flag.y);
      
      // Apply rotation
      ctx.rotate(flag.rotationAngle);
      
      // Add wave effect
      const waveY = Math.sin(flag.waveOffset) * flag.waveAmplitude;
      ctx.translate(0, waveY);
      
      // Draw the flag
      ctx.drawImage(
        flagImage, 
        -flag.width / 2, 
        -flag.height / 2, 
        flag.width, 
        flag.height
      );
      
      // Reset transformation before drawing glow
      ctx.restore();
      
      // Add glow effect around the flag
      generateLight(flag.x, flag.y, flag.glowSize, flag.glowColor);
    }
    
    function updateFlags() {
      flags.forEach(flag => {
        // Update position
        flag.x += flag.speedX;
        flag.y += flag.speedY;
        
        // Update rotation angle
        flag.rotationAngle += flag.rotation;
        
        // Update wave offset
        flag.waveOffset += flag.waveFrequency;
        
        // Bounce when hitting container edges instead of canvas edges
        if (flag.x < containerBounds.left || flag.x > containerBounds.right) {
          flag.speedX *= -1;
          // Keep within bounds
          flag.x = Math.max(containerBounds.left, Math.min(flag.x, containerBounds.right));
        }
        
        if (flag.y < containerBounds.top || flag.y > containerBounds.bottom) {
          flag.speedY *= -1;
          // Keep within bounds
          flag.y = Math.max(containerBounds.top, Math.min(flag.y, containerBounds.bottom));
        }
      });
    }
    
    function animate() {
      // Draw without clearing canvas (fireworks script does that)
      
      // Update container bounds in case of scrolling
      updateContainerBounds();
      
      // Update flag positions
      updateFlags();
      
      // Draw all flags
      flags.forEach(flag => {
        drawFlag(flag);
      });
      
      requestAnimationFrame(animate);
    }
    
    // Handle window resize
    window.addEventListener('resize', () => {
      if (canvas) {
        flags.length = 0; // Clear flags array
        updateContainerBounds();
        createFlags(15);
      }
    });
    
    // Handle scroll events to update container position
    window.addEventListener('scroll', updateContainerBounds);
    
  }, 100); // Small delay to ensure fireworks is initialized first
})(); 