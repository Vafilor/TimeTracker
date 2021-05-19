export interface JsonResponse<T> {
    source: Response;
    data: T
}

export interface ApiError {
    code: string;
    message: string;
}

export interface ApiResourceError extends ApiError {
    resource: string;
}

export class ApiErrorResponse extends Error {
    public response: Response;
    public errors: ApiError[]; // TODO how I do I indicate the error might have additional fields like resource?

    public constructor(response: Response, body?: any) {
        super(response.statusText);

        this.response = response;

        if (body && body.errors) {
            this.errors = body.errors;
        } else {
            this.errors = [];
        }
    }

    public hasErrorCode(code: string): boolean {
        for(const error of this.errors) {
            if (error.code === code) {
                return true;
            }
        }

        return false;
    }

    public getErrorForCode(code: string): ApiError|null {
        for(const error of this.errors) {
            if (error.code === code) {
                return error;
            }
        }

        return null;
    }
}

export class CoreApi {
    static fetchJson<T>(url: string, options: RequestInit) {
        let headers = {
            'Content-Type': 'application/json',
            // @ts-ignore
            'X-Csrf-Token': window.CSRF_TOKEN,
        };

        if (options && options.headers) {
            headers = { ...options.headers, ...headers };
            delete options.headers;
        }

        return fetch(url, Object.assign({
            credentials: 'same-origin',
            headers,
        }, options))
            .then(CoreApi.checkStatus)
            .then( (response: Response) => {
                if (response.headers.has('X-CSRF-TOKEN')) {
                    // @ts-ignore
                    window.CSRF_TOKEN = response.headers.get('X-CSRF-TOKEN');
                }

                // decode JSON, but avoid problems with empty responses
                return response.text()
                    .then(text => {
                        return {
                            source: response,
                            data: text ? JSON.parse(text) : null
                        };
                    })
            });
    }

    static delete(url: string, options: RequestInit = {}): Promise<any> {
        options.method = 'DELETE';

        return CoreApi.fetchJson(url, options)
    }

    static get<T>(url: string, options: RequestInit = {}): Promise<JsonResponse<T>> {
        options.method = 'GET';

        return CoreApi.fetchJson(url, options)
    }

    static post<T>(url: string, data: any, options: RequestInit = {}): Promise<JsonResponse<T>> {
        options.method = 'POST';
        options.body = JSON.stringify(data);

        return CoreApi.fetchJson(url, options)
    }

    static put<T>(url: string, data: any, options: RequestInit = {}): Promise<JsonResponse<T>> {
        options.method = 'PUT';
        options.body = JSON.stringify(data);

        return CoreApi.fetchJson(url, options)
    }

    static checkStatus(response: Response) {
        if (response.status >= 200 && response.status < 400) {
            return response;
        }

        return response.text()
            .then(text => {
                const body = text ? JSON.parse(text) : undefined;
                throw new ApiErrorResponse(response, body);
            })
    }
}

