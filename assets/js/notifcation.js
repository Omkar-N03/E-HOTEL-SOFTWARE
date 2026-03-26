const SmartNotify = {
    init() {
        if (!document.getElementById('notify-container')) {
            const container = document.createElement('div');
            container.id = 'notify-container';
            document.body.appendChild(container);
        }
    },
    show(title, message, type = 'info', sound = false) {
        this.init();
        const container = document.getElementById('notify-container');
        const toast = document.createElement('div');
        toast.className = `toast-item toast-${type}`;
        const icons = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-circle',
            warning: 'fa-bell',
            info: 'fa-info-circle'
        };
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fa ${icons[type]}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fa fa-times"></i>
            </button>
        `;
        container.appendChild(toast);
        if (sound) this.playAlert(type);
        setTimeout(() => toast.classList.add('toast-show'), 10);
        setTimeout(() => {
            toast.classList.remove('toast-show');
            setTimeout(() => toast.remove(), 400);
        }, 5000);
    },
    playAlert(type) {
        const soundMap = {
            success: '../assets/audio/success.mp3',
            danger: '../assets/audio/alert.mp3',
            info: '../assets/audio/ding.mp3'
        };
        const audio = new Audio(soundMap[type] || soundMap.info);
        audio.play().catch(() => console.log("Audio interaction required."));
    }
};
