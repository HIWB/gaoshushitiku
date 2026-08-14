// ================================================================
// 关于弹窗
// ================================================================

function openAbout(profileData) {
    document.getElementById('profileName').textContent = profileData.name || '姓名';
    document.getElementById('profileRole').textContent = profileData.role || '';
    document.getElementById('profileBio').textContent = profileData.bio || '';
    document.getElementById('profileEmail').textContent = profileData.email || '-';
    document.getElementById('profileWechat').textContent = profileData.wechat || '-';
    document.getElementById('profileSchool').textContent = profileData.school || '-';
    document.getElementById('profileAvatar').src = profileData.avatar || '';

    document.getElementById('aboutOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeAbout() {
    document.getElementById('aboutOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

// 点击外部关闭关于弹窗
document.addEventListener('click', function(e) {
    if (e.target.id === 'aboutOverlay') closeAbout();
});

// ESC 关闭关于
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAbout();
        closeLightbox();
    }
});

// ================================================================
// 图片预览器（全屏 + 缩略图导航 + 分组标签 + 拖动）
// ================================================================

let lightboxItems = [];
let currentLightboxIndex = 0;
let scale = 1;
let translateX = 0;
let translateY = 0;
let isDragging = false;
let startX = 0, startY = 0;
let lastTranslateX = 0, lastTranslateY = 0;

function openLightbox(imageSrc, allItems) {
    if (!allItems || allItems.length === 0) return;
    lightboxItems = allItems;
    const index = allItems.findIndex(item => item.src === imageSrc);
    currentLightboxIndex = index >= 0 ? index : 0;

    scale = 1;
    translateX = 0;
    translateY = 0;
    updateImageTransform();

    document.getElementById('lightboxOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    renderLightbox(currentLightboxIndex);
}

function renderLightbox(index) {
    if (!lightboxItems.length) return;
    if (index < 0) index = lightboxItems.length - 1;
    if (index >= lightboxItems.length) index = 0;
    currentLightboxIndex = index;

    const item = lightboxItems[index];
    const img = document.getElementById('lightboxImage');
    img.src = item.src;

    scale = 1;
    translateX = 0;
    translateY = 0;
    updateImageTransform();

    document.getElementById('currentIdxDisplay').textContent = index + 1;
    document.getElementById('totalCountDisplay').textContent = lightboxItems.length;

    renderGroupLabels();
    renderThumbs();

    const activeThumb = document.querySelector('.lightbox-thumbs .thumb-item.active');
    if (activeThumb) {
        activeThumb.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
    }
}

function renderGroupLabels() {
    const container = document.getElementById('groupLabels');
    const groups = ['paper', 'answer', 'solution'];
    const groupNames = { paper: '试卷', answer: '标准答案', solution: '我的解答' };
    const groupColors = {
        paper: 'rgba(52, 152, 219, 0.3)',
        answer: 'rgba(46, 204, 113, 0.3)',
        solution: 'rgba(231, 76, 60, 0.3)'
    };

    let html = '';
    groups.forEach(g => {
        const count = lightboxItems.filter(item => item.group === g).length;
        const isActive = lightboxItems[currentLightboxIndex].group === g;
        html += `
            <span class="group-label ${isActive ? 'active' : ''}" style="${isActive ? 'border-color:' + groupColors[g] : ''}">
                ${groupNames[g]} <span class="count">${count}</span>
            </span>
        `;
    });
    container.innerHTML = html;
}

function renderThumbs() {
    const container = document.getElementById('lightboxThumbs');
    let html = '';
    lightboxItems.forEach((item, i) => {
        const isActive = i === currentLightboxIndex;
        const groupLabel = { paper: '卷', answer: '答', solution: '解' }[item.group] || '';
        html += `
            <div class="thumb-item ${isActive ? 'active' : ''}" data-thumb-index="${i}">
                <img src="${item.src}" alt="缩略图 ${i+1}" loading="lazy" />
                <span class="thumb-badge">${groupLabel}</span>
            </div>
        `;
    });
    container.innerHTML = html;

    container.querySelectorAll('.thumb-item').forEach(el => {
        el.addEventListener('click', function() {
            const idx = parseInt(this.dataset.thumbIndex);
            renderLightbox(idx);
        });
    });
}

function updateImageTransform() {
    const img = document.getElementById('lightboxImage');
    img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
}

function closeLightbox() {
    document.getElementById('lightboxOverlay').classList.remove('open');
    document.body.style.overflow = '';
    lightboxItems = [];
    scale = 1;
    translateX = 0;
    translateY = 0;
    updateImageTransform();
}

function prevImage() {
    renderLightbox(currentLightboxIndex - 1);
}

function nextImage() {
    renderLightbox(currentLightboxIndex + 1);
}

// ---- 滚轮缩放 ----
document.addEventListener('DOMContentLoaded', function() {
    const mainEl = document.getElementById('lightboxMain');
    if (!mainEl) return;

    mainEl.addEventListener('wheel', function(e) {
        if (!document.getElementById('lightboxOverlay').classList.contains('open')) return;
        e.preventDefault();
        const delta = e.deltaY > 0 ? -0.15 : 0.15;
        scale = Math.min(Math.max(scale + delta, 0.3), 3);
        updateImageTransform();
    }, { passive: false });

    // ---- 鼠标拖动 ----
    const img = document.getElementById('lightboxImage');

    mainEl.addEventListener('mousedown', function(e) {
        if (!document.getElementById('lightboxOverlay').classList.contains('open')) return;
        if (scale <= 1) return;
        isDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        lastTranslateX = translateX;
        lastTranslateY = translateY;
        mainEl.style.cursor = 'grabbing';
    });

    document.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        translateX = lastTranslateX + dx;
        translateY = lastTranslateY + dy;
        updateImageTransform();
    });

    document.addEventListener('mouseup', function() {
        if (isDragging) {
            isDragging = false;
            mainEl.style.cursor = 'grab';
        }
    });

    // ---- 触摸支持 ----
    let touchStartX = 0, touchStartY = 0;
    let touchLastX = 0, touchLastY = 0;
    let isTouching = false;

    mainEl.addEventListener('touchstart', function(e) {
        if (!document.getElementById('lightboxOverlay').classList.contains('open')) return;
        if (scale <= 1) return;
        const touch = e.touches[0];
        touchStartX = touch.clientX;
        touchStartY = touch.clientY;
        touchLastX = translateX;
        touchLastY = translateY;
        isTouching = true;
    }, { passive: true });

    mainEl.addEventListener('touchmove', function(e) {
        if (!isTouching || scale <= 1) return;
        const touch = e.touches[0];
        const dx = touch.clientX - touchStartX;
        const dy = touch.clientY - touchStartY;
        translateX = touchLastX + dx;
        translateY = touchLastY + dy;
        updateImageTransform();
    }, { passive: true });

    mainEl.addEventListener('touchend', function() {
        isTouching = false;
    }, { passive: true });

    // ---- 键盘控制 ----
    document.addEventListener('keydown', function(e) {
        if (!document.getElementById('lightboxOverlay').classList.contains('open')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') prevImage();
        if (e.key === 'ArrowRight') nextImage();
    });

    // ---- 按钮事件 ----
    document.getElementById('lightboxClose')?.addEventListener('click', closeLightbox);
    document.getElementById('lightboxPrev')?.addEventListener('click', prevImage);
    document.getElementById('lightboxNext')?.addEventListener('click', nextImage);

    // ---- 点击背景关闭 ----
    document.getElementById('lightboxOverlay')?.addEventListener('click', function(e) {
        if (e.target === this) closeLightbox();
    });
});