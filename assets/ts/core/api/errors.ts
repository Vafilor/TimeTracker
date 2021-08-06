export class ApiError {
    static findByCode(error: any, code: string): any {
        for(const err of error.errors) {
            if (err.code === code) {
                return err;
            }
        }

        return null;
    }
}