import TimeElapsedController from './time-elapsed_controller'

export default class TimeElapsedTitleController extends TimeElapsedController {
    updateUI() {
        document.title = super.updateUI();
    }
}