document.addEventListener('DOMContentLoaded', () => {
    // FAQ Toggle
    const faqButtons = document.querySelectorAll('[id$="-button"]');
    faqButtons.forEach(button => {
        button.addEventListener('click', () => {
            const contentId = button.id.replace('button', 'content');
            const content = document.getElementById(contentId);
            const isExpanded = !content.classList.contains('hidden');
            content.classList.toggle('hidden');
            const icon = button.querySelector('i');
            icon.classList.toggle('ri-arrow-down-s-line');
            icon.classList.toggle('ri-arrow-up-s-line');
            faqButtons.forEach(otherButton => {
                if (otherButton !== button) {
                    const otherContentId = otherButton.id.replace('button', 'content');
                    const otherContent = document.getElementById(otherContentId);
                    otherContent.classList.add('hidden');
                    const otherIcon = otherButton.querySelector('i');
                    otherIcon.classList.remove('ri-arrow-up-s-line');
                    otherIcon.classList.add('ri-arrow-down-s-line');
                }
            });
        });
    });

    // Mobile Menu Toggle
    const mobileMenuButton = document.querySelector('.md\\:hidden button');
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', () => {
            const navLinks = document.querySelector('.md\\:flex.items-center.space-x-8');
            navLinks.classList.toggle('hidden');
        });
    }

    // OTP Input Auto-focus
    const otpInputs = document.querySelectorAll('.otp-input');
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            if (input.value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && input.value === '' && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
    });

    // Book Preview
    const bookCards = document.querySelectorAll('.book-card');
    bookCards.forEach(card => {
        card.addEventListener('click', (e) => {
            if (!e.target.closest('a')) {
                console.log('Book card clicked:', card.querySelector('h3').textContent);
            }
        });
    });

    // Dashboard Section Toggle
    const toggleButtons = document.querySelectorAll('.toggle-section');
    toggleButtons.forEach(button => {
        button.addEventListener('click', () => {
            const sectionContent = button.closest('div').querySelector('.section-content');
            sectionContent.classList.toggle('hidden');
            const icon = button.querySelector('i');
            icon.classList.toggle('ri-arrow-down-s-line');
            icon.classList.toggle('ri-arrow-up-s-line');
        });
    });

    // Withdrawal Modal
    const withdrawalModal = document.getElementById('withdrawal-modal');
    const openWithdrawalModal = document.getElementById('open-withdrawal-modal');
    const closeWithdrawalModal = document.getElementById('close-withdrawal-modal');

    if (openWithdrawalModal) {
        openWithdrawalModal.addEventListener('click', () => {
            withdrawalModal.classList.remove('hidden');
        });
    }

    if (closeWithdrawalModal) {
        closeWithdrawalModal.addEventListener('click', () => {
            withdrawalModal.classList.add('hidden');
        });
    }

    // Social Sharing
    const shareReferralButton = document.getElementById('share-referral');
    if (shareReferralButton) {
        shareReferralButton.addEventListener('click', () => {
            const referralLink = shareReferralButton.closest('.section-content').querySelector('a').href;
            const shareText = encodeURIComponent('Join my MLM network and earn with me! ' + referralLink);
            const shareOptions = [
                { name: 'WhatsApp', url: `https://wa.me/?text=${shareText}` },
                { name: 'Twitter', url: `https://twitter.com/intent/tweet?text=${shareText}` },
                { name: 'Facebook', url: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(referralLink)}` }
            ];
            const shareMenu = document.createElement('div');
            shareMenu.className = 'absolute bg-white shadow-lg rounded-lg p-4 mt-2';
            shareOptions.forEach(option => {
                const link = document.createElement('a');
                link.href = option.url;
                link.target = '_blank';
                link.className = 'block py-2 px-4 text-gray-700 hover:bg-gray-100';
                link.textContent = `Share on ${option.name}`;
                shareMenu.appendChild(link);
            });
            shareReferralButton.parentElement.appendChild(shareMenu);
            setTimeout(() => shareMenu.remove(), 5000);
        });
    }

    // Earnings Chart
    const ctx = document.getElementById('earningsChart')?.getContext('2d');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: earningsLabels,
                datasets: [{
                    label: 'Earnings (Ksh)',
                    data: earningsData,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: { title: { display: true, text: 'Date' } },
                    y: { title: { display: true, text: 'Earnings (Ksh)' }, beginAtZero: true }
                }
            }
        });
    }

    // Testimonial Carousel
    const testimonialCarousel = document.getElementById('testimonial-carousel');
    const testimonialCards = document.querySelectorAll('.testimonial-card');
    let currentTestimonial = 0;

    if (testimonialCarousel) {
        testimonialCards[currentTestimonial].classList.add('active');
        document.getElementById('next-testimonial').addEventListener('click', () => {
            testimonialCards[currentTestimonial].classList.remove('active');
            currentTestimonial = (currentTestimonial + 1) % testimonialCards.length;
            testimonialCards[currentTestimonial].classList.add('active');
        });
        document.getElementById('prev-testimonial').addEventListener('click', () => {
            testimonialCards[currentTestimonial].classList.remove('active');
            currentTestimonial = (currentTestimonial - 1 + testimonialCards.length) % testimonialCards.length;
            testimonialCards[currentTestimonial].classList.add('active');
        });
        setInterval(() => {
            testimonialCards[currentTestimonial].classList.remove('active');
            currentTestimonial = (currentTestimonial + 1) % testimonialCards.length;
            testimonialCards[currentTestimonial].classList.add('active');
        }, 5000);
    }

    // Learn More Modal
    const learnMoreModal = document.getElementById('learn-more-modal');
    const learnMoreButtons = document.querySelectorAll('#learn-more-btn, #learn-more-btn-cta');
    const closeLearnMoreModal = document.getElementById('close-learn-more-modal');

    learnMoreButtons.forEach(button => {
        button.addEventListener('click', () => {
            learnMoreModal.classList.remove('hidden');
        });
    });

    if (closeLearnMoreModal) {
        closeLearnMoreModal.addEventListener('click', () => {
            learnMoreModal.classList.add('hidden');
        });
    }

    // Copy Affiliate Link
    const copyButtons = document.querySelectorAll('.copy-link');
    copyButtons.forEach(button => {
        button.addEventListener('click', () => {
            const link = button.getAttribute('data-link');
            navigator.clipboard.writeText(link).then(() => {
                button.innerHTML = '<i class="ri-check-line"></i> Copied!';
                setTimeout(() => {
                    button.innerHTML = '<i class="ri-clipboard-line"></i> Copy';
                }, 2000);
            });
        });
    });

    // Scroll-Triggered Animations
    const animateElements = document.querySelectorAll('.animate-slide-in');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.2 });

    animateElements.forEach(element => {
        observer.observe(element);
    });
});