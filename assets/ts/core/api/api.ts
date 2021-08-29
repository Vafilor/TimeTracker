export interface PaginatedResponse<T> {
    page: number;
    perPage: number;
    totalCount: number;
    totalPages: number;
    count: number;
    data: Array<T>;
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