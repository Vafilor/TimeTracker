import { Controller } from '@hotwired/stimulus';

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

export class UseFlash  {
    declare targetElement: Element;

    private bubbles: boolean
    private cancelable: boolean

    constructor(private readonly controller: Controller, options: FlashOptions = {}) {
        this.controller = controller;

        this.targetElement = options.element ?? controller.element;
        this.bubbles = options.bubbles ?? true;
        this.cancelable = options.cancelable ?? true;

        this.enhanceController()
    }

    flash = (detail: CreateFlashOptions): CustomEvent => {
        const { controller, targetElement, bubbles, cancelable } = this

        // includes the emitting controller in the event detail
        Object.assign(detail, { controller })

        const eventName = 'flash:add';

        // creates the custom event
        const event = new CustomEvent(eventName, {
            detail,
            bubbles,
            cancelable
        });

        // dispatch the event from the given element or by default from the root element of the controller
        targetElement.dispatchEvent(event)

        return event
    }

    private enhanceController() {
        Object.assign(this.controller, { flash: this.flash })
    }
}

export const useFlash = (controller: Controller, options: FlashOptions = {}): UseFlash => {
    return new UseFlash(controller, options)
}