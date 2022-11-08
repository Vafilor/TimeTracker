import { Controller } from '@hotwired/stimulus';
// TODO - redo this class.
export interface CreateFlashOptions {
    type: string;
    title?: string;
    message: string;
    url?: string;
    urlText?: string;
    dismissTimeout?: number;
}

export interface FlashOptions {
    element?: Element;
    bubbles?: boolean;
    cancelable?: boolean;
}

const defaultOptions = {
    bubbles: true,
    cancelable: true
}

export class UseFlash  {
    bubbles: boolean
    cancelable: boolean

    constructor(controller: Controller, options: FlashOptions = {}) {
        // super(controller, options)
        //
        // this.targetElement = options.element ?? controller.element
        // this.bubbles = options.bubbles ?? defaultOptions.bubbles
        // this.cancelable = options.cancelable ?? defaultOptions.cancelable

        this.enhanceController()
    }

    // flash = (detail: CreateFlashOptions): CustomEvent => {
    flash = (detail: CreateFlashOptions) => {
        // const { controller, targetElement, bubbles, cancelable, log } = this
        //
        // // includes the emitting controller in the event detail
        // Object.assign(detail, { controller })
        //
        // const eventName = 'flash:add';
        //
        // // creates the custom event
        // const event = new CustomEvent(eventName, {
        //     detail,
        //     bubbles,
        //     cancelable
        // })
        //
        // // dispatch the event from the given element or by default from the root element of the controller
        // targetElement.dispatchEvent(event)
        //
        // log('dispatch', { eventName, detail, bubbles, cancelable })
        //
        // return event
    }

    private enhanceController() {
        // Object.assign(this.controller, { flash: this.flash })
    }
}

export const useFlash = (controller: Controller, options: FlashOptions = {}): UseFlash => {
    return new UseFlash(controller, options)
}