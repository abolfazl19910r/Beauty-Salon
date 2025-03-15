export class ErrorHandler {
    /**
     * @param {Error} error
     * @param {Function} retry
     * @param {Number} maxRetries
     * @param {Number} currentRetry
     * @returns {Promise}
     */
    static async handleNetworkError(error, retry, maxRetries = 3, currentRetry = 0) {
        if ((error.message.includes('network') || error.message.includes('fetch')) && currentRetry < maxRetries) {
            const delay = Math.pow(2, currentRetry) * 1000;
            await new Promise(resolve => setTimeout(resolve, delay));

            return retry().catch(err =>
                this.handleNetworkError(err, retry, maxRetries, currentRetry + 1)
            );
        }

        throw error;
    }

    /**
     * @param {Response} response
     * @returns {Object}
     */
    static async handleResponse(response) {
        if (!response.ok) {
            let errorMessage = `خطای ${response.status}: ${response.statusText}`;

            try {
                const errorData = await response.json();
                if (errorData.message) {
                    errorMessage = errorData.message;
                } else if (errorData.error) {
                    errorMessage = errorData.error;
                }
            } catch (e) {
                try {
                    const errorText = await response.text();
                    if (errorText) errorMessage += ` - ${errorText}`;
                } catch (textError) {
                }
            }

            const error = new Error(errorMessage);
            error.status = response.status;
            throw error;
        }

        try {
            return await response.json();
        } catch (e) {
            return await response.text();
        }
    }

    /**
     * @param {Error} error
     * @param {String} fallbackMessage
     */
    static showErrorToUser(error, fallbackMessage = 'خطایی رخ داد. لطفاً دوباره تلاش کنید.') {
        const message = error.message || fallbackMessage;

        if (window.toast) {
            window.toast.error(message);
            return;
        }

        alert(message);
    }
}

/**
 * @param {String} url
 * @param {Object} options
 * @returns {Promise}
 */
export async function fetchWithErrorHandling(url, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        ...(options.headers || {})
    };

    const executeFetch = () => fetch(url, { ...options, headers })
        .then(ErrorHandler.handleResponse);

    try {
        return await executeFetch();
    } catch (error) {
        return ErrorHandler.handleNetworkError(error, executeFetch)
            .catch(finalError => {
                ErrorHandler.showErrorToUser(finalError);
                throw finalError;
            });
    }
}
