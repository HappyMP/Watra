import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    clear() {
        this.element.value = '';
        this.element.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
