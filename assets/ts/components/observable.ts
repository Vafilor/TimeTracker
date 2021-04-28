export type Observer<T> = (val: T) => void;

export default class Observable<T> {
    private readonly observers: Array<Observer<T>>;

    constructor() {
        this.observers = [];
    }

    addObserver(observer: Observer<T>) {
        this.observers.push(observer);

        return observer;
    }

    removeObserver(observer: Observer<T>) {
        const index = this.observers.indexOf(observer);
        if (index > -1) {
            this.observers.splice(index, 1);
        }
    }

    emit(value: T) {
        for(const observer of this.observers) {
            observer(value);
        }
    }
}