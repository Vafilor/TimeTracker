import $ from "jquery";
import Observable from "./observable";

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
    private readonly confirmClass: string = '';
    private readonly id: string;
    private $modal?: JQuery;
    public readonly clicked = new Observable<ConfirmClickEvent>();

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

        $('body').append(this.$modal);
        this.$modal.modal('show');
    }

    remove() {
        if (!this.$modal) {
            return;
        }

        this.$modal.on('hidden.bs.modal', () => {
            this.$modal.remove();
            this.$modal = null;
        })

        this.$modal.modal('hide');
    }

    createTemplate(title: string, body: string, cancelText: string, confirmText: string) {
        return `
        <div class="modal fade" id="${this.id}" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">${title}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                ${body}
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-button-id="cancel">${cancelText}</button>
                <button type="button" class="btn ${this.confirmClass}" data-button="confirm" data-button-id="confirm">${confirmText}</button>
              </div>
            </div>
          </div>
        </div>`;
    }
}
// TODO Modal needs optional loading buttons.