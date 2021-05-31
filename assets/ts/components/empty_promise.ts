export function createResolvePromise(): Promise<void> {
    return new Promise<void>(function (resolve, reject) {
        resolve();
    });
}