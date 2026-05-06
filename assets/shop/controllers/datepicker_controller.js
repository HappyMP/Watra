import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.setAttribute('min', new Date().toISOString().split('T')[0]);
    }
}
