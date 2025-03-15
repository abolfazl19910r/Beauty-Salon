export class Toast {
    /**
     * @param {String} message
     * @param {String} type
     * @param {Number} duration
     */
    static show(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;

        Object.assign(toast.style, {
            position: 'fixed',
            bottom: '20px',
            right: '20px',
            zIndex: '9999',
            minWidth: '250px',
            padding: '12px 16px',
            borderRadius: '4px',
            boxShadow: '0 4px 8px rgba(0,0,0,0.2)',
            animation: 'fadeIn 0.3s, fadeOut 0.3s 4.7s',
            fontFamily: 'inherit',
            fontSize: '14px'
        });

        const colors = {
            success: { bg: '#4CAF50', text: '#fff' },
            error: { bg: '#F44336', text: '#fff' },
            info: { bg: '#2196F3', text: '#fff' },
            warning: { bg: '#FF9800', text: '#fff' }
        };

        if (colors[type]) {
            toast.style.backgroundColor = colors[type].bg;
            toast.style.color = colors[type].text;
        }

        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes fadeOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(20px); }
            }
        `;
        document.head.appendChild(style);

        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                document.body.removeChild(toast);
            }
        }, duration);
    }

    /**
     * @param {String} message
     * @param {Number} duration
     */
    static success(message, duration = 5000) {
        this.show(message, 'success', duration);
    }

    /**
     * @param {String} message
     * @param {Number} duration
     */
    static error(message, duration = 5000) {
        this.show(message, 'error', duration);
    }

    /**
     * @param {String} message
     * @param {Number} duration
     */
    static info(message, duration = 5000) {
        this.show(message, 'info', duration);
    }

    /**
     * @param {String} message
     * @param {Number} duration
     */
    static warning(message, duration = 5000) {
        this.show(message, 'warning', duration);
    }
}

window.toast = Toast;
