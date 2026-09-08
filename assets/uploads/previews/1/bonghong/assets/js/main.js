document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Image gallery thumbnail click handler (detail.php)
    const mainImage = document.getElementById('main-product-image');
    const thumbnails = document.querySelectorAll('.thumb-item');
    if (mainImage && thumbnails.length > 0) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                // Remove active class from all
                thumbnails.forEach(t => t.classList.remove('active'));
                // Add to clicked
                this.classList.add('active');
                // Change main image source
                const newSrc = this.querySelector('img').src;
                mainImage.src = newSrc;
            });
        });
    }

    // 2. Interactive star rating selector (detail.php review form)
    const starButtons = document.querySelectorAll('.star-btn');
    const ratingInput = document.getElementById('rating-value');
    if (starButtons.length > 0 && ratingInput) {
        starButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const rating = parseInt(this.getAttribute('data-value'));
                ratingInput.value = rating;
                
                // Highlight active stars
                starButtons.forEach(s => {
                    const val = parseInt(s.getAttribute('data-value'));
                    if (val <= rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
    }

    // 3. Password strength checker in real-time (auth.php register form)
    const passwordInput = document.getElementById('register-password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    
    if (passwordInput && strengthBar && strengthText) {
        passwordInput.addEventListener('input', function() {
            const val = this.value;
            let score = 0;
            
            if (val.length === 0) {
                strengthBar.style.width = '0';
                strengthText.textContent = '';
                return;
            }
            
            // Criteria checks
            const hasMinLength = val.length >= 8;
            const hasLowercase = /[a-z]/.test(val);
            const hasUppercase = /[A-Z]/.test(val);
            const hasNumber = /[0-9]/.test(val);
            const hasSpecial = /[^A-Za-z0-9]/.test(val);
            
            if (hasMinLength) score += 20;
            if (hasLowercase) score += 20;
            if (hasUppercase) score += 20;
            if (hasNumber) score += 20;
            if (hasSpecial) score += 20;
            
            // Adjust GUI
            strengthBar.style.width = score + '%';
            
            if (score <= 40) {
                strengthBar.style.backgroundColor = 'var(--danger)';
                strengthText.className = 'strength-text weak';
                strengthText.textContent = 'Yếu (Nhập ít nhất 8 ký tự, chữ hoa, thường, số, ký tự đặc biệt)';
            } else if (score < 100) {
                strengthBar.style.backgroundColor = 'var(--warning)';
                strengthText.className = 'strength-text medium';
                strengthText.textContent = 'Trung bình (Cần thêm ký tự phức tạp)';
            } else {
                strengthBar.style.backgroundColor = 'var(--success)';
                strengthText.className = 'strength-text strong';
                strengthText.textContent = 'Mạnh (Mật khẩu hợp lệ)';
            }
        });
    }

    // 4. Client-side form validations for registration strength check
    const registerForm = document.getElementById('register-form');
    if (registerForm && passwordInput) {
        registerForm.addEventListener('submit', function(e) {
            const password = passwordInput.value;
            
            const hasMinLength = password.length >= 8;
            const hasLowercase = /[a-z]/.test(password);
            const hasUppercase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[^A-Za-z0-9]/.test(password);
            
            if (!hasMinLength || !hasLowercase || !hasUppercase || !hasNumber || !hasSpecial) {
                e.preventDefault();
                showToast('Mật khẩu chưa đủ mạnh! Phải chứa ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.', 'error');
            }
        });
    }

    // 5. Toast Notification System
    window.showToast = function(message, type = 'success') {
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerText = message;
        
        document.body.appendChild(toast);
        
        // Show after a tick
        setTimeout(() => {
            toast.classList.add('show');
        }, 50);
        
        // Hide and remove after 4 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 4000);
    };

    // Show server-side session messages if any
    const serverMessage = document.getElementById('server-toast-message');
    if (serverMessage) {
        const msg = serverMessage.getAttribute('data-message');
        const type = serverMessage.getAttribute('data-type') || 'success';
        showToast(msg, type);
    }
});
