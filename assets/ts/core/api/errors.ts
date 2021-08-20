export class ApiError {
    static findByCode(error: any, code: string): any {
        if (!error || !error.errors) {
            return null;
        }

        for(const err of error.errors) {
            if (err.code === code) {
                return err;
            }
        }

        return null;
    }
}