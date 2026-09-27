/**
 * 🌸 Sakura Blossom Falling Engine 3.0 — Botanical Sakura Edition 🌸
 * - 3 Kiểu hoa anh đào phong phú:
 *   + 🌺 Bông hoa 5 cánh nguyên vẹn nở rộ xoay tròn (Full 5-Petal Flower)
 *   + 🌸 Cánh hoa đơn uốn cong có gân tinh tế (Curved Single Petal)
 *   + 🍃 Cặp 2 cánh hoa dính nhau chao lượn (Double Petal Pair)
 * - Tông màu hồng thắm đào tươi (Vibrant Rosy Sakura, không bị trắng bệch)
 * - Ánh sáng nhẹ lung linh rõ nét trên cả Dark Mode & Light Mode
 * - Rơi chậm bồng bềnh, uốn lượn 3D theo làn gió
 * - Phản hồi cử chỉ chuột tạo luồng gió nhẹ
 */
(function() {
    if (window.__sakuraEngineLoaded) {
        var old = document.getElementById('sakuraCanvas');
        if (old && old.parentNode) old.parentNode.removeChild(old);
    }
    window.__sakuraEngineLoaded = true;

    var canvas = document.createElement('canvas');
    canvas.id = 'sakuraCanvas';
    canvas.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;pointer-events:none !important;z-index:5;';
    document.body.appendChild(canvas);

    var ctx = canvas.getContext('2d');
    var width = (canvas.width = window.innerWidth);
    var height = (canvas.height = window.innerHeight);

    window.addEventListener('resize', function() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
    });

    var mouse = { x: -1000, y: -1000, vx: 0, vy: 0, lastX: 0, lastY: 0 };
    window.addEventListener('mousemove', function(e) {
        mouse.vx = (e.clientX - mouse.lastX) * 0.12;
        mouse.vy = (e.clientY - mouse.lastY) * 0.12;
        mouse.x = mouse.lastX = e.clientX;
        mouse.y = mouse.lastY = e.clientY;
    }, { passive: true });

    var NUM_ITEMS = 38;
    var items = [];

    function randomRange(min, max) {
        return Math.random() * (max - min) + min;
    }

    function createSakuraItem(initialY) {
        // Tỷ lệ xuất hiện: 60% cánh đơn, 25% bông hoa 5 cánh, 15% cặp cánh đôi
        var roll = Math.random();
        var type = roll < 0.60 ? 'single' : (roll < 0.85 ? 'full_flower' : 'double');

        var size, baseSpeedY, opacity;
        if (type === 'full_flower') {
            size = randomRange(14, 22);
            baseSpeedY = randomRange(0.28, 0.52); // bông hoa rơi chậm, xoay tròn
            opacity = randomRange(0.85, 0.98);
        } else if (type === 'double') {
            size = randomRange(13, 19);
            baseSpeedY = randomRange(0.35, 0.62);
            opacity = randomRange(0.78, 0.95);
        } else {
            size = randomRange(12, 20);
            baseSpeedY = randomRange(0.32, 0.68);
            opacity = randomRange(0.75, 0.95);
        }

        return {
            type: type,
            x: Math.random() * width,
            y: initialY !== undefined ? initialY : randomRange(-50, height),
            size: size,
            speedY: baseSpeedY,
            speedX: randomRange(-0.25, 0.25),
            
            // Dao động gió & lượn sóng
            sway: randomRange(1.0, 2.5),
            swaySpeed: randomRange(0.012, 0.025),
            swayOffset: Math.random() * Math.PI * 2,
            
            // Góc xoay
            angle: Math.random() * Math.PI * 2,
            rotSpeed: type === 'full_flower' ? randomRange(-0.015, 0.015) : randomRange(-0.02, 0.02),
            
            // Lật không gian 3D
            flipX: Math.random() * Math.PI,
            flipXSpeed: type === 'full_flower' ? randomRange(0.008, 0.018) : randomRange(0.016, 0.034),
            flipY: Math.random() * Math.PI,
            flipYSpeed: randomRange(0.01, 0.02),

            opacity: opacity
        };
    }

    for (var i = 0; i < NUM_ITEMS; i++) {
        items.push(createSakuraItem(randomRange(-height, height)));
    }

    // 🌸 1. Vẽ cánh hoa đơn (Single Petal) với đường cong sinh động & màu hồng thắm
    function drawSinglePetal(s, opacity) {
        ctx.beginPath();
        ctx.moveTo(0, 0);
        ctx.bezierCurveTo(-s * 0.48, -s * 0.26, -s * 0.65, -s * 0.82, -s * 0.22, -s);
        ctx.lineTo(0, -s * 0.86); // Vết khuyết chữ V đầu cánh hoa
        ctx.lineTo(s * 0.22, -s);
        ctx.bezierCurveTo(s * 0.65, -s * 0.82, s * 0.48, -s * 0.26, 0, 0);

        var grad = ctx.createRadialGradient(0, -s * 0.45, 0, 0, -s * 0.45, s * 1.05);
        grad.addColorStop(0, 'rgba(255, 235, 245, ' + opacity + ')');
        grad.addColorStop(0.3, 'rgba(255, 175, 204, ' + (opacity * 0.95) + ')');
        grad.addColorStop(0.75, 'rgba(255, 117, 140, ' + (opacity * 0.9) + ')');
        grad.addColorStop(1, 'rgba(244, 63, 94, ' + (opacity * 0.85) + ')');

        ctx.fillStyle = grad;
        ctx.shadowColor = 'rgba(255, 117, 140, ' + (opacity * 0.5) + ')';
        ctx.shadowBlur = 5;
        ctx.fill();

        // Gân hoa khẽ uốn lượn
        ctx.beginPath();
        ctx.moveTo(0, -s * 0.05);
        ctx.quadraticCurveTo(-s * 0.04, -s * 0.45, 0, -s * 0.72);
        ctx.strokeStyle = 'rgba(255, 255, 255, ' + (opacity * 0.55) + ')';
        ctx.lineWidth = 0.9;
        ctx.stroke();
    }

    // 🌺 2. Vẽ nguyên bông hoa anh đào 5 cánh nở rộ xoay tròn (Full 5-Petal Flower)
    function drawFullFlower(s, opacity) {
        var petalLen = s * 0.88;
        for (var i = 0; i < 5; i++) {
            ctx.save();
            ctx.rotate((i * Math.PI * 2) / 5);

            ctx.beginPath();
            ctx.moveTo(0, 0);
            ctx.bezierCurveTo(-petalLen * 0.38, -petalLen * 0.35, -petalLen * 0.48, -petalLen * 0.82, -petalLen * 0.16, -petalLen);
            ctx.lineTo(0, -petalLen * 0.86); // Vết khuyết
            ctx.lineTo(petalLen * 0.16, -petalLen);
            ctx.bezierCurveTo(petalLen * 0.48, -petalLen * 0.82, petalLen * 0.38, -petalLen * 0.35, 0, 0);

            var pGrad = ctx.createRadialGradient(0, -petalLen * 0.4, 0, 0, -petalLen * 0.4, petalLen);
            pGrad.addColorStop(0, 'rgba(255, 240, 245, ' + opacity + ')');
            pGrad.addColorStop(0.35, 'rgba(255, 182, 193, ' + (opacity * 0.95) + ')');
            pGrad.addColorStop(0.75, 'rgba(255, 105, 180, ' + (opacity * 0.9) + ')');
            pGrad.addColorStop(1, 'rgba(244, 63, 94, ' + (opacity * 0.85) + ')');

            ctx.fillStyle = pGrad;
            ctx.shadowColor = 'rgba(255, 105, 180, ' + (opacity * 0.45) + ')';
            ctx.shadowBlur = 4;
            ctx.fill();

            ctx.restore();
        }

        // Nhụy hoa hồng đậm ở tâm
        ctx.beginPath();
        ctx.arc(0, 0, s * 0.22, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(244, 63, 94, ' + (opacity * 0.9) + ')';
        ctx.fill();

        // 5 hạt phấn hoa vàng tươi xinh xắn
        for (var j = 0; j < 5; j++) {
            var a = (j * Math.PI * 2) / 5 + 0.32;
            var r = s * 0.26;
            ctx.beginPath();
            ctx.arc(Math.cos(a) * r, Math.sin(a) * r, s * 0.055, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(254, 240, 138, ' + opacity + ')';
            ctx.shadowColor = 'rgba(250, 204, 21, 0.8)';
            ctx.shadowBlur = 3;
            ctx.fill();
        }
    }

    // 🍃 3. Vẽ cặp 2 cánh hoa dính nhau lượn vòng (Double Petal Pair)
    function drawDoublePetal(s, opacity) {
        ctx.save();
        ctx.rotate(-0.28);
        drawSinglePetal(s * 0.92, opacity);
        ctx.restore();

        ctx.save();
        ctx.rotate(0.36);
        drawSinglePetal(s * 0.85, opacity * 0.92);
        ctx.restore();
    }

    var isRunning = true;
    var animFrame = null;
    var globalTime = 0;

    function renderItem(p) {
        ctx.save();
        ctx.translate(p.x, p.y);

        var dynamicTilt = p.angle + Math.sin(p.swayOffset) * 0.3;
        ctx.rotate(dynamicTilt);

        var scaleX = Math.cos(p.flipX);
        var scaleY = 0.8 + Math.sin(p.flipY) * 0.2;
        ctx.scale(scaleX, scaleY);

        if (p.type === 'full_flower') {
            drawFullFlower(p.size, p.opacity);
        } else if (p.type === 'double') {
            drawDoublePetal(p.size, p.opacity);
        } else {
            drawSinglePetal(p.size, p.opacity);
        }

        ctx.restore();
    }

    function animate() {
        if (!isRunning) return;
        ctx.clearRect(0, 0, width, height);

        globalTime += 0.016;
        var ambientWind = Math.sin(globalTime * 0.32) * 0.42 + 0.38;

        mouse.vx *= 0.92;
        mouse.vy *= 0.92;

        for (var i = 0; i < items.length; i++) {
            var p = items[i];

            p.swayOffset += p.swaySpeed;
            p.angle += p.rotSpeed;
            p.flipX += p.flipXSpeed;
            p.flipY += p.flipYSpeed;

            // Lực cản không khí giúp hoa dập dềnh bay chậm bồng bềnh
            var airDrag = 1.0 - Math.abs(Math.cos(p.flipX)) * 0.3;
            var currentSpeedY = p.speedY * airDrag + Math.sin(p.swayOffset * 1.5) * 0.12;

            p.y += currentSpeedY;
            p.x += Math.sin(p.swayOffset) * p.sway + ambientWind + p.speedX;

            // Gió tương tác khi rê chuột qua gần
            var dx = p.x - mouse.x;
            var dy = p.y - mouse.y;
            var dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < 90 && dist > 0) {
                var force = (90 - dist) / 90;
                p.x += (dx / dist) * force * 3.6 + mouse.vx * 0.4;
                p.y += (dy / dist) * force * 2.2 + mouse.vy * 0.4;
                p.flipX += 0.09;
            }

            // Tái tạo khi rơi khỏi mép màn hình
            if (p.y > height + 40 || p.x > width + 50 || p.x < -50) {
                items[i] = createSakuraItem(-30);
            }

            renderItem(p);
        }

        animFrame = requestAnimationFrame(animate);
    }

    animate();

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            isRunning = false;
            cancelAnimationFrame(animFrame);
        } else {
            if (!isRunning) {
                isRunning = true;
                animate();
            }
        }
    });

    window.toggleSakura = function() {
        if (canvas.style.display === 'none') {
            canvas.style.display = 'block';
            if (!isRunning) {
                isRunning = true;
                animate();
            }
        } else {
            canvas.style.display = 'none';
        }
    };
})();
