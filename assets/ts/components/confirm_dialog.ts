import $ from "jquery";
import Observable from "./observable";
import LoadingButton from "./loading_button";
import { Modal } from 'bootstrap';

export type ConfirmButtonClicked = 'confirm' | 'cancel';

export interface ConfirmClickEvent {
    buttonClicked: ConfirmButtonClicked;
    source: ConfirmDialog;
}

export interface ConfirmDialogShowOptions {
    title: string;
    body: string;
    cancelText?: string;
    confirmText?: string;
}

export class ConfirmDialog {
    private readonly id: string;
    public readonly clicked = new Observable<ConfirmClickEvent>();
    private confirmButton?: LoadingButton;
    private readonly confirmClass: string = '';
    private $modal?: JQuery;
    private modal?: Modal;

    constructor(confirmClass: string = 'btn-primary') {
        this.id = Math.floor(Math.random() * 100000).toString();
        this.confirmClass = confirmClass;
    }

    show(options: ConfirmDialogShowOptions) {
        const title = options.title;
        const body = options.body;
        const cancelText = options.cancelText ? options.cancelText : 'cancel';
        const confirmText = options.confirmText ? options.confirmText : 'confirm';

        this.$modal = $(this.createTemplate(title, body, cancelText, confirmText));

        this.$modal.find('button').on('click', (event) => {
            const target = $(event.currentTarget);
            const buttonId = target.data('button-id') as ConfirmButtonClicked;

            this.clicked.emit({
                buttonClicked: buttonId,
                source: this
            });
        })

        this.confirmButton = new LoadingButton(this.$modal.find('.js-confirm'));

        $('body').append(this.$modal);

        this.modal = new Modal(this.$modal[0]);
        this.modal.show();
    }

    startLoading() {
        this.confirmButton?.startLoading();
    }

    stopLoading() {
        this.confirmButton?.startLoading();
    }

    remove() {
        if (!this.$modal) {
            return;
        }

        this.$modal.on('hidden.bs.modal', () => {
            if (!this.$modal) {
                return;
            }

            this.$modal.remove();
            this.$modal = undefined;
        })

        if (this.modal) {
            this.modal.hide();
        }
    }

    createTemplate(title: string, body: string, cancelText: string, confirmText: string) {
        return `
        <div class="modal fade" id="${this.id}" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">${title}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                ${body}
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-button-id="cancel">${cancelText}</button>
                <button type="button" class="btn js-confirm ${this.confirmClass}" data-button="confirm" data-button-id="confirm">
                    <span class="spinner-border spinner-border-sm d-none js-loading" role="status" aria-hidden="true"></span>
                    ${confirmText}
                </button>
              </div>
            </div>
          </div>
        </div>`;
    }
}