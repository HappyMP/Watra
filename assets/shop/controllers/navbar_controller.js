import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { threshold: { type: Number, default: 60 } };

    connect() {
        this._onScroll = this._handleScroll.bind(this);
        window.addEventListener('scroll', this._onScroll, { passive: true });
        this._handleScroll();
    }

    disconnect() {
        window.removeEventListener('scroll', this._onScroll);
    }

    _handleScroll() {
        const scrolled = window.scrollY > this.thresholdValue;
        this.element.classList.toggle('watra-navbar--scrolled', scrolled);
    }
}
