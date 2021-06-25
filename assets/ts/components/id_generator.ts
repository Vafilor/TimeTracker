export default class IdGenerator {
    private static lastId = 0;
    static next() {
        return (++IdGenerator.lastId).toString();
    }
}