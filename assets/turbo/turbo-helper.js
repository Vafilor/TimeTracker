import * as Turbo from '@hotwired/turbo';

class TurboHelper {
    constructor() {
        document.addEventListener('turbo:before-cache', () => {
            this.beforeCache();
        });

        document.addEventListener('turbo:before-fetch-request', (event) => {
            this.beforeFetchRequest(event);
        })

        document.addEventListener('turbo:before-fetch-response', (event) => {
            this.beforeFetchResponse(event);
        })
    }

    beforeCache() {
        document.querySelectorAll('[data-app-turbo-cache=show]').forEach(element => {
            element.classList.remove('d-none');
        });

        document.querySelectorAll('[data-app-turbo-cache=hide]').forEach(element => {
            element.classList.add('d-none');
        });
    }

    beforeFetchRequest(event) {
        const frameId = event.detail.fetchOptions.headers['Turbo-Frame'];
        if (!frameId) {
            return;
        }

        const frame = document.querySelector(`#${frameId}`);

        if (!frame || !frame.dataset.turboFormRedirect) {
            return;
        }

        event.detail.fetchOptions.headers['Turbo-Frame-Redirect'] = 1;
    }

    beforeFetchResponse(event) {
        const fetchResponse = event.detail.fetchResponse;
        const redirectLocation = fetchResponse.response.headers.get('Turbo-Location');
        if (!redirectLocation) {
            return;
        }

        event.preventDefault();
        Turbo.clearCache();
        Turbo.visit(redirectLocation);
    }
}

export default new TurboHelper();